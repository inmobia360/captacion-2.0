<?php
/**
 * Compra Captación - API de Créditos y Ledger Contable (Credits Core)
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';
$user = require_auth();
$userId = (int)$user['id'];

// Liberar reservas vencidas antes de mostrar o modificar el saldo.
try {
    $db->beginTransaction();
    $expired = $db->prepare("SELECT user_id, credits FROM credit_reservations WHERE status = 'reserved' AND expires_at <= CURRENT_TIMESTAMP");
    $expired->execute();
    foreach ($expired->fetchAll(PDO::FETCH_ASSOC) as $reservation) {
        $db->prepare("UPDATE wallets SET reserved_balance = MAX(0, reserved_balance - ?), available_balance = available_balance + ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
           ->execute([(float)$reservation['credits'], (float)$reservation['credits'], (int)$reservation['user_id']]);
    }
    $db->exec("UPDATE credit_reservations SET status = 'expired', updated_at = CURRENT_TIMESTAMP WHERE status = 'reserved' AND expires_at <= CURRENT_TIMESTAMP");
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
}

// 1. ESTADO DE CRÉDITOS Y MONEDERO CON VERIFICACIÓN DE BIENVENIDA
if ($action === 'status') {
    $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();

    if ($wallet && (float)$wallet['available_balance'] == 250.0 && ($user['role'] ?? '') !== 'admin') {
        $db->prepare("UPDATE wallets SET available_balance = 3.0, total_granted = 3.0, expires_at = datetime('now', '+30 days'), cumulative = 0 WHERE user_id = ?")->execute([$userId]);
        $wallet['available_balance'] = 3.0;
    }

    if (!$wallet) {
        // Inicializar monedero con 3 créditos de bienvenida (30 días, no acumulables)
        $db->prepare("INSERT INTO wallets (user_id, available_balance, consumed_balance, pending_balance, total_granted, expires_at, cumulative) VALUES (?, 3.0, 0.0, 0.0, 3.0, datetime('now', '+30 days'), 0)")->execute([$userId]);
        $wallet = ['available_balance' => 3.0, 'consumed_balance' => 0.0, 'pending_balance' => 0.0];

        // Registrar apunte en el libro mayor contable (Ledger) con 30 días de caducidad (no acumulable)
        $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, status, metadata) VALUES (?, 'welcome_bonus', 'promotion', 3.0, 3.0, 'active', ?)")
           ->execute([$userId, json_encode(['source' => 'welcome_promotion', 'validity_days' => 30, 'cumulative' => false, 'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))])]);
    }

    $plans = [
        ['id' => 'credit_single', 'name' => '1 Crédito', 'price' => 10.00, 'credits' => 1, 'per_credit' => '10,00 €', 'popular' => true, 'description' => 'Desbloqueo individual de un contacto profesional.'],
    ];

    $quickRecharges = [];

    echo json_encode([
        'ok' => true,
        'wallet' => [
            'available_balance' => (float)$wallet['available_balance'],
            'consumed_balance' => (float)$wallet['consumed_balance'],
            'pending_balance' => (float)$wallet['pending_balance'],
        ],
        'plans' => $plans,
        'quick_recharges' => $quickRecharges
    ]);
    exit;
}

// 2. HISTORIAL DEL LIBRO MAYOR CONTABLE (LEDGER)
if ($action === 'ledger') {
    $stmt = $db->prepare("SELECT id, movement_type, credit_source, amount, balance_after, status, metadata, created_at FROM ledger WHERE user_id = ? ORDER BY id DESC LIMIT 50");
    $stmt->execute([$userId]);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear movimientos para presentación amigable
    $formatted = array_map(function($m) {
        $meta = json_decode($m['metadata'] ?: '{}', true);
        return [
            'id' => $m['id'],
            'type' => $m['movement_type'],
            'type_label' => match($m['movement_type']) {
                'welcome_bonus' => '🎁 Bono de bienvenida (3 créditos / 30 días, no acumulables)',
                'purchase_stripe' => '💳 Recarga de créditos (Stripe)',
                'unlock_record' => '🔓 Desbloqueo de oportunidad',
                'unlock_share_reward', 'reward' => '⚡ Recompensa circular (+0.5 cr por desbloqueo)',
                'referral_milestone_a' => '⭐ Hito A: +3 cr por cartera XML del invitado',
                'referral_milestone_b' => '🏷️ Hito B: 50% DTO por compra de saldo de referido',
                'referral_reward' => '👥 Recompensa por referido 50/50',
                'refund' => '↩️ Reembolso de créditos',
                default => 'Transacción de monedero'
            },
            'amount' => (float)$m['amount'],
            'balance_after' => (float)$m['balance_after'],
            'status' => $m['status'],
            'metadata' => $meta,
            'date' => $m['created_at']
        ];
    }, $movements);

    echo json_encode([
        'ok' => true,
        'ledger' => $formatted
    ]);
    exit;
}

// 3. CONSUMIR CRÉDITO PARA DESBLOQUEO
if ($action === 'consume' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $idempotencyKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $idempotencyKey)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Idempotency-Key obligatorio y no válido.']);
        exit;
    }
    $recordId = (int)($_POST['record_id'] ?? 0);
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    if ($recordId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de registro no válido.']);
        exit;
    }
    if ($reservationId <= 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'code' => 'reservation_required', 'error' => 'El consumo debe proceder de una reserva activa aceptada.']);
        exit;
    }

    $db->beginTransaction();
    try {
        $reservationStmt = $db->prepare("SELECT cr.record_id, cr.operation_id, cr.credits, cr.status, cr.expires_at,
                                                o.status AS operation_status, o.contract_signed,
                                                o.captador_signed, o.colaborador_signed
                                         FROM credit_reservations cr
                                         JOIN operations o ON o.id = cr.operation_id
                                         WHERE cr.id = ? AND cr.user_id = ? LIMIT 1");
        $reservationStmt->execute([$reservationId, $userId]);
        $reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);
        if (!$reservation || (int)$reservation['record_id'] !== $recordId || $reservation['status'] !== 'reserved' || strtotime($reservation['expires_at']) <= time()) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['ok' => false, 'code' => 'reservation_invalid', 'error' => 'La reserva no está activa para esta oportunidad.']);
            exit;
        }
        if ($reservation['operation_status'] !== 'agreed'
            || (int)$reservation['contract_signed'] !== 1
            || (int)$reservation['captador_signed'] !== 1
            || (int)$reservation['colaborador_signed'] !== 1) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['ok' => false, 'code' => 'double_signature_required', 'error' => 'El consumo requiere aceptación y firma server-side de ambas partes.']);
            exit;
        }
        $stmt = $db->prepare("SELECT available_balance, reserved_balance, consumed_balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();

        if (!$wallet || (float)$wallet['reserved_balance'] < (float)$reservation['credits']) {
            $db->rollBack();
            http_response_code(402);
            echo json_encode(['ok' => false, 'error' => 'Saldo insuficiente. Recarga créditos para continuar.', 'code' => 'insufficient_funds']);
            exit;
        }

        $recordStmt = $db->prepare("SELECT id, user_id, deleted_at, privacy_scope FROM records WHERE id = ? LIMIT 1");
        $recordStmt->execute([$recordId]);
        $record = $recordStmt->fetch();
        if (!$record || $record['deleted_at'] !== null || (int)$record['user_id'] === $userId || $record['privacy_scope'] === 'private_user') {
            $db->rollBack();
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'El anuncio no está disponible para este desbloqueo.']);
            exit;
        }

        $alreadyUnlocked = $db->prepare("SELECT id FROM access_logs WHERE user_id = ? AND record_id = ? LIMIT 1");
        $alreadyUnlocked->execute([$userId, $recordId]);
        if ($alreadyUnlocked->fetchColumn()) {
            $db->rollBack();
            echo json_encode(['ok' => true, 'message' => 'Este contacto ya estaba desbloqueado.', 'record_id' => $recordId]);
            exit;
        }

        $newAvailable = (float)$wallet['available_balance'];
        $newConsumed = (float)$wallet['consumed_balance'] + 1.0;

        $db->prepare("UPDATE wallets SET available_balance = ?, reserved_balance = MAX(0, reserved_balance - 1), consumed_balance = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
           ->execute([$newAvailable, $newConsumed, $userId]);

        // Registrar acceso
        $db->prepare("INSERT OR IGNORE INTO access_logs (user_id, record_id) VALUES (?, ?)")->execute([$userId, $recordId]);
        $db->prepare("UPDATE credit_reservations SET status = 'consumed', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'reserved'")
           ->execute([$reservationId]);

        // Registrar en el Ledger
        $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, status, metadata) VALUES (?, 'unlock_record', 'wallet', -1.0, ?, 'completed', ?)")
           ->execute([$userId, $newAvailable, json_encode(['record_id' => $recordId, 'reason' => 'Desbloqueo de expediente completo y datos de contacto'])]);

        $db->commit();

        echo json_encode([
            'ok' => true,
            'message' => 'Oportunidad desbloqueada correctamente.',
            'available_balance' => $newAvailable,
            'record_id' => $recordId
        ]);
        exit;
    } catch (Throwable $e) {
        $db->rollBack();
        http_response_code(500);
        error_log('Credit consumption failed for authenticated user.');
        echo json_encode(['ok' => false, 'error' => 'No se pudo procesar el desbloqueo.']);
        exit;
    }
}

// Reservar un crédito para una operación; no da acceso ni consume saldo todavía.
if ($action === 'reserve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int)($_POST['record_id'] ?? 0);
    $reservationKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''));
    if ($recordId <= 0 || !preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $reservationKey)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Registro o Idempotency-Key no válido.']); exit;
    }
    $db->beginTransaction();
    try {
        $existing = $db->prepare("SELECT id, status, expires_at FROM credit_reservations WHERE reservation_key = ?");
        $existing->execute([$reservationKey]);
        if ($row = $existing->fetch(PDO::FETCH_ASSOC)) {
            $db->commit(); echo json_encode(['ok' => true, 'reservation_id' => (int)$row['id'], 'status' => $row['status'], 'expires_at' => $row['expires_at']]); exit;
        }
        $recordStmt = $db->prepare("SELECT id, user_id, deleted_at, privacy_scope FROM records WHERE id = ? LIMIT 1");
        $recordStmt->execute([$recordId]); $record = $recordStmt->fetch(PDO::FETCH_ASSOC);
        if (!$record || $record['deleted_at'] !== null || (int)$record['user_id'] === $userId || $record['privacy_scope'] === 'private_user') {
            $db->rollBack(); http_response_code(403); echo json_encode(['ok' => false, 'error' => 'La oportunidad no está disponible.']); exit;
        }
        $walletStmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
        $walletStmt->execute([$userId]); $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
        if (!$wallet || (float)$wallet['available_balance'] < 1) {
            $db->rollBack(); http_response_code(402); echo json_encode(['ok' => false, 'error' => 'Saldo insuficiente.', 'code' => 'insufficient_funds']); exit;
        }
        // La reserva dura 72 h para permitir aceptación y firma sin penalizar
        // al colaborador; el job/endpoint de estado la libera al caducar.
        $expiresAt = date('Y-m-d H:i:s', time() + (72 * 3600));
        $db->prepare("UPDATE wallets SET available_balance = available_balance - 1, reserved_balance = reserved_balance + 1, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND available_balance >= 1")
           ->execute([$userId]);
        $operationCode = 'CC-' . strtoupper(bin2hex(random_bytes(6)));
        $db->prepare("INSERT INTO operations (operation_code, captador_user_id, colaborador_user_id, record_id, status) VALUES (?, ?, ?, ?, 'requested')")
           ->execute([$operationCode, (int)$record['user_id'], $userId, $recordId]);
        $operationId = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO credit_reservations (reservation_key, user_id, record_id, operation_id, credits, expires_at) VALUES (?, ?, ?, ?, 1, ?)")
           ->execute([$reservationKey, $userId, $recordId, $operationId, $expiresAt]);
        $db->commit(); echo json_encode(['ok' => true, 'status' => 'reserved', 'reservation_id' => (int)$db->lastInsertId(), 'operation_id' => $operationId, 'operation_code' => $operationCode, 'expires_at' => $expiresAt]); exit;
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); http_response_code(500); echo json_encode(['ok' => false, 'error' => 'No se pudo reservar el crédito.']); exit; }
}

// Liberar una reserva activa cuando la operación se cancela o no continúa.
if ($action === 'release' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    if ($reservationId <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Reserva no válida.']); exit; }
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT user_id, credits, status FROM credit_reservations WHERE id = ? AND user_id = ?");
        $stmt->execute([$reservationId, $userId]); $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reservation) { $db->rollBack(); http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Reserva no encontrada.']); exit; }
        if ($reservation['status'] === 'reserved') {
            $db->prepare("UPDATE wallets SET reserved_balance = MAX(0, reserved_balance - ?), available_balance = available_balance + ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
               ->execute([(float)$reservation['credits'], (float)$reservation['credits'], $userId]);
            $db->prepare("UPDATE credit_reservations SET status = 'released', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$reservationId]);
        }
        $db->commit(); echo json_encode(['ok' => true, 'status' => 'released']); exit;
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); http_response_code(500); echo json_encode(['ok' => false, 'error' => 'No se pudo liberar la reserva.']); exit; }
}
