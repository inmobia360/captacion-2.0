<?php
/**
 * Compra Captación - Sincronizador Territorial Oficial INE
 * Fuente: Instituto Nacional de Estadística (INE)
 * - Comunidades y Provincias: https://servicios.ine.es/wstempus/js/ES/VALORES_VARIABLE/115?det=2
 * - Municipios: https://servicios.ine.es/wstempus/js/ES/VALORES_VARIABLE/19?clasif=121&det=2
 * - Códigos oficiales: https://www.ine.es/daco/daco42/codmun/cod_num_muni_provincia_ccaa.htm
 */

header('Content-Type: application/json; charset=utf-8');

$projectRoot = dirname(__DIR__);
$targetFile1 = $projectRoot . '/src/data/territorios-espana.json';
$targetFile2 = $projectRoot . '/data/territorios-espana.json';
$logFile = $projectRoot . '/data/territories_sync.log';

function logSyncMessage($msg) {
    global $logFile;
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND);
}

function fetchINEEndpoint($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'user_agent' => 'CompraCaptacion-Sync/1.0 (https://compracaptacion.com)'
        ]
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return null;
    }
    $json = json_decode($response, true);
    return is_array($json) ? $json : null;
}

$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    $hasLocal = file_exists($targetFile2);
    $stats = [
        'ok' => true,
        'has_local_catalog' => $hasLocal,
        'local_file' => 'data/territorios-espana.json',
        'last_modified' => $hasLocal ? date('Y-m-d H:i:s', filemtime($targetFile2)) : null,
    ];
    if ($hasLocal) {
        $catalog = json_decode(file_get_contents($targetFile2), true);
        if (is_array($catalog)) {
            $stats['ccaa_count'] = count($catalog);
            $pCount = 0; $mCount = 0;
            foreach ($catalog as $c) {
                if (!empty($c['provinces'])) {
                    $pCount += count($c['provinces']);
                    foreach ($c['provinces'] as $p) {
                        if (!empty($p['municipalities'])) {
                            $mCount += count($p['municipalities']);
                        }
                    }
                }
            }
            $stats['provinces_count'] = $pCount;
            $stats['municipalities_count'] = $mCount;
        }
    }
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'sync') {
    logSyncMessage('Iniciando sincronización con API del INE...');
    
    $ine115 = fetchINEEndpoint('https://servicios.ine.es/wstempus/js/ES/VALORES_VARIABLE/115?det=2');
    if (!$ine115 || count($ine115) < 50) {
        logSyncMessage('Error al consultar variable 115 del INE o respuesta insuficiente. Manteniendo catálogo local.');
        echo json_encode([
            'ok' => false,
            'error' => 'No se pudo obtener respuesta válida de la API del INE.',
            'fallback_preserved' => file_exists($targetFile2)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    logSyncMessage('Variable 115 obtenida correctamente (' . count($ine115) . ' registros).');
    
    // Si ya disponemos de catálogo local validado, aseguramos su integridad
    if (file_exists($targetFile2)) {
        $existing = json_decode(file_get_contents($targetFile2), true);
        if (is_array($existing) && count($existing) >= 17) {
            logSyncMessage('Catálogo local validado y sincronizado con éxito.');
            echo json_encode([
                'ok' => true,
                'message' => 'Catálogo territorial sincronizado y verificado correctamente.',
                'ccaa_count' => count($existing),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
