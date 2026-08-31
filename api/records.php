<?php
/**
 * Compra Captación - API de Inmuebles, Demandas, Búsqueda y Desbloqueos
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$currentUser = get_auth_user();
$currentUserId = $currentUser ? (int)$currentUser['id'] : 0;

// 1. LISTAR INMUEBLES O DEMANDAS PÚBLICAS CON ENMASCARAMIENTO ESTRICTO
if ($action === 'list' || $action === 'public_records') {
    $recordType = $_GET['record_type'] ?? ''; // property o need
    $province = trim($_GET['province'] ?? '');
    $municipality = trim($_GET['municipality'] ?? '');
    $propertyType = trim($_GET['property_type'] ?? '');
    $operationType = trim($_GET['operation_type'] ?? '');
    $isExclusive = isset($_GET['is_exclusive']) && $_GET['is_exclusive'] !== '' ? (int)$_GET['is_exclusive'] : null;
    $minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : 0;
    $maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : 0;
    $query = trim($_GET['q'] ?? '');
    $minReputation = isset($_GET['min_reputation']) && $_GET['min_reputation'] !== '' ? max(0, min(100, (int)$_GET['min_reputation'])) : null;
    $limit = min(500, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $where = ["deleted_at IS NULL", "status = 'active'", "privacy_scope IN ('global_public', 'network_private')"];
    $params = [];

    if ($recordType && in_array($recordType, ['property', 'need'])) {
        $where[] = "record_type = ?";
        $params[] = $recordType;
    }
    if ($province) {
        $where[] = "province LIKE ?";
        $params[] = "%$province%";
    }
    if ($municipality) {
        $where[] = "municipality LIKE ?";
        $params[] = "%$municipality%";
    }
    if ($propertyType) {
        $where[] = "property_type = ?";
        $params[] = $propertyType;
    }
    if ($operationType) {
        $where[] = "operation_type = ?";
        $params[] = $operationType;
    }
    if ($isExclusive !== null) {
        $where[] = "is_exclusive = ?";
        $params[] = $isExclusive;
    }
    if ($minPrice > 0) {
        $where[] = "price >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $where[] = "price <= ?";
        $params[] = $maxPrice;
    }
    if ($query) {
        $where[] = "(title LIKE ? OR zone LIKE ? OR description_public LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    // La reputación es complementaria: nunca debe impedir que se muestren anuncios.
    // Se aplica después en el cliente si el módulo de reputación está disponible.

    $whereClause = implode(' AND ', $where);
    $countStmt = $db->prepare("SELECT COUNT(*) FROM records WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT id, record_type, record_key, user_id, title, property_type, operation_type,
                   price, commission_percentage, commission_amount, province, municipality, zone,
                   address_public, bedrooms, bathrooms, surface_m2, is_exclusive, description_public,
                   images_json, features_json, status, created_at,
                   0 AS reputation_score,
                   'new_professional' AS reputation_category,
                   0 AS reputation_verified,
                   0 AS reputation_completed_operations,
                   0 AS reputation_publication_completeness
            FROM records
            WHERE $whereClause ORDER BY records.id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Obtener qué registros tiene ya desbloqueados el usuario actual
    $unlockedMap = [];
    if ($currentUserId > 0) {
        $unlockedStmt = $db->prepare("SELECT record_id FROM access_logs WHERE user_id = ?");
        $unlockedStmt->execute([$currentUserId]);
        $unlockedMap = array_fill_keys($unlockedStmt->fetchAll(PDO::FETCH_COLUMN), true);
    }

    $records = array_map(function($row) use ($currentUserId, $unlockedMap) {
        $row['images'] = json_decode($row['images_json'] ?: '[]', true) ?: [];
        $row['features'] = json_decode($row['features_json'] ?: '[]', true) ?: [];
        unset($row['images_json'], $row['features_json']);
        
        $context = mb_strtolower(($row['record_key'] ?? '') . ' ' . ($row['title'] ?? '') . ' ' . ($row['description_public'] ?? '') . ' ' . ($row['property_type'] ?? ''), 'UTF-8');
        if (preg_match('/(nave|industrial|almacen|almacén|poligono|polígono|talleres|fábrica|fabrica|cristaleria|cristalería)/i', $context)) {
            $row['property_type'] = 'Nave industrial';
            if (stripos($row['title'], 'apartment en') !== false || stripos($row['title'], 'flat en') !== false) {
                $row['title'] = preg_replace('/^(apartment|flat|piso|activo)\s+en\s+/i', 'Nave industrial en ', $row['title']);
            }
        } elseif (preg_match('/(terreno|solar|parcela|finca rústica|finca rustica|suelo|urbanizable)/i', $context)) {
            $row['property_type'] = 'Terreno / Parcela';
            if (stripos($row['title'], 'apartment en') !== false || stripos($row['title'], 'flat en') !== false) {
                $row['title'] = preg_replace('/^(apartment|flat|piso|activo)\s+en\s+/i', 'Terreno en ', $row['title']);
            }
        } elseif (strtolower($row['property_type'] ?? '') === 'apartment' || strtolower($row['property_type'] ?? '') === 'flat') {
            $row['property_type'] = 'Piso / Apartamento';
        }

        $isOwner = ($currentUserId > 0 && (int)$row['user_id'] === $currentUserId);
        $isUnlocked = isset($unlockedMap[$row['id']]) || $isOwner;
        $row['is_unlocked'] = $isUnlocked;
        $row['is_owner'] = $isOwner;
        
        // Enmascarar información de autor si no está desbloqueado
        if (!$isUnlocked) {
            unset($row['user_id']);
        }
        return $row;
    }, $rows);

    echo json_encode([
        'ok' => true,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'records' => $records
    ]);
    exit;
}

// 2. DETALLE DE UN REGISTRO ESPECÍFICO (CON DATOS PRIVADOS SI ESTÁ DESBLOQUEADO)
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $key = trim($_GET['key'] ?? '');

    $stmt = $db->prepare("SELECT r.*, u.full_name as author_name, u.agency_name as author_agency, u.phone as author_phone, u.email as author_email, u.cif_nif as author_cif,
                                 COALESCE(pr.score, 0) AS reputation_score,
                                 COALESCE(pr.category, 'new_professional') AS reputation_category,
                                 COALESCE(pr.verification_badge, 0) AS reputation_verified,
                                 COALESCE(pr.completed_operations, 0) AS reputation_completed_operations,
                                 COALESCE(pr.publication_completeness, 0) AS reputation_publication_completeness
                          FROM records r
                          JOIN users u ON r.user_id = u.id
                          LEFT JOIN professional_reputation pr ON pr.user_id = r.user_id
                          WHERE (r.id = ? OR r.record_key = ?) AND r.deleted_at IS NULL");
    $stmt->execute([$id, $key]);
    $record = $stmt->fetch();

    if (!$record) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Inmueble o demanda no encontrada.']);
        exit;
    }

    $isOwner = ($currentUserId > 0 && (int)$record['user_id'] === $currentUserId);
    $isUnlocked = false;

    if ($currentUserId > 0) {
        $checkStmt = $db->prepare("SELECT id FROM access_logs WHERE user_id = ? AND record_id = ?");
        $checkStmt->execute([$currentUserId, $record['id']]);
        $isUnlocked = (bool)$checkStmt->fetch() || $isOwner;
    }

    $record['images'] = json_decode($record['images_json'] ?: '[]', true) ?: [];
    $record['features'] = json_decode($record['features_json'] ?: '[]', true) ?: [];
    unset($record['images_json'], $record['features_json']);

    $record['is_unlocked'] = $isUnlocked;
    $record['is_owner'] = $isOwner;

    // Si NO está desbloqueado, ocultar información confidencial
    if (!$isUnlocked) {
        unset($record['address_private'], $record['description_private'], $record['author_phone'], $record['author_email'], $record['author_cif']);
    }

    echo json_encode(['ok' => true, 'record' => $record]);
    exit;
}

// 3. PUBLICAR NUEVA CAPTACIÓN O DEMANDA (AUTO-PUBLISH)
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $recordType = in_array($input['record_type'] ?? '', ['property', 'need']) ? $input['record_type'] : 'property';
    $title = trim($input['title'] ?? '');
    $propertyType = trim($input['property_type'] ?? 'piso');
    $operationType = in_array($input['operation_type'] ?? '', ['colaboracion_50_50', 'venta_100']) ? $input['operation_type'] : 'colaboracion_50_50';
    $price = (float)($input['price'] ?? 0);
    $commissionPercentage = (float)($input['commission_percentage'] ?? 50.0);
    $commissionAmount = (float)($input['commission_amount'] ?? ($price * 0.03 * ($commissionPercentage / 100)));
    $province = trim($input['province'] ?? '');
    $municipality = trim($input['municipality'] ?? '');
    $zone = trim($input['zone'] ?? '');
    $addressPublic = trim($input['address_public'] ?? "$zone, $municipality");
    $addressPrivate = trim($input['address_private'] ?? '');
    $bedrooms = (int)($input['bedrooms'] ?? 0);
    $bathrooms = (int)($input['bathrooms'] ?? 0);
    $surfaceM2 = (float)($input['surface_m2'] ?? 0);
    $isExclusive = !empty($input['is_exclusive']) ? 1 : 0;
    $descriptionPublic = trim($input['description_public'] ?? '');
    $descriptionPrivate = trim($input['description_private'] ?? '');
    $images = !empty($input['images']) && is_array($input['images']) ? $input['images'] : ['assets/media/property-defaults/piso-default.jpg'];
    $features = !empty($input['features']) && is_array($input['features']) ? $input['features'] : [];

    if (!$title || $price <= 0 || !$province || !$municipality) {
        echo json_encode(['ok' => false, 'error' => 'Por favor completa título, precio, provincia y municipio.']);
        exit;
    }

    $recordKey = 'rec_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 16);

    $stmt = $db->prepare("INSERT INTO records (
        record_type, record_key, user_id, user_email, title, property_type, operation_type,
        price, commission_percentage, commission_amount, province, municipality, zone,
        address_public, address_private, bedrooms, bathrooms, surface_m2, is_exclusive,
        description_public, description_private, images_json, features_json, status, privacy_scope
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, 'active', 'global_public'
    )");

    $stmt->execute([
        $recordType, $recordKey, $user['id'], $user['email'], $title, $propertyType, $operationType,
        $price, $commissionPercentage, $commissionAmount, $province, $municipality, $zone,
        $addressPublic, $addressPrivate, $bedrooms, $bathrooms, $surfaceM2, $isExclusive,
        $descriptionPublic, $descriptionPrivate, json_encode($images), json_encode($features)
    ]);
    $newId = (int)$db->lastInsertId();

    // Matching backend inmediato: persiste solo coincidencias útiles (>=60%) y evita duplicados.
    try {
        $opposite = $recordType === 'property' ? 'need' : 'property';
        $matchStmt = $db->prepare("SELECT id, user_id, province, municipality, property_type, price FROM records WHERE record_type = ? AND status = 'active' AND deleted_at IS NULL AND user_id != ?");
        $matchStmt->execute([$opposite, (int)$user['id']]);
        foreach ($matchStmt->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
            $score = 0;
            if (strcasecmp($province, (string)$candidate['province']) === 0) $score += 35;
            if ($municipality && strcasecmp($municipality, (string)$candidate['municipality']) === 0) $score += 20;
            if (strcasecmp($propertyType, (string)$candidate['property_type']) === 0) $score += 15;
            $candidatePrice = (float)$candidate['price']; $delta = ($price > 0 && $candidatePrice > 0) ? abs($price - $candidatePrice) / max($price, $candidatePrice) : 1;
            if ($delta <= .10) $score += 30; elseif ($delta <= .20) $score += 20; elseif ($delta <= .30) $score += 10;
            if ($score < 60) continue;
            $left = min($newId, (int)$candidate['id']); $right = max($newId, (int)$candidate['id']);
            $key = "match_{$left}_{$right}";
            $insert = $db->prepare("INSERT OR IGNORE INTO record_matches (record_id, matched_record_id, score, idempotency_key) VALUES (?, ?, ?, ?)");
            $insert->execute([$newId, (int)$candidate['id'], $score, $key]);
            if ($insert->rowCount() > 0) {
                $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'info', '#/coincidencias-ventas')")
                   ->execute([(int)$candidate['user_id'], 'Nueva coincidencia compatible', "Se ha detectado una coincidencia del {$score}% con una oportunidad compatible."]);
            }
        }
    } catch (Throwable $e) { error_log('Matching deferred after record creation.'); }

    // Notificación al usuario
    $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'success', ?)")
       ->execute([$user['id'], 'Captación publicada', 'Tu captación "' . $title . '" está activa y visible en el marketplace.', '#/inmueble?id=' . $newId]);

    echo json_encode([
        'ok' => true,
        'message' => '¡Captación publicada con éxito y disponible en el marketplace!',
        'id' => $newId,
        'record_key' => $recordKey
    ]);
    exit;
}

// 4. DESBLOQUEAR CONTACTO Y DATOS PRIVADOS (CONSUME 1 CRÉDITO)
if ($action === 'unlock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // El desbloqueo directo queda retirado: evita consumir saldo y abrir una
    // operación sin aceptación, contrato y trazabilidad de reserva.
    http_response_code(409);
    echo json_encode([
        'ok' => false,
        'code' => 'protected_collaboration_flow_required',
        'error' => 'Solicita primero una colaboración protegida. El acceso se habilita tras la aceptación y firma del acuerdo.'
    ]);
    exit;

    /* Legacy path retained below for migration reference; unreachable by design. */
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $recordId = (int)($input['record_id'] ?? 0);

    $stmt = $db->prepare("SELECT r.*, u.email as owner_email, u.full_name as owner_name FROM records r JOIN users u ON r.user_id = u.id WHERE r.id = ? AND r.deleted_at IS NULL");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode(['ok' => false, 'error' => 'Inmueble no encontrado.']);
        exit;
    }

    if ((int)$record['user_id'] === (int)$user['id']) {
        echo json_encode(['ok' => true, 'message' => 'Eres el propietario de esta captación.']);
        exit;
    }

    // Verificar si ya está desbloqueado
    $checkStmt = $db->prepare("SELECT id FROM access_logs WHERE user_id = ? AND record_id = ?");
    $checkStmt->execute([$user['id'], $recordId]);
    if ($checkStmt->fetch()) {
        echo json_encode(['ok' => true, 'message' => 'Ya dispones de acceso autorizado a este contacto.']);
        exit;
    }

    // Verificar saldo de créditos
    $stmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $wallet = $stmt->fetch();
    $balance = $wallet ? (float)$wallet['available_balance'] : 0.0;

    if ($balance < 1.0) {
        echo json_encode([
            'ok' => false,
            'need_credits' => true,
            'error' => 'Saldo insuficiente de créditos. Necesitas un crédito para iniciar una reserva protegida de colaboración.'
        ]);
        exit;
    }

    // Deducir 1 crédito de forma atómica
    $newBalance = $balance - 1.0;
    $db->prepare("UPDATE wallets SET available_balance = ?, consumed_balance = consumed_balance + 1.0, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
       ->execute([$newBalance, $user['id']]);

    // Registrar en Ledger
    $idempotencyKey = 'unlock_' . $user['id'] . '_' . $recordId . '_' . time();
    $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, related_entity_type, related_entity_id, metadata) VALUES (?, 'consumption', 'unlock_contact', -1.0, ?, 'record', ?, ?)")
       ->execute([$user['id'], $newBalance, (string)$recordId, json_encode(['title' => $record['title'], 'record_key' => $record['record_key'], 'idempotency_key' => $idempotencyKey])]);

    // Registrar en Access Logs
    $db->prepare("INSERT INTO access_logs (user_id, record_id, consumed_credit, referral_bonus_paid) VALUES (?, ?, 1.0, 0.0)")
       ->execute([$user['id'], $recordId]);

    // Crear Operación de Colaboración inicial
    $opKey = 'op_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 16);
    $db->prepare("INSERT INTO operations (operation_code, captador_user_id, colaborador_user_id, record_id, status) VALUES (?, ?, ?, ?, 'in_progress')")
       ->execute([$opKey, $record['user_id'], $user['id'], $recordId]);

    // Modelo Circular ("Tokenomics Inmobiliario"): +0.5 créditos automáticos al captador/propietario
    $ownerId = (int)$record['user_id'];
    $db->prepare("UPDATE wallets SET available_balance = available_balance + 0.5, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
       ->execute([$ownerId]);
    
    $ownerWalletStmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
    $ownerWalletStmt->execute([$ownerId]);
    $ownerWallet = $ownerWalletStmt->fetch();
    $ownerNewBalance = $ownerWallet ? (float)$ownerWallet['available_balance'] : 0.5;

    $ownerLedgerKey = 'reward_unlock_' . $ownerId . '_' . $recordId . '_' . time();
    $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, related_entity_type, related_entity_id, metadata) VALUES (?, 'reward', 'unlock_share_reward', 0.5, ?, 'record', ?, ?)")
       ->execute([$ownerId, $ownerNewBalance, (string)$recordId, json_encode(['description' => 'Recompensa circular (+0.5 créditos) por desbloqueo de tu captación', 'unlocked_by' => $user['full_name'], 'title' => $record['title'], 'idempotency_key' => $ownerLedgerKey])]);

    // Notificar al propietario con el beneficio de +0.5 créditos
    $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'success', ?)")
       ->execute([$ownerId, '¡+0.5 Créditos ganados por desbloqueo!', $user['full_name'] . ' (' . ($user['agency_name'] ?: 'Agente') . ') ha desbloqueado tu captación "' . $record['title'] . '". Has recibido +0.5 créditos en tu saldo.', '#/area-privada/creditos']);

    echo json_encode([
        'ok' => true,
        'message' => '¡Contacto y datos privados desbloqueados con éxito! Se ha descontado 1 crédito.',
        'remaining_credits' => $newBalance
    ]);
    exit;
}

// 5. MIS CAPTACIONES Y DEMANDAS (PANEL SAAS)
if ($action === 'my_records') {
    $user = require_auth();
    // Finalizar automáticamente la conservación de 72 horas. Esto también
    // cubre el caso en que el usuario vuelva al panel después del vencimiento.
    $expireStmt = $db->prepare("UPDATE records SET deleted_at = CURRENT_TIMESTAMP, status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND deleted_at IS NULL AND deletion_deadline_at IS NOT NULL AND deletion_deadline_at <= CURRENT_TIMESTAMP");
    $expireStmt->execute([(int)$user['id']]);
    $stmt = $db->prepare("SELECT * FROM records WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();

    $records = array_map(function($row) {
        $row['images'] = json_decode($row['images_json'] ?: '[]', true) ?: [];
        $row['features'] = json_decode($row['features_json'] ?: '[]', true) ?: [];
        return $row;
    }, $rows);

    echo json_encode(['ok' => true, 'records' => $records]);
    exit;
}

// 6. TOGGLE GUARDAR FAVORITO
if ($action === 'toggle_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $recordId = (int)($input['record_id'] ?? 0);

    $checkStmt = $db->prepare("SELECT id FROM saved_records WHERE user_id = ? AND record_id = ?");
    $checkStmt->execute([$user['id'], $recordId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $db->prepare("DELETE FROM saved_records WHERE id = ?")->execute([$existing['id']]);
        echo json_encode(['ok' => true, 'saved' => false, 'message' => 'Eliminado de guardados.']);
    } else {
        $db->prepare("INSERT INTO saved_records (user_id, record_id) VALUES (?, ?)")->execute([$user['id'], $recordId]);
        echo json_encode(['ok' => true, 'saved' => true, 'message' => 'Guardado en tu cartera de favoritos.']);
    }
    exit;
}

// 7. EDITAR / ACTUALIZAR CAPTACIÓN O DEMANDA INDIVIDUAL
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $recordId = (int)($input['id'] ?? $input['record_id'] ?? 0);

    if ($recordId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de registro no válido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtCheck = $db->prepare("SELECT * FROM records WHERE id = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$recordId]);
    $record = $stmtCheck->fetch();

    if (!$record) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Registro no encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$record['user_id'] !== (int)$user['id'] && ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No tienes permisos para editar este registro.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $title = trim((string)($input['title'] ?? $record['title']));
    $propertyType = trim((string)($input['property_type'] ?? $record['property_type']));
    $price = isset($input['price']) ? (float)$input['price'] : (float)$record['price'];
    $commissionPercentage = isset($input['commission_percentage']) ? (float)$input['commission_percentage'] : (float)$record['commission_percentage'];
    $commissionAmount = round($price * 0.03 * ($commissionPercentage / 100), 2);
    $province = trim((string)($input['province'] ?? $record['province']));
    $municipality = trim((string)($input['municipality'] ?? $record['municipality']));
    $zone = trim((string)($input['zone'] ?? $record['zone']));
    $bedrooms = isset($input['bedrooms']) ? (int)$input['bedrooms'] : (int)$record['bedrooms'];
    $bathrooms = isset($input['bathrooms']) ? (int)$input['bathrooms'] : (int)$record['bathrooms'];
    $surfaceM2 = isset($input['surface_m2']) ? (float)$input['surface_m2'] : (float)$record['surface_m2'];
    $descriptionPublic = trim((string)($input['description_public'] ?? $record['description_public']));
    $status = in_array($input['status'] ?? '', ['active', 'paused']) ? $input['status'] : $record['status'];

    $stmtUp = $db->prepare("UPDATE records SET 
        title = ?, property_type = ?, price = ?, commission_percentage = ?, commission_amount = ?,
        province = ?, municipality = ?, zone = ?, bedrooms = ?, bathrooms = ?, surface_m2 = ?,
        description_public = ?, status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    $stmtUp->execute([
        $title, $propertyType, $price, $commissionPercentage, $commissionAmount,
        $province, $municipality, $zone, $bedrooms, $bathrooms, $surfaceM2,
        $descriptionPublic, $status, $recordId
    ]);
    require_once __DIR__ . '/reputation.php';
    try { reputation_calculate($db, (int)$user['id']); } catch (Throwable $e) { error_log('Reputation recalculation deferred after record update.'); }

    echo json_encode([
        'ok' => true,
        'message' => 'Registro actualizado correctamente.',
        'record' => [
            'id' => $recordId,
            'title' => $title,
            'property_type' => $propertyType,
            'price' => $price,
            'province' => $province,
            'municipality' => $municipality,
            'status' => $status
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 8. PAUSAR / REACTIVAR REGISTRO INDIVIDUAL
if ($action === 'toggle_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $recordId = (int)($input['id'] ?? $input['record_id'] ?? 0);
    $targetStatus = in_array($input['status'] ?? '', ['active', 'paused']) ? $input['status'] : 'paused';

    if ($recordId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID no válido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtCheck = $db->prepare("SELECT * FROM records WHERE id = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$recordId]);
    $record = $stmtCheck->fetch();

    if (!$record) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Registro no encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$record['user_id'] !== (int)$user['id'] && ($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No tienes permisos para modificar este registro.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->prepare("UPDATE records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
       ->execute([$targetStatus, $recordId]);

    $label = $targetStatus === 'paused' ? 'pausado' : 'activado';
    echo json_encode([
        'ok' => true,
        'status' => $targetStatus,
        'message' => "El registro ha sido $label correctamente."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 9. ELIMINAR REGISTRO INDIVIDUAL
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $rawId = trim((string)($input['id'] ?? $input['record_id'] ?? ''));

    if (empty($rawId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID no válido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $isAdmin = (($user['role'] ?? '') === 'admin');
    $ownerClause = $isAdmin ? '' : ' AND user_id = ?';
    $stmtCheck = $db->prepare("SELECT * FROM records WHERE (id = ? OR record_key = ? OR record_key LIKE ? OR title = ?) AND deleted_at IS NULL" . $ownerClause);
    $stmtCheck->execute($isAdmin ? [$rawId, $rawId, "%$rawId%", $rawId] : [$rawId, $rawId, "%$rawId%", $rawId, (int)$user['id']]);
    $record = $stmtCheck->fetch();

    if (!$record) {
        echo json_encode(['ok' => true, 'message' => 'Registro retirado con éxito.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $recId = (int)$record['id'];

    // Comprobar si tiene operaciones en curso
    $stmtOps = $db->prepare("SELECT COUNT(*) FROM operations WHERE record_id = ? AND status IN ('active', 'pending', 'in_progress', 'closing')");
    $stmtOps->execute([$recId]);
    $hasOperations = (int)$stmtOps->fetchColumn() > 0;

    if ($hasOperations) {
        $deadline = date('Y-m-d H:i:s', time() + 72 * 3600);
        $db->prepare("UPDATE records SET privacy_scope = 'private_user', data_origin = 'preserved_in_operation', deletion_requested_at = CURRENT_TIMESTAMP, deletion_deadline_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$deadline, $recId]);
        echo json_encode([
            'ok' => true,
            'preserved' => true,
            'deletion_deadline_at' => date('c', time() + 72 * 3600),
            'message' => 'El registro ha sido retirado del Marketplace público y se mantendrá visible en tu panel durante 72 horas por el trámite activo.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $deleteStmt = $db->prepare("UPDATE records SET deleted_at = CURRENT_TIMESTAMP, status = 'deleted' WHERE id = ?" . ($isAdmin ? '' : ' AND user_id = ?'));
    $deleteStmt->execute($isAdmin ? [$recId] : [$recId, (int)$user['id']]);

    echo json_encode([
        'ok' => true,
        'message' => 'Registro eliminado permanentemente de la plataforma.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 10. ELIMINACIÓN AGRUPADA / MASIVA (BULK DELETE)
if ($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $rawIds = !empty($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

    if (empty($rawIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se seleccionaron registros.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $deletedCount = 0;
    $preservedCount = 0;

    foreach ($rawIds as $rawId) {
        $idStr = trim((string)$rawId);
        if (empty($idStr)) continue;
        
        $isAdmin = (($user['role'] ?? '') === 'admin');
        $ownerClause = $isAdmin ? '' : ' AND user_id = ?';
        $stmtCheck = $db->prepare("SELECT * FROM records WHERE (id = ? OR record_key = ? OR record_key LIKE ? OR title = ?) AND deleted_at IS NULL" . $ownerClause);
        $stmtCheck->execute($isAdmin ? [$idStr, $idStr, "%$idStr%", $idStr] : [$idStr, $idStr, "%$idStr%", $idStr, (int)$user['id']]);
        $record = $stmtCheck->fetch();
        if (!$record) {
            $deletedCount++;
            continue;
        }

        $recId = (int)$record['id'];

        // Comprobar operaciones en curso
        $stmtOps = $db->prepare("SELECT COUNT(*) FROM operations WHERE record_id = ? AND status IN ('active', 'pending', 'in_progress', 'closing')");
        $stmtOps->execute([$recId]);
        $hasOperations = (int)$stmtOps->fetchColumn() > 0;

        if ($hasOperations) {
            $deadline = date('Y-m-d H:i:s', time() + 72 * 3600);
            $db->prepare("UPDATE records SET privacy_scope = 'private_user', data_origin = 'preserved_in_operation', deletion_requested_at = CURRENT_TIMESTAMP, deletion_deadline_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
               ->execute([$deadline, $recId]);
            $preservedCount++;
        } else {
            $deleteStmt = $db->prepare("UPDATE records SET deleted_at = CURRENT_TIMESTAMP, status = 'deleted' WHERE id = ?" . ($isAdmin ? '' : ' AND user_id = ?'));
            $deleteStmt->execute($isAdmin ? [$recId] : [$recId, (int)$user['id']]);
            $deletedCount++;
        }
    }

    echo json_encode([
        'ok' => true,
        'deleted_count' => $deletedCount,
        'preserved_count' => $preservedCount,
        'message' => "Se han eliminado $deletedCount registros seleccionados." . ($preservedCount > 0 ? " ($preservedCount preservados por operaciones activas)." : '')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 11. CAMBIO DE ESTADO AGRUPADO (BULK STATUS: PAUSE / ACTIVATE)
if ($action === 'bulk_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $rawIds = !empty($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];
    $targetStatus = in_array($input['status'] ?? '', ['active', 'paused']) ? $input['status'] : 'paused';

    if (empty($rawIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No se seleccionaron registros.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $updatedCount = 0;
    foreach ($rawIds as $rawId) {
        $idStr = trim((string)$rawId);
        if (empty($idStr)) continue;
        $stmtCheck = $db->prepare("SELECT * FROM records WHERE (id = ? OR record_key = ? OR record_key LIKE ? OR title = ?) AND deleted_at IS NULL");
        $stmtCheck->execute([$idStr, $idStr, "%$idStr%", $idStr]);
        $record = $stmtCheck->fetch();
        if (!$record) continue;

        $recId = (int)$record['id'];
        $db->prepare("UPDATE records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$targetStatus, $recId]);
        $updatedCount++;
    }

    $label = $targetStatus === 'paused' ? 'pausados' : 'reactivados';
    echo json_encode([
        'ok' => true,
        'updated_count' => $updatedCount,
        'status' => $targetStatus,
        'message' => "$updatedCount registros han sido $label correctamente."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
