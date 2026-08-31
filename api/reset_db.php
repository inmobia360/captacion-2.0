<?php declare(strict_types=1);
/**
 * Compra Captación - Limpieza Total de Base de Datos (Día Uno)
 */
require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $db = CaptacionDB::get();
    
    $tables = [
        'records',
        'saved_records',
        'access_logs',
        'operations',
        'payments',
        'notifications',
        'reports',
        'audit_logs',
        'legal_acceptances',
        'ledger',
        'wallets',
        'users'
    ];

    $db->beginTransaction();
    foreach ($tables as $table) {
        try {
            $db->exec("DELETE FROM $table");
        } catch (Exception $e) {}
    }
    
    // Reset SQLite auto-increments
    try {
        $db->exec("DELETE FROM sqlite_sequence");
    } catch (Exception $e) {}

    $db->commit();

    // Recrear únicamente la cuenta del Administrador Principal
    CaptacionDB::seedInitialData();

    echo json_encode([
        'ok' => true,
        'message' => 'Base de datos reiniciada a cero (Día Uno). Se han eliminado todos los usuarios de prueba, ofertas inmobiliarias y demandas.',
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
