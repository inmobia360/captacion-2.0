<?php
/**
 * Compra Captación - API de Territorios Oficiales de España (INE)
 * Sirve Comunidades Autónomas, Provincias y Municipios con fallback local blindado.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // Cache 24h

$jsonPath = dirname(__DIR__) . '/data/territorios-espana.json';
if (!file_exists($jsonPath)) {
    $jsonPath = dirname(__DIR__) . '/src/data/territorios-espana.json';
}

$action = $_GET['action'] ?? 'all';
$query = trim($_GET['q'] ?? '');
$ccaaId = trim($_GET['ccaa_id'] ?? '');
$provinceId = trim($_GET['province_id'] ?? '');

$fallbackProvinces = [
    'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres',
    'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca', 'Girona', 'Granada', 'Guadalajara',
    'Guipúzcoa', 'Huelva', 'Huesca', 'Illes Balears', 'Jaén', 'A Coruña', 'La Rioja', 'Las Palmas', 'León',
    'Lleida', 'Lugo', 'Madrid', 'Málaga', 'Murcia', 'Navarra', 'Ourense', 'Palencia', 'Pontevedra', 'Salamanca',
    'Santa Cruz de Tenerife', 'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo', 'Valencia',
    'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza', 'Ceuta', 'Melilla'
];

if (!file_exists($jsonPath)) {
    echo json_encode([
        'ok' => true,
        'source' => 'fallback_basic',
        'provinces' => $fallbackProvinces
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$catalog = json_decode(file_get_contents($jsonPath), true);
if (!is_array($catalog)) {
    echo json_encode([
        'ok' => true,
        'source' => 'fallback_basic',
        'provinces' => $fallbackProvinces
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Listado exclusivo de Comunidades Autónomas
if ($action === 'ccaa') {
    $ccaaList = array_map(function($c) {
        return [
            'id' => $c['id'] ?? '',
            'name' => $c['name'] ?? ''
        ];
    }, $catalog);
    echo json_encode(['ok' => true, 'ccaa' => $ccaaList], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Listado de Provincias (filtradas por CCAA si se especifica)
if ($action === 'provinces') {
    $provinces = [];
    foreach ($catalog as $c) {
        if ($ccaaId && ($c['id'] ?? '') !== $ccaaId) {
            continue;
        }
        if (!empty($c['provinces'])) {
            foreach ($c['provinces'] as $p) {
                $provinces[] = [
                    'id' => $p['id'] ?? '',
                    'name' => $p['name'] ?? '',
                    'ccaa_id' => $c['id'] ?? '',
                    'ccaa_name' => $c['name'] ?? ''
                ];
            }
        }
    }
    echo json_encode(['ok' => true, 'provinces' => $provinces], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Listado de Municipios por Provincia
if ($action === 'municipalities') {
    $municipalities = [];
    foreach ($catalog as $c) {
        if (!empty($c['provinces'])) {
            foreach ($c['provinces'] as $p) {
                if ($provinceId && ($p['id'] ?? '') !== $provinceId && ($p['name'] ?? '') !== $provinceId) {
                    continue;
                }
                if (!empty($p['municipalities'])) {
                    foreach ($p['municipalities'] as $m) {
                        $municipalities[] = [
                            'id' => $m['id'] ?? ($m['ine_code'] ?? ''),
                            'name' => $m['name'] ?? '',
                            'province_id' => $p['id'] ?? '',
                            'province_name' => $p['name'] ?? ''
                        ];
                    }
                }
            }
        }
    }
    echo json_encode(['ok' => true, 'municipalities' => $municipalities], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Búsqueda libre por término
if ($query) {
    $results = [];
    foreach ($catalog as $c) {
        if (!empty($c['provinces'])) {
            foreach ($c['provinces'] as $p) {
                if (!empty($p['municipalities'])) {
                    foreach ($p['municipalities'] as $m) {
                        $mName = $m['name'] ?? '';
                        $pName = $p['name'] ?? '';
                        if (stripos($mName, $query) !== false || stripos($pName, $query) !== false) {
                            $results[] = [
                                'municipality' => $mName,
                                'province' => $pName,
                                'ccaa' => $c['name'] ?? '',
                                'ine_code' => $m['id'] ?? ($m['ine_code'] ?? '')
                            ];
                            if (count($results) >= 50) break 3;
                        }
                    }
                }
            }
        }
    }
    echo json_encode(['ok' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. Devolución completa estructurada (por defecto)
echo json_encode([
    'ok' => true,
    'source' => 'ine_official_catalog',
    'ccaa_count' => count($catalog),
    'data' => $catalog
], JSON_UNESCAPED_UNICODE);
