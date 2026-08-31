<?php
/**
 * Compra Captación - API de Panel de Administración y Moderación
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';
$admin = require_admin();

// 1. MÉTRICAS Y ESTADÍSTICAS GLOBALES
if ($action === 'stats') {
    $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalProperties = (int)$db->query("SELECT COUNT(*) FROM records WHERE record_type = 'property' AND deleted_at IS NULL")->fetchColumn();
    $totalNeeds = (int)$db->query("SELECT COUNT(*) FROM records WHERE record_type = 'need' AND deleted_at IS NULL")->fetchColumn();
    $totalOperations = (int)$db->query("SELECT COUNT(*) FROM operations")->fetchColumn();
    $totalUnlocks = (int)$db->query("SELECT COUNT(*) FROM access_logs")->fetchColumn();
    $totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'succeeded'")->fetchColumn();

    echo json_encode([
        'ok' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'total_properties' => $totalProperties,
            'total_needs' => $totalNeeds,
            'total_operations' => $totalOperations,
            'total_unlocks' => $totalUnlocks,
            'total_revenue' => $totalRevenue,
        ]
    ]);
    exit;
}

// 2. GESTIÓN DE USUARIOS
if ($action === 'users') {
    $stmt = $db->query("SELECT id, email, full_name, agency_name, cif_nif, phone, role, verification_status, email_verified, created_at FROM users ORDER BY created_at DESC LIMIT 100");
    $users = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'users' => $users]);
    exit;
}

if ($action === 'verify_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $targetUserId = (int)($input['user_id'] ?? 0);
    $status = in_array($input['status'] ?? '', ['approved', 'rejected', 'suspended', 'pending']) ? $input['status'] : 'approved';

    $db->prepare("UPDATE users SET verification_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
       ->execute([$status, $targetUserId]);

    echo json_encode(['ok' => true, 'message' => "Estado de verificación actualizado a: $status"]);
    exit;
}

// 3. MODERACIÓN DE CAPTACIONES
if ($action === 'records') {
    $stmt = $db->query("SELECT r.*, u.full_name as author_name, u.email as author_email, u.agency_name as author_agency FROM records r JOIN users u ON r.user_id = u.id WHERE r.deleted_at IS NULL ORDER BY r.created_at DESC LIMIT 100");
    $records = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'records' => $records]);
    exit;
}

if ($action === 'moderate_record' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $recordId = (int)($input['record_id'] ?? 0);
    $status = in_array($input['status'] ?? '', ['active', 'paused', 'pending_review', 'closed']) ? $input['status'] : 'active';

    $db->prepare("UPDATE records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
       ->execute([$status, $recordId]);

    echo json_encode(['ok' => true, 'message' => "Estado de publicación actualizado a: $status"]);
    exit;
}

// 4. AUDITORÍA Y PAGOS
if ($action === 'audit_logs') {
    $stmt = $db->query("SELECT a.*, u.email as user_email FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'logs' => $logs]);
    exit;
}

if ($action === 'payments') {
    $stmt = $db->query("SELECT p.*, u.email as user_email, u.full_name FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 100");
    $payments = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'payments' => $payments]);
    exit;
}
