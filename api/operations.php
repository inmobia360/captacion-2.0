<?php
/**
 * Compra Captación - API de Operaciones (50/50 Colaboración y 100% Compraventa)
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/reputation.php';

header('Content-Type: application/json');

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$user = require_auth();

// 1. LISTAR OPERACIONES DEL USUARIO
if ($action === 'list') {
    $stmt = $db->prepare("SELECT o.*, r.title as property_title, r.price as property_price, r.property_type, r.province, r.municipality,
                                  u_owner.full_name as owner_name, u_owner.agency_name as owner_agency,
                                  CASE WHEN o.contract_signed = 1 THEN u_owner.phone ELSE '' END as owner_phone,
                                  CASE WHEN o.contract_signed = 1 THEN u_owner.email ELSE '' END as owner_email,
                                  u_collab.full_name as collaborator_name, u_collab.agency_name as collaborator_agency,
                                  CASE WHEN o.contract_signed = 1 THEN u_collab.phone ELSE '' END as collaborator_phone,
                                  CASE WHEN o.contract_signed = 1 THEN u_collab.email ELSE '' END as collaborator_email
                           FROM operations o
                           JOIN records r ON o.record_id = r.id
                           JOIN users u_owner ON o.captador_user_id = u_owner.id
                           JOIN users u_collab ON o.colaborador_user_id = u_collab.id
                           WHERE o.captador_user_id = ? OR o.colaborador_user_id = ?
                           ORDER BY o.created_at DESC");
    $stmt->execute([$user['id'], $user['id']]);
    $operations = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'operations' => $operations]);
    exit;
}

// Firma server-side de cada parte. El documento definitivo debe ser el PDF
// que el cliente hashée antes de llamar a este endpoint.
if ($action === 'sign_contract' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $opId = (int)($input['operation_id'] ?? 0);
    $documentHash = strtolower(trim((string)($input['document_hash'] ?? '')));
    if ($opId <= 0 || !preg_match('/^[a-f0-9]{64}$/', $documentHash)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Operación o huella documental no válida.']);
        exit;
    }
    $stmt = $db->prepare('SELECT * FROM operations WHERE id = ? AND (captador_user_id = ? OR colaborador_user_id = ?) LIMIT 1');
    $stmt->execute([$opId, $user['id'], $user['id']]);
    $op = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$op) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Operación no encontrada o no autorizada.']);
        exit;
    }
    if (!in_array($op['status'], ['agreed', 'in_progress'], true)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'La operación debe estar aceptada antes de firmar.']);
        exit;
    }
    if ($op['contract_hash'] !== '' && !hash_equals((string)$op['contract_hash'], $documentHash)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'La otra parte está firmando una versión diferente del documento.']);
        exit;
    }
    $column = ((int)$op['captador_user_id'] === (int)$user['id']) ? 'captador_signed' : 'colaborador_signed';
    $db->prepare("UPDATE operations SET {$column} = 1, contract_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
       ->execute([$documentHash, $opId]);
    $db->prepare("UPDATE operations SET contract_signed = 1, contract_signed_at = CURRENT_TIMESTAMP WHERE id = ? AND captador_signed = 1 AND colaborador_signed = 1")
       ->execute([$opId]);
    $fresh = $db->prepare('SELECT contract_signed, captador_signed, colaborador_signed, contract_signed_at FROM operations WHERE id = ?');
    $fresh->execute([$opId]);
    echo json_encode(['ok' => true, 'signature_recorded' => true, 'contract' => $fresh->fetch(PDO::FETCH_ASSOC)]);
    exit;
}

// La captadora acepta o rechaza la solicitud sin revelar contactos todavía.
if ($action === 'respond_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $opId = (int)($input['operation_id'] ?? 0);
    $decision = (string)($input['decision'] ?? '');
    if ($opId <= 0 || !in_array($decision, ['accept', 'reject'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Solicitud o decisión no válida.']);
        exit;
    }
    $stmt = $db->prepare('SELECT * FROM operations WHERE id = ? AND captador_user_id = ? LIMIT 1');
    $stmt->execute([$opId, $user['id']]);
    $op = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$op) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Solicitud no encontrada o no autorizada.']);
        exit;
    }
    if ($op['status'] !== 'requested') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'La solicitud ya fue respondida.']);
        exit;
    }
    if ($decision === 'accept') {
        $db->prepare("UPDATE operations SET status = 'agreed', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$opId]);
        echo json_encode(['ok' => true, 'status' => 'agreed', 'message' => 'Solicitud aceptada. Ambas partes deben firmar el acuerdo.']);
        exit;
    }
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE operations SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$opId]);
        $reservation = $db->prepare("SELECT id, user_id, credits FROM credit_reservations WHERE operation_id = ? AND status = 'reserved' LIMIT 1");
        $reservation->execute([$opId]);
        if ($row = $reservation->fetch(PDO::FETCH_ASSOC)) {
            $db->prepare("UPDATE wallets SET reserved_balance = MAX(0, reserved_balance - ?), available_balance = available_balance + ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
               ->execute([(float)$row['credits'], (float)$row['credits'], (int)$row['user_id']]);
            $db->prepare("UPDATE credit_reservations SET status = 'rejected', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([(int)$row['id']]);
        }
        $db->commit();
        echo json_encode(['ok' => true, 'status' => 'cancelled', 'message' => 'Solicitud rechazada y crédito liberado.']);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'No se pudo rechazar la solicitud.']);
        exit;
    }
}

// 2. ACTUALIZAR ESTADO DE LA OPERACIÓN
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $opId = (int)($input['operation_id'] ?? 0);
    $newStatus = in_array($input['status'] ?? '', ['requested', 'agreed', 'in_progress', 'closed', 'disputed', 'cancelled']) ? $input['status'] : '';

    if (!$newStatus) {
        echo json_encode(['ok' => false, 'error' => 'Estado no válido.']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM operations WHERE id = ? AND (captador_user_id = ? OR colaborador_user_id = ?)");
    $stmt->execute([$opId, $user['id'], $user['id']]);
    $op = $stmt->fetch();

    if (!$op) {
        echo json_encode(['ok' => false, 'error' => 'Operación no encontrada o no autorizada.']);
        exit;
    }

    $closedAt = ($newStatus === 'closed') ? date('Y-m-d H:i:s') : null;
    $db->prepare("UPDATE operations SET status = ?, updated_at = CURRENT_TIMESTAMP, closed_at = ? WHERE id = ?")
       ->execute([$newStatus, $closedAt, $opId]);
    try {
        reputation_calculate($db, (int)$op['captador_user_id']);
        reputation_calculate($db, (int)$op['colaborador_user_id']);
    } catch (Throwable $e) {
        error_log('Reputation recalculation deferred after operation update.');
    }

    // Notificar a la otra parte
    $recipientId = ((int)$op['captador_user_id'] === (int)$user['id']) ? $op['colaborador_user_id'] : $op['captador_user_id'];
    $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Actualización de operación', ?, 'info', '#/panel')")
       ->execute([$recipientId, "El estado de la operación ha cambiado a: $newStatus."]);

    echo json_encode(['ok' => true, 'message' => "Estado actualizado a $newStatus."]);
    exit;
}
