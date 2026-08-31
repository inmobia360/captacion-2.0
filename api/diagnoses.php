<?php
/** Borradores aislados de diagnóstico profesional. No publica ni consume créditos. */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = CaptacionDB::get();
    $user = require_auth();
    $userId = (int)$user['id'];
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    $allowedStatuses = ['draft', 'in_review', 'needs_information', 'needs_expert', 'ready_for_publication', 'archived'];

    if ($action === 'list') {
        $stmt = $db->prepare("SELECT id, status, record_type, completeness_score, version, created_at, updated_at FROM captation_diagnoses WHERE user_id = ? AND deleted_at IS NULL ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        echo json_encode(['ok' => true, 'diagnoses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($action === 'get') {
        $stmt = $db->prepare("SELECT id, status, record_type, payload_json, completeness_score, version, created_at, updated_at FROM captation_diagnoses WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Diagnóstico no encontrado.']); exit; }
        $row['payload'] = json_decode($row['payload_json'], true) ?: [];
        unset($row['payload_json']);
        echo json_encode(['ok' => true, 'diagnosis' => $row]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false, 'error' => 'Método no permitido.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if ($action === 'create') {
        $recordType = in_array($input['record_type'] ?? 'property', ['property', 'need'], true) ? $input['record_type'] : 'property';
        $payload = is_array($input['payload'] ?? null) ? $input['payload'] : [];
        $stmt = $db->prepare("INSERT INTO captation_diagnoses (user_id, status, record_type, payload_json, completeness_score) VALUES (?, 'draft', ?, ?, 0)");
        $stmt->execute([$userId, $recordType, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        echo json_encode(['ok' => true, 'id' => (int)$db->lastInsertId(), 'status' => 'draft', 'credits_consumed' => 0]);
        exit;
    }

    if ($action === 'update') {
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Diagnóstico no válido.']); exit; }
        $payload = is_array($input['payload'] ?? null) ? $input['payload'] : [];
        $status = in_array($input['status'] ?? 'draft', $allowedStatuses, true) ? $input['status'] : 'draft';
        $version = max(1, (int)($input['version'] ?? 1));
        $stmt = $db->prepare("UPDATE captation_diagnoses SET status = ?, payload_json = ?, version = version + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND version = ? AND deleted_at IS NULL");
        $stmt->execute([$status, json_encode($payload, JSON_UNESCAPED_UNICODE), $id, $userId, $version]);
        if ($stmt->rowCount() !== 1) { http_response_code(409); echo json_encode(['ok' => false, 'error' => 'El diagnóstico cambió o no existe. Recarga e inténtalo de nuevo.']); exit; }
        echo json_encode(['ok' => true, 'id' => $id, 'status' => $status, 'credits_consumed' => 0]);
        exit;
    }

    if ($action === 'archive') {
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Diagnóstico no válido.']); exit; }
        $stmt = $db->prepare("UPDATE captation_diagnoses SET status = 'archived', deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id, $userId]);
        if ($stmt->rowCount() !== 1) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Diagnóstico no encontrado.']); exit; }
        echo json_encode(['ok' => true, 'id' => $id, 'status' => 'archived', 'credits_consumed' => 0]);
        exit;
    }

    http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo procesar el diagnóstico.']);
}
