<?php
/**
 * Compra Captación CRM - Tickets & Customer Support
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/database.php';

header('Content-Type: application/json; charset=UTF-8');

$db = CaptacionDB::get();

// Crear tablas de soporte si no existen
$db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_code TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    user_email TEXT NOT NULL,
    user_name TEXT NOT NULL DEFAULT '',
    agency_name TEXT NOT NULL DEFAULT '',
    subject TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'general',
    priority TEXT NOT NULL DEFAULT 'medium',
    status TEXT NOT NULL DEFAULT 'open',
    resolution_notes TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    sender_role TEXT NOT NULL DEFAULT 'admin',
    sender_name TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    attachments_json TEXT DEFAULT '[]',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($action === 'list') {
    $status = $_GET['status'] ?? 'all';
    $sql = "SELECT * FROM support_tickets";
    $params = [];
    if ($status !== 'all') {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY id DESC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'tickets' => $tickets]);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ?");
    $stmt->execute([$id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Ticket no encontrado.']);
        exit;
    }

    $msgStmt = $db->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC");
    $msgStmt->execute([$id]);
    $messages = $msgStmt->fetchAll();

    echo json_encode(['ok' => true, 'ticket' => $ticket, 'messages' => $messages]);
    exit;
}

if ($action === 'reply') {
    $ticketId = (int)($input['ticket_id'] ?? 0);
    $message = trim((string)($input['message'] ?? ''));
    $newStatus = (string)($input['status'] ?? 'in_progress');

    if (!$ticketId || !$message) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Mensaje y Ticket ID obligatorios.']);
        exit;
    }

    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, sender_name, message) VALUES (?, 'admin', 'Soporte Central', ?)");
    $stmt->execute([$ticketId, $message]);

    $upStmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $upStmt->execute([$newStatus, $ticketId]);
    $db->commit();

    echo json_encode(['ok' => true, 'message' => 'Respuesta registrada con éxito.']);
    exit;
}

if ($action === 'create') {
    $userEmail = trim((string)($input['email'] ?? ''));
    $userName = trim((string)($input['name'] ?? 'Usuario'));
    $subject = trim((string)($input['subject'] ?? ''));
    $message = trim((string)($input['message'] ?? ''));
    $category = (string)($input['category'] ?? 'soporte');
    $priority = (string)($input['priority'] ?? 'medium');

    if (!$userEmail || !$subject || !$message) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Faltan campos obligatorios.']);
        exit;
    }

    $ticketCode = 'TCK-' . strtoupper(substr(md5(uniqid()), 0, 8));

    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO support_tickets (ticket_code, user_id, user_email, user_name, subject, category, priority, status) VALUES (?, 1, ?, ?, ?, ?, ?, 'open')");
    $stmt->execute([$ticketCode, $userEmail, $userName, $subject, $category, $priority]);
    $ticketId = (int)$db->lastInsertId();

    $msgStmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, sender_name, message) VALUES (?, 'user', ?, ?)");
    $msgStmt->execute([$ticketId, $userName, $message]);
    $db->commit();

    echo json_encode(['ok' => true, 'ticket_code' => $ticketCode, 'ticket_id' => $ticketId]);
    exit;
}

if ($action === 'bulk_delete') {
    $ticketIds = $input['ticket_ids'] ?? [];
    if (!is_array($ticketIds) || empty($ticketIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Lista de IDs de tickets requerida.']);
        exit;
    }

    $cleanIds = array_filter(array_map('intval', $ticketIds), fn($id) => $id > 0);
    if (empty($cleanIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Ningún ID válido proporcionado.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $stmt = $db->prepare("DELETE FROM support_tickets WHERE id IN ($placeholders)");
    $stmt->execute($cleanIds);
    $affected = $stmt->rowCount();

    $db->prepare("DELETE FROM ticket_messages WHERE ticket_id IN ($placeholders)")->execute($cleanIds);

    echo json_encode([
        'ok' => true,
        'message' => "$affected ticket(s) eliminados correctamente.",
        'affected' => $affected
    ]);
    exit;
}

if ($action === 'bulk_status') {
    $ticketIds = $input['ticket_ids'] ?? [];
    $newStatus = (string)($input['status'] ?? 'resolved');

    if (!is_array($ticketIds) || empty($ticketIds) || !in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Parámetros no válidos.']);
        exit;
    }

    $cleanIds = array_filter(array_map('intval', $ticketIds), fn($id) => $id > 0);
    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $params = array_merge([$newStatus], $cleanIds);

    $stmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
    $stmt->execute($params);
    $affected = $stmt->rowCount();

    echo json_encode([
        'ok' => true,
        'message' => "$affected ticket(s) actualizados a estado '$newStatus'.",
        'affected' => $affected
    ]);
    exit;
}

