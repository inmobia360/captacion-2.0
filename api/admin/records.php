<?php
/**
 * Compra Captación CRM - Real Estate Records & Portfolio Moderation
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($action === 'list') {
    $type = $_GET['type'] ?? 'all'; // all, property, need
    $status = $_GET['status'] ?? 'all';
    $search = trim($_GET['q'] ?? '');
    
    $where = ["r.deleted_at IS NULL"];
    $params = [];
    
    if ($type !== 'all' && in_array($type, ['property', 'need'])) {
        $where[] = "r.record_type = ?";
        $params[] = $type;
    }
    if ($status !== 'all') {
        $where[] = "r.status = ?";
        $params[] = $status;
    }
    if ($search !== '') {
        $where[] = "(r.title LIKE ? OR r.province LIKE ? OR r.municipality LIKE ? OR r.property_type LIKE ? OR u.agency_name LIKE ? OR u.email LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    
    $whereSql = implode(" AND ", $where);
    $sql = "SELECT r.*, u.full_name as author_name, u.email as author_email, u.agency_name as author_agency, u.role as author_role
            FROM records r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE $whereSql
            ORDER BY r.id DESC
            LIMIT 150";
            
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    echo json_encode([
        'ok' => true,
        'count' => count($records),
        'records' => $records
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'set_status') {
    $recordId = (int)($input['record_id'] ?? 0);
    $status = (string)($input['status'] ?? 'active');
    
    if (!$recordId || !in_array($status, ['active', 'paused', 'deleted', 'closed'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Parámetros no válidos.']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$status, $recordId]);
    
    echo json_encode([
        'ok' => true,
        'message' => "Estado del registro #$recordId actualizado a '$status'."
    ]);
    exit;
}

if ($action === 'delete') {
    $recordId = (int)($input['record_id'] ?? 0);
    if (!$recordId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de registro requerido.']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE records SET status = 'deleted', deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$recordId]);
    
    echo json_encode([
        'ok' => true,
        'message' => "Registro #$recordId eliminado correctamente."
    ]);
    exit;
}

if ($action === 'bulk_delete') {
    $recordIds = $input['record_ids'] ?? [];
    if (!is_array($recordIds) || empty($recordIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Lista de IDs de inmuebles requerida.']);
        exit;
    }
    
    $cleanIds = array_filter(array_map('intval', $recordIds), fn($id) => $id > 0);
    if (empty($cleanIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Ningún ID válido proporcionado.']);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $stmt = $db->prepare("UPDATE records SET status = 'deleted', deleted_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
    $stmt->execute($cleanIds);
    $affected = $stmt->rowCount();
    
    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'records_bulk_deleted', ?, ?, ?)")
       ->execute([$_SESSION['staff_user_id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['count' => $affected, 'ids' => $cleanIds])]);
    
    echo json_encode([
        'ok' => true,
        'message' => "$affected inmueble(s) eliminados correctamente.",
        'affected' => $affected
    ]);
    exit;
}

if ($action === 'bulk_status') {
    $recordIds = $input['record_ids'] ?? [];
    $newStatus = (string)($input['status'] ?? 'active');
    
    if (!is_array($recordIds) || empty($recordIds) || !in_array($newStatus, ['active', 'paused', 'deleted'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Parámetros de cambio masivo no válidos.']);
        exit;
    }
    
    $cleanIds = array_filter(array_map('intval', $recordIds), fn($id) => $id > 0);
    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $params = array_merge([$newStatus], $cleanIds);
    
    $stmt = $db->prepare("UPDATE records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
    $stmt->execute($params);
    $affected = $stmt->rowCount();
    
    echo json_encode([
        'ok' => true,
        'message' => "$affected inmueble(s) actualizados a estado '$newStatus'.",
        'affected' => $affected
    ]);
    exit;
}


http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Acción no reconocida.']);
