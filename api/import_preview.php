<?php declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = trim((string)($input['source_url'] ?? $_GET['url'] ?? ''));
$sourceText = trim((string)($input['source_text'] ?? ''));

if (empty($url) && empty($sourceText)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Indica una URL o pega el texto del anuncio.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function parseRealEstateHtml(string $html, string $url = ''): array {
    $fields = [
        'title' => '',
        'price' => null,
        'surface' => null,
        'bedrooms' => null,
        'bathrooms' => null,
        'locality' => '',
        'province' => '',
        'propertyType' => 'Piso',
        'operation' => 'Venta',
        'description' => '',
        'source_url' => $url
    ];

    // 1. JSON-LD Extraction
    if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $jsonStr) {
            $data = json_decode(trim($jsonStr), true);
            if (is_array($data)) {
                $items = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : [$data];
                foreach ($items as $item) {
                    if (isset($item['name']) && empty($fields['title'])) $fields['title'] = (string)$item['name'];
                    if (isset($item['description']) && empty($fields['description'])) $fields['description'] = (string)$item['description'];
                    if (isset($item['offers']['price']) && empty($fields['price'])) $fields['price'] = (float)$item['offers']['price'];
                    if (isset($item['floorSize']['value']) && empty($fields['surface'])) $fields['surface'] = (float)$item['floorSize']['value'];
                    if (isset($item['numberOfRooms']) && empty($fields['bedrooms'])) $fields['bedrooms'] = (int)$item['numberOfRooms'];
                    if (isset($item['numberOfBathroomsTotal']) && empty($fields['bathrooms'])) $fields['bathrooms'] = (int)$item['numberOfBathroomsTotal'];
                    if (isset($item['address']['addressLocality']) && empty($fields['locality'])) $fields['locality'] = (string)$item['address']['addressLocality'];
                    if (isset($item['address']['addressRegion']) && empty($fields['province'])) $fields['province'] = (string)$item['address']['addressRegion'];
                }
            }
        }
    }

    // 2. OpenGraph Meta Tags
    if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/i', $html, $m) && empty($fields['title'])) {
        $fields['title'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/i', $html, $m) && empty($fields['description'])) {
        $fields['description'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/i', $html, $m) && empty($fields['description'])) {
        $fields['description'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m) && empty($fields['title'])) {
        $fields['title'] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    // 3. Regex Fallback for Price, Surface, Bedrooms, Bathrooms
    $allText = strip_tags($html) . ' ' . $fields['title'] . ' ' . $fields['description'];
    
    if (empty($fields['price']) && preg_match('/([\d\.\,]{4,10})\s*(?:€|EUR|euros)/i', $allText, $m)) {
        $cleanPrice = (float)str_replace(['.', ','], ['', '.'], $m[1]);
        if ($cleanPrice > 1000) $fields['price'] = $cleanPrice;
    }
    
    if (empty($fields['surface']) && preg_match('/(\d{2,5})\s*(?:m²|m2|metros|m\s*cuadrados)/i', $allText, $m)) {
        $fields['surface'] = (float)$m[1];
    }
    
    if (empty($fields['bedrooms']) && preg_match('/(\d{1,2})\s*(?:hab|dorm|habitación|habitaciones|dormitorios)/i', $allText, $m)) {
        $fields['bedrooms'] = (int)$m[1];
    }

    if (empty($fields['bathrooms']) && preg_match('/(\d{1,2})\s*(?:baño|baños|bany|aseo|aseos|wc)/i', $allText, $m)) {
        $fields['bathrooms'] = (int)$m[1];
    }

    if (preg_match('/(chalet|casa|ático|atico|dúplex|duplex|apartamento|estudio|local|nave|oficina|terreno|solar|edificio)/i', $allText, $m)) {
        $found = mb_strtolower($m[1], 'UTF-8');
        if (str_contains($found, 'chalet') || str_contains($found, 'casa')) $fields['propertyType'] = 'Casa / chalet';
        elseif (str_contains($found, 'ático') || str_contains($found, 'atico')) $fields['propertyType'] = 'Ático';
        elseif (str_contains($found, 'dúplex') || str_contains($found, 'duplex')) $fields['propertyType'] = 'Dúplex';
        elseif (str_contains($found, 'apartamento')) $fields['propertyType'] = 'Apartamento';
        elseif (str_contains($found, 'estudio')) $fields['propertyType'] = 'Estudio';
        elseif (str_contains($found, 'local')) $fields['propertyType'] = 'Local comercial';
        elseif (str_contains($found, 'nave')) $fields['propertyType'] = 'Nave';
        elseif (str_contains($found, 'oficina')) $fields['propertyType'] = 'Oficina';
        elseif (str_contains($found, 'terreno') || str_contains($found, 'solar')) $fields['propertyType'] = 'Terreno / solar';
        elseif (str_contains($found, 'edificio')) $fields['propertyType'] = 'Edificio residencial';
        else $fields['propertyType'] = 'Piso';
    }

    if (preg_match('/(alquiler|rent)/i', $allText)) {
        $fields['operation'] = 'Alquiler';
    } else {
        $fields['operation'] = 'Venta';
    }

    if (!empty($fields['description'])) {
        $fields['description'] = mb_substr(trim(preg_replace('/\s+/', ' ', $fields['description'])), 0, 1200, 'UTF-8');
    }

    return $fields;
}

$html = '';
if (!empty($sourceText)) {
    $html = $sourceText;
} elseif (!empty($url)) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = (string)curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400 || empty($html)) {
        echo json_encode([
            'ok' => true,
            'assistedRequired' => true,
            'message' => 'La web de origen requiere importación asistida. Copia y pega el texto del anuncio a continuación.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$fields = parseRealEstateHtml($html, $url);

echo json_encode([
    'ok' => true,
    'assistedRequired' => false,
    'fields' => $fields,
    'message' => 'Ficha analizada con éxito.'
], JSON_UNESCAPED_UNICODE);
