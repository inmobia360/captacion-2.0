<?php
/**
 * Compra Captación - Script de Inicialización y Re-seed de Base de Datos
 * Protegido: Solo accesible por Administrador autenticado o línea de comandos (CLI)
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

// Si se invoca vía Web/HTTP, exigir autenticación y rol de Administrador
if (php_sapi_name() !== 'cli') {
    $admin = require_admin();
}

header('Content-Type: application/json; charset=utf-8');

try {
    $db = CaptacionDB::get();
    $db->exec("DELETE FROM records");
    $db->exec("DELETE FROM users WHERE email != 'admin@compracaptacion.com'");
    CaptacionDB::seedInitialData();
    $count = $db->query("SELECT COUNT(*) FROM records")->fetchColumn();
    $records = $db->query("SELECT id, title, price, municipality, province, record_type FROM records")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'message' => 'Base de datos reinicializada con éxito.',
        'active_records_count' => (int)$count,
        'records' => $records
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error al inicializar base de datos.'
    ], JSON_UNESCAPED_UNICODE);
}
