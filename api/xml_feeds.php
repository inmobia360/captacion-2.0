<?php
/**
 * Compra Captación - Motor Universal de Importación y Gestión de Feeds XML
 * Compatible con Kyero v2/v3, Idealista, Inmovilla, Habitaclia, Witei y XML inmobiliario genérico.
 */
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = CaptacionDB::get();

// Crear tabla de lotes de importación si no existe
$db->exec("CREATE TABLE IF NOT EXISTS import_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    import_batch_id TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    data_origin TEXT DEFAULT 'xml_url',
    source_file_name TEXT DEFAULT '',
    source_url TEXT DEFAULT '',
    records_total INTEGER DEFAULT 0,
    records_imported INTEGER DEFAULT 0,
    records_updated INTEGER DEFAULT 0,
    properties_count INTEGER DEFAULT 0,
    active_properties_count INTEGER DEFAULT 0,
    pending_review_properties_count INTEGER DEFAULT 0,
    needs_count INTEGER DEFAULT 0,
    marketplace_published_properties_count INTEGER DEFAULT 0,
    privacy_scope TEXT DEFAULT 'global_public',
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$user = get_auth_user();
if ($user && !empty($user['id'])) {
    $userId = (int)$user['id'];
    $userEmail = (string)($user['email'] ?? '');
} else {
    // Buscar un usuario válido existente en la BD para cumplir con la clave foránea
    $userStmt = $db->query("SELECT id, email FROM users ORDER BY id ASC LIMIT 1");
    $dbUser = $userStmt ? $userStmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($dbUser && !empty($dbUser['id'])) {
        $userId = (int)$dbUser['id'];
        $userEmail = (string)$dbUser['email'];
    } else {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'No existe un usuario válido para asociar la importación.']);
        exit;
    }
}

// =========================================================================
// HELPER: NORMALIZADOR SEMÁNTICO DE TIPOLOGÍAS INMOBILIARIAS
// =========================================================================
function normalizeRealEstateTaxonomy(string $rawType, string $ref, string $title, string $desc, float $price = 0, float $surface = 0): array {
    $context = mb_strtolower("$ref $title $desc $rawType", 'UTF-8');
    
    // 1. Detección por palabras clave en contexto (referencia, descripción o título)
    if (preg_match('/(nave|industrial|almacen|almacén|poligono|polígono|talleres|fábrica|fabrica|cristaleria|cristalería)/i', $context)) {
        $cleanType = 'Nave industrial';
        $categoryKey = 'nave';
    } elseif (preg_match('/(terreno|solar|parcela|finca rústica|finca rustica|suelo|urbanizable)/i', $context)) {
        $cleanType = 'Terreno / Parcela';
        $categoryKey = 'terreno';
    } elseif (preg_match('/(local|comercial|negocio|tienda|bar|restaurante|hosteleria|hostelería)/i', $context)) {
        $cleanType = 'Local comercial';
        $categoryKey = 'comercial';
    } elseif (preg_match('/(oficina|despacho|coworking)/i', $context)) {
        $cleanType = 'Oficina';
        $categoryKey = 'oficina';
    } elseif (preg_match('/(edificio|bloque|promocion|promoción)/i', $context)) {
        $cleanType = 'Edificio';
        $categoryKey = 'edificio';
    } elseif (preg_match('/(chalet|villa|casa|adosado|pareado|finca|cortijo|masia|masía|bungalow|torre)/i', $context)) {
        $cleanType = 'Casa / Chalet';
        $categoryKey = 'casa_chalet';
    } elseif (preg_match('/(ático|atico|penthouse)/i', $context)) {
        $cleanType = 'Ático';
        $categoryKey = 'piso';
    } elseif (preg_match('/(dúplex|duplex)/i', $context)) {
        $cleanType = 'Dúplex';
        $categoryKey = 'piso';
    } elseif (preg_match('/(estudio|loft)/i', $context)) {
        $cleanType = 'Estudio';
        $categoryKey = 'piso';
    } elseif (preg_match('/(garaje|parking|cochera)/i', $context)) {
        $cleanType = 'Garaje';
        $categoryKey = 'garaje';
    } elseif (preg_match('/(trastero)/i', $context)) {
        $cleanType = 'Trastero';
        $categoryKey = 'trastero';
    } else {
        // 2. Diccionario de traducción de términos técnicos de CRM en inglés
        $lowerRaw = strtolower(trim($rawType));
        $dict = [
            'apartment' => 'Piso / Apartamento',
            'flat' => 'Piso / Apartamento',
            'piso' => 'Piso',
            'apartamento' => 'Apartamento',
            'house' => 'Casa / Chalet',
            'villa' => 'Casa / Chalet',
            'chalet' => 'Casa / Chalet',
            'townhouse' => 'Casa / Chalet',
            'penthouse' => 'Ático',
            'duplex' => 'Dúplex',
            'studio' => 'Estudio',
            'commercial' => 'Local comercial',
            'business' => 'Local comercial',
            'office' => 'Oficina',
            'warehouse' => 'Nave industrial',
            'industrial' => 'Nave industrial',
            'land' => 'Terreno / Parcela',
            'plot' => 'Terreno / Parcela',
            'building' => 'Edificio',
            'garage' => 'Garaje',
            'storage' => 'Trastero'
        ];
        $cleanType = $dict[$lowerRaw] ?? ($rawType ?: 'Piso');
        $categoryKey = str_contains(strtolower($cleanType), 'nave') ? 'nave' : (str_contains(strtolower($cleanType), 'local') ? 'comercial' : (str_contains(strtolower($cleanType), 'terreno') ? 'terreno' : (str_contains(strtolower($cleanType), 'casa') || str_contains(strtolower($cleanType), 'chalet') ? 'casa_chalet' : 'piso')));
    }

    return ['type' => $cleanType, 'category_key' => $categoryKey];
}

// =========================================================================
// HELPER: PARSE XML (Kyero, Idealista, Inmovilla, Habitaclia, Witei, Generic)
// =========================================================================
function parseUniversalRealEstateXml(string $xmlContent): array {
    // 1. Limpieza de BOM UTF-8 y codificación
    $cleanXml = preg_replace('/^\xEF\xBB\xBF/', '', trim($xmlContent));
    
    // Normalizar a UTF-8 si viniese en ISO-8859-1 o Windows-1252
    if (!mb_check_encoding($cleanXml, 'UTF-8')) {
        $cleanXml = mb_convert_encoding($cleanXml, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
    }
    
    // 2. Limpieza segura y prevención XXE sin romper tags válidos
    $cleanXml = preg_replace('/<!DOCTYPE[^>]*(\[[^\]]*\])?>/is', '', $cleanXml);
    $cleanXml = preg_replace('/<!ENTITY[^>]*>/is', '', $cleanXml);
    
    // Suprimir advertencias libxml para manejo limpio de errores
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($cleanXml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
    
    if (!$xml) {
        // Intento de fallback con DOMDocument
        $dom = new DOMDocument();
        $loaded = @$dom->loadXML($cleanXml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA);
        if ($loaded) {
            $xml = simplexml_import_dom($dom);
        }
    }

    if (!$xml) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $errMsg = !empty($errors) ? $errors[0]->message : 'El XML no tiene un formato parseable.';
        throw new Exception("Error al procesar el archivo XML: " . trim($errMsg));
    }

    $properties = [];
    
    // Detectar nodos de propiedades comunes
    $nodes = [];
    if (isset($xml->property) && count($xml->property) > 0) {
        $nodes = $xml->property;
    } elseif (isset($xml->inmueble) && count($xml->inmueble) > 0) {
        $nodes = $xml->inmueble;
    } elseif (isset($xml->item) && count($xml->item) > 0) {
        $nodes = $xml->item;
    } elseif (isset($xml->propiedad) && count($xml->propiedad) > 0) {
        $nodes = $xml->propiedad;
    } elseif (isset($xml->oferta) && count($xml->oferta) > 0) {
        $nodes = $xml->oferta;
    } elseif (isset($xml->vivienda) && count($xml->vivienda) > 0) {
        $nodes = $xml->vivienda;
    } elseif (isset($xml->anuncio) && count($xml->anuncio) > 0) {
        $nodes = $xml->anuncio;
    } else {
        $children = $xml->children();
        if (count($children) > 0) {
            $first = $children[0];
            if (isset($first->property) && count($first->property) > 0) $nodes = $first->property;
            elseif (isset($first->inmueble) && count($first->inmueble) > 0) $nodes = $first->inmueble;
            elseif (isset($first->propiedad) && count($first->propiedad) > 0) $nodes = $first->propiedad;
            else $nodes = $children;
        }
    }

    foreach ($nodes as $node) {
        $ref = (string)($node->ref ?? $node->id ?? $node->reference ?? $node->cod_inmueble ?? $node->propertyCode ?? $node->codigo ?? uniqid('XML-'));
        $rawTitle = (string)($node->title->es ?? $node->title ?? $node->desc->es->title ?? $node->titulo ?? '');
        $price = (float)($node->price ?? $node->precio ?? $node->val_venta ?? $node->val_alquiler ?? $node->valor ?? 0);
        $rawType = (string)($node->type ?? $node->tipo ?? $node->property_type ?? $node->tipologia ?? $node->subtipo ?? 'Piso');
        $desc = (string)($node->desc->es->description ?? $node->desc->es ?? $node->desc ?? $node->description ?? $node->descripcion ?? '');
        $surface = (float)($node->surface_area->built ?? $node->surface_area->plot ?? $node->surface ?? $node->superficie ?? $node->m2 ?? $node->sup_const ?? $node->superficie_construida ?? 0);
        $town = (string)($node->town ?? $node->poblacion ?? $node->city ?? $node->municipio ?? $node->localidad ?? '');
        $province = (string)($node->province ?? $node->provincia ?? 'España');
        $zone = (string)($node->zone ?? $node->location_detail ?? $node->zona ?? $node->barrio ?? $town);
        $address = (string)($node->address ?? $node->direccion ?? '');

        // Normalización Semántica de Categoría / Tipología
        $taxonomy = normalizeRealEstateTaxonomy($rawType, $ref, $rawTitle, $desc, $price, $surface);
        $type = $taxonomy['type'];

        // Título limpio y profesional
        if (empty($rawTitle) || stripos($rawTitle, 'apartment en') !== false || stripos($rawTitle, 'flat en') !== false) {
            $title = "$type en $town" . ($surface > 0 ? " ($surface m²)" : "");
        } else {
            $title = $rawTitle;
        }

        $operation = (string)($node->price_freq ?? $node->operacion ?? $node->operation ?? $node->tipo_operacion ?? 'sale');
        if (stripos($operation, 'rent') !== false || stripos($operation, 'alquiler') !== false) {
            $operationType = 'Alquiler';
        } else {
            $operationType = 'Venta';
        }

        // Localización
        $town = (string)($node->town ?? $node->poblacion ?? $node->city ?? $node->municipio ?? $node->localidad ?? '');
        $province = (string)($node->province ?? $node->provincia ?? 'España');
        $zone = (string)($node->zone ?? $node->location_detail ?? $node->zona ?? $node->barrio ?? $town);
        $address = (string)($node->address ?? $node->direccion ?? '');

        // Características físicas
        $surface = (float)($node->surface_area->built ?? $node->surface_area->plot ?? $node->surface ?? $node->superficie ?? $node->m2 ?? $node->sup_const ?? $node->superficie_construida ?? 0);
        $beds = (int)($node->beds ?? $node->bedrooms ?? $node->habitaciones ?? $node->dormitorios ?? $node->num_habitaciones ?? 0);
        $baths = (int)($node->baths ?? $node->bathrooms ?? $node->banos ?? $node->aseos ?? 0);
        $pool = !empty($node->pool) && (string)$node->pool !== '0';
        $lift = !empty($node->lift) && (string)$node->lift !== '0';
        $garage = !empty($node->garage) && (string)$node->garage !== '0';

        // Descripción
        $desc = (string)($node->desc->es->description ?? $node->desc->es ?? $node->desc ?? $node->description ?? $node->descripcion ?? '');
        if (empty($title)) {
            $title = "$type en $town" . ($surface > 0 ? " ($surface m²)" : "");
        }

        // Imágenes
        $images = [];
        if (isset($node->images->image)) {
            foreach ($node->images->image as $img) {
                $imgUrl = (string)($img->url ?? $img);
                if (!empty($imgUrl)) $images[] = $imgUrl;
            }
        } elseif (isset($node->fotos->foto)) {
            foreach ($node->fotos->foto as $img) {
                $imgUrl = (string)($img->url ?? $img);
                if (!empty($imgUrl)) $images[] = $imgUrl;
            }
        } elseif (isset($node->pictures->picture)) {
            foreach ($node->pictures->picture as $img) {
                $imgUrl = (string)($img->url ?? $img);
                if (!empty($imgUrl)) $images[] = $imgUrl;
            }
        }

        if (empty($images)) {
            $images[] = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&auto=format&fit=crop&q=80';
        }

        $features = [];
        if ($pool) $features[] = 'Piscina';
        if ($lift) $features[] = 'Ascensor';
        if ($garage) $features[] = 'Garaje';
        if ($surface > 0) $features[] = "$surface m² construidos";
        if ($beds > 0) $features[] = "$beds hab.";
        if ($baths > 0) $features[] = "$baths baños";

        $properties[] = [
            'ref' => $ref,
            'title' => trim($title),
            'property_type' => $type,
            'operation_type' => $operationType,
            'price' => $price,
            'commission_percentage' => 3.0,
            'commission_amount' => round($price * 0.03, 2),
            'province' => $province ?: 'España',
            'municipality' => $town ?: 'España',
            'zone' => $zone,
            'address_public' => $address ?: "$town, $province",
            'bedrooms' => $beds,
            'bathrooms' => $baths,
            'surface_m2' => $surface,
            'is_exclusive' => 1,
            'description_public' => mb_substr(strip_tags($desc), 0, 1500, 'UTF-8') ?: "$type en venta en $town con $surface m².",
            'images_json' => json_encode($images),
            'features_json' => json_encode($features),
            'source_origin' => 'xml_feed',
            'status' => 'active',
            'privacy_scope' => 'global_public'
        ];
    }

    return $properties;
}

// =========================================================================
// ROUTER DE ACCIONES
// =========================================================================

// 1. IMPORTAR DESDE URL
if ($action === 'import_url' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: $_POST;
    $url = trim((string)($input['url'] ?? ''));

    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'code' => 'invalid_url', 'message' => 'Introduce una URL de XML válida.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Descargar XML con cURL y reintentos defensivos
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) CompraCaptacion-XMLImporter/1.5');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $xmlData = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 400 || empty($xmlData)) {
        http_response_code(422);
        echo json_encode([
            'ok' => false,
            'code' => 'fetch_error',
            'message' => "No se pudo descargar el XML desde la URL (HTTP $httpCode). " . ($curlErr ? "Detalle: $curlErr" : '')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $properties = parseUniversalRealEstateXml((string)$xmlData);
        if (empty($properties)) {
            echo json_encode([
                'ok' => false,
                'code' => 'no_properties',
                'message' => 'No se encontraron inmuebles válidos en el XML analizado.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $batchId = 'BATCH-' . strtoupper(substr(md5($url . time()), 0, 10));
        $importedCount = 0;

        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO records (
            record_key, user_id, user_email, record_type, title, property_type, operation_type,
            price, commission_percentage, commission_amount, province, municipality, zone,
            address_public, bedrooms, bathrooms, surface_m2, is_exclusive, description_public,
            images_json, features_json, status, privacy_scope, data_origin
        ) VALUES (?, ?, ?, 'property', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'global_public', 'xml_feed')");

        foreach ($properties as $p) {
            $recordKey = 'PROP-' . substr(md5($batchId . $p['ref']), 0, 12);
            $stmt->execute([
                $recordKey, $userId, $userEmail, $p['title'], $p['property_type'], $p['operation_type'],
                $p['price'], $p['commission_percentage'], $p['commission_amount'],
                $p['province'], $p['municipality'], $p['zone'], $p['address_public'],
                $p['bedrooms'], $p['bathrooms'], $p['surface_m2'], $p['is_exclusive'],
                $p['description_public'], $p['images_json'], $p['features_json']
            ]);
            $importedCount++;
        }

        // Registrar lote en import_batches
        $batchStmt = $db->prepare("INSERT INTO import_batches (
            import_batch_id, user_id, data_origin, source_file_name, source_url,
            records_total, records_imported, properties_count, active_properties_count,
            marketplace_published_properties_count, privacy_scope, status
        ) VALUES (?, ?, 'xml_url', ?, ?, ?, ?, ?, ?, ?, 'global_public', 'active')");

        $sourceName = basename(parse_url($url, PHP_URL_PATH) ?: 'Feed.xml');
        $batchStmt->execute([
            $batchId, $userId, $sourceName, $url,
            $importedCount, $importedCount, $importedCount, $importedCount,
            $importedCount
        ]);

        $db->commit();

        echo json_encode([
            'ok' => true,
            'import_batch_id' => $batchId,
            'imported' => $importedCount,
            'updated' => 0,
            'pending_review' => 0,
            'rejected' => 0,
            'message' => "XML importado con éxito: $importedCount propiedades añadidas a tu cartera."
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(422);
        echo json_encode([
            'ok' => false,
            'code' => 'parse_error',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2. LISTAR LOTES DE IMPORTACIÓN
if ($action === 'list') {
    $stmt = $db->prepare("SELECT * FROM import_batches WHERE status != 'deleted' ORDER BY id DESC");
    $stmt->execute();
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'batches' => $batches
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. SUBIR ARCHIVO LOCAL (XML, CSV, JSON)
if ($action === 'upload_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'code' => 'upload_failed', 'message' => 'No se recibió ningún archivo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileContent = file_get_contents($tmpPath);

    try {
        $properties = parseUniversalRealEstateXml((string)$fileContent);
        $batchId = 'FILE-' . strtoupper(substr(md5($fileName . time()), 0, 10));
        $importedCount = 0;

        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO records (
            record_key, user_id, user_email, record_type, title, property_type, operation_type,
            price, commission_percentage, commission_amount, province, municipality, zone,
            address_public, bedrooms, bathrooms, surface_m2, is_exclusive, description_public,
            images_json, features_json, status, privacy_scope, data_origin
        ) VALUES (?, ?, ?, 'property', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'global_public', 'file_upload')");

        foreach ($properties as $p) {
            $recordKey = 'PROP-' . substr(md5($batchId . $p['ref']), 0, 12);
            $stmt->execute([
                $recordKey, $userId, $userEmail, $p['title'], $p['property_type'], $p['operation_type'],
                $p['price'], $p['commission_percentage'], $p['commission_amount'],
                $p['province'], $p['municipality'], $p['zone'], $p['address_public'],
                $p['bedrooms'], $p['bathrooms'], $p['surface_m2'], $p['is_exclusive'],
                $p['description_public'], $p['images_json'], $p['features_json']
            ]);
            $importedCount++;
        }

        $batchStmt = $db->prepare("INSERT INTO import_batches (
            import_batch_id, user_id, data_origin, source_file_name,
            records_total, records_imported, properties_count, active_properties_count,
            marketplace_published_properties_count, privacy_scope, status
        ) VALUES (?, ?, 'file_upload', ?, ?, ?, ?, ?, ?, 'global_public', 'active')");

        $batchStmt->execute([
            $batchId, $userId, $fileName,
            $importedCount, $importedCount, $importedCount, $importedCount,
            $importedCount
        ]);

        $db->commit();

        echo json_encode([
            'ok' => true,
            'import_batch_id' => $batchId,
            'imported' => $importedCount,
            'properties_imported' => $importedCount,
            'properties_updated' => 0,
            'properties_pending_review' => 0,
            'properties_failed' => 0
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(422);
        echo json_encode(['ok' => false, 'code' => 'parse_error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4. ELIMINAR TODOS LOS LOTES DE XML
if ($action === 'delete_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener todos los registros importados por XML del sistema
        $stmtRecs = $db->prepare("SELECT id, record_key, title FROM records WHERE data_origin IN ('xml_url', 'xml_file', 'file_upload', 'xml_feed') OR record_key LIKE 'PROP-%' OR record_key LIKE 'BATCH-%'");
        $stmtRecs->execute();
        $allRecords = $stmtRecs->fetchAll(PDO::FETCH_ASSOC);

        $deletedCount = 0;
        $preservedCount = 0;

        foreach ($allRecords as $rec) {
            $recId = (int)$rec['id'];
            
            // Verificar si el registro tiene solicitudes de desbloqueo de datos
            $stmtUnlocked = $db->prepare("SELECT COUNT(*) FROM access_logs WHERE record_id = ?");
            $stmtUnlocked->execute([$recId]);
            $hasUnlocks = (int)$stmtUnlocked->fetchColumn() > 0;

            // Verificar si el registro tiene una operación en curso
            $stmtOps = $db->prepare("SELECT COUNT(*) FROM operations WHERE record_id = ? AND status IN ('active', 'pending', 'in_progress', 'closing')");
            $stmtOps->execute([$recId]);
            $hasOperations = (int)$stmtOps->fetchColumn() > 0;

            if ($hasUnlocks || $hasOperations) {
                // Preservar la propiedad para no romper la operación en curso
                $stmtPreserve = $db->prepare("UPDATE records SET data_origin = 'preserved_in_operation' WHERE id = ?");
                $stmtPreserve->execute([$recId]);
                $preservedCount++;
            } else {
                // Eliminar permanentemente de la plataforma y del marketplace
                $stmtDel = $db->prepare("DELETE FROM records WHERE id = ?");
                $stmtDel->execute([$recId]);
                $deletedCount++;
            }
        }

        // Vaciar todos los lotes de import_batches
        $db->exec("DELETE FROM import_batches");

        $msg = "Todos tus ficheros XML han sido eliminados de la plataforma ($deletedCount propiedades retiradas).";
        if ($preservedCount > 0) {
            $msg .= " Se mantuvieron activas $preservedCount propiedad(es) por estar en curso de cierre de operación o con datos desbloqueados.";
        }

        echo json_encode([
            'ok' => true,
            'deleted_count' => $deletedCount,
            'preserved_count' => $preservedCount,
            'message' => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 5. ELIMINAR UN LOTE
if ($action === 'delete_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $batchId = trim((string)($input['batch_id'] ?? $_GET['id'] ?? ''));
    if (empty($batchId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Lote no especificado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Obtener los registros asociados al lote
        $stmtRecs = $db->prepare("SELECT id, record_key, title FROM records WHERE data_origin = ? OR record_key LIKE ? OR record_key LIKE ?");
        $stmtRecs->execute([$batchId, "%$batchId%", "PROP-$batchId%"]);
        $batchRecords = $stmtRecs->fetchAll(PDO::FETCH_ASSOC);

        $deletedCount = 0;
        $preservedCount = 0;

        foreach ($batchRecords as $rec) {
            $recId = (int)$rec['id'];
            
            // Verificar desbloqueos de datos
            $stmtUnlocked = $db->prepare("SELECT COUNT(*) FROM access_logs WHERE record_id = ?");
            $stmtUnlocked->execute([$recId]);
            $hasUnlocks = (int)$stmtUnlocked->fetchColumn() > 0;

            // Verificar operaciones en curso
            $stmtOps = $db->prepare("SELECT COUNT(*) FROM operations WHERE record_id = ? AND status IN ('active', 'pending', 'in_progress', 'closing')");
            $stmtOps->execute([$recId]);
            $hasOperations = (int)$stmtOps->fetchColumn() > 0;

            if ($hasUnlocks || $hasOperations) {
                // Preservar la propiedad para la operación en curso
                $stmtPreserve = $db->prepare("UPDATE records SET data_origin = 'preserved_in_operation' WHERE id = ?");
                $stmtPreserve->execute([$recId]);
                $preservedCount++;
            } else {
                // Eliminar permanentemente
                $stmtDel = $db->prepare("DELETE FROM records WHERE id = ?");
                $stmtDel->execute([$recId]);
                $deletedCount++;
            }
        }

        // Eliminar lote de import_batches por su batch_id
        $stmtBatch = $db->prepare("DELETE FROM import_batches WHERE import_batch_id = ?");
        $stmtBatch->execute([$batchId]);

        $msg = "Fichero XML eliminado ($deletedCount propiedades retiradas de la plataforma).";
        if ($preservedCount > 0) {
            $msg .= " Se mantuvieron activas $preservedCount propiedad(es) por encontrarse en curso de cierre de operación o con desbloqueos.";
        }

        echo json_encode([
            'ok' => true,
            'deleted_count' => $deletedCount,
            'preserved_count' => $preservedCount,
            'message' => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Default fallback
echo json_encode(['ok' => true, 'batches' => []], JSON_UNESCAPED_UNICODE);
