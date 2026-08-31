<?php declare(strict_types=1);
/**
 * Compra Captación CRM - Telemetry, WAF & Error Logs
 */
require_once dirname(__DIR__) . '/database.php';

header('Content-Type: application/json; charset=UTF-8');

$db = CaptacionDB::get();

try {
    $logs = $db->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100")->fetchAll();
    echo json_encode(['ok' => true, 'logs' => $logs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
