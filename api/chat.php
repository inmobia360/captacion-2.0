<?php
/**
 * Compra Captación - API de IA Vera (Asistente Inmobiliaria Especializada entre Profesionales)
 * Conexión con modelos gratuitos / OpenRouter / Groq y motor semántico inmobiliario de respaldo.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;
$message = trim($input['message'] ?? '');
$history = is_array($input['history'] ?? null) ? $input['history'] : [];
$userName = trim($input['user_name'] ?? 'Profesional');


// Manejo de solicitudes de contacto de leads desde el dossier
$action = $_GET['action'] ?? $input['action'] ?? '';
if ($action === 'lead_inquiry' || isset($input['lead_name'])) {
    $leadName = trim($input['lead_name'] ?? '');
    $leadEmail = trim($input['lead_email'] ?? '');
    $leadPhone = trim($input['lead_phone'] ?? '');
    $recipientEmail = trim($input['recipient_agent_email'] ?? 'comercial@compracaptacion.com');
    $propertyTitle = trim($input['property_title'] ?? 'Inmueble');
    
    // Guardar lead en SQLite / Base de datos si es posible
    try {
        require_once __DIR__ . '/database.php';
        $db = CaptacionDB::get();
        
        // Buscar el usuario destinatario por email
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $userStmt->execute([$recipientEmail]);
        $targetUserId = (int)$userStmt->fetchColumn();
        
        if ($targetUserId > 0) {
            $notifTitle = "Nuevo Lead Comprador para $propertyTitle";
            $notifMsg = "$leadName ($leadPhone / $leadEmail) ha solicitado información y visita para $propertyTitle.";
            $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'info', '#/panel')")
               ->execute([$targetUserId, $notifTitle, $notifMsg]);
        }
    } catch (Exception $e) {}
    
    echo json_encode([
        'ok' => true,
        'message' => '¡Solicitud registrada con éxito! El agente asignado se pondrá en contacto a la mayor brevedad.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($message)) {
    echo json_encode(['ok' => false, 'error' => 'Mensaje vacío.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$openRouterKey = getenv('OPENROUTER_API_KEY') ?: '';
$aiResponse = null;

$systemPrompt = "Eres Vera, la analista e inteligencia artificial inmobiliaria de Compra Captación (https://compracaptacion.com/). Asesoras a agentes inmobiliarios, agencias y brokers en España de manera cercana, profesional y elegante en la colaboración entre profesionales. Destaca el reparto de honorarios 50/50, la protección registral de inmuebles con datos ciegos y la intermediación ética entre profesionales.";

if (!empty($openRouterKey)) {
    $payload = [
        'model' => 'meta-llama/llama-3.3-70b-instruct:free',
        'messages' => array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_slice($history, -6),
            [['role' => 'user', 'content' => $message]]
        ),
        'max_tokens' => 600,
        'temperature' => 0.6
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openRouterKey,
            'HTTP-Referer: https://compracaptacion.com',
            'X-Title: CompraCaptacion Vera AI'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $data = json_decode($res, true);
        $aiResponse = $data['choices'][0]['message']['content'] ?? null;
    }
}

if (empty($aiResponse)) {
    $aiResponse = generateLocalRealEstateResponse($message, $userName);
}

echo json_encode([
    'ok' => true,
    'response' => $aiResponse,
    'provider' => !empty($openRouterKey) ? 'openrouter_llama3_free' : 'vera_real_estate_engine',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
exit;

function generateLocalRealEstateResponse($msg, $name) {
    $m = mb_strtolower($msg, 'UTF-8');

    if (preg_match('/(d+[d.,]*)s*(€|eur|euros?|k)/i', $msg, $matches) || strpos($m, 'comision') !== false || strpos($m, 'honorarios') !== false || strpos($m, 'calcul') !== false || strpos($m, 'cuanto') !== false) {
        $rawNum = preg_replace('/[^d]/', '', $matches[1] ?? '210000');
        $price = intval($rawNum);
        if ($price < 1000 && $price > 0) $price = $price * 1000;
        if ($price <= 0) $price = 210000;
        
        $comm = round($price * 0.03);
        $share = round($comm / 2);
        
        return "¡Hola " . htmlspecialchars($name) . "! Para un inmueble de **" . number_format($price, 0, ',', '.') . " €**:

" .
               "• **Comisión total (3%):** " . number_format($comm, 0, ',', '.') . " €
" .
               "• **Tus honorarios como captador (50%):** " . number_format($share, 0, ',', '.') . " €
" .
               "• **Honorarios para la agencia colaboradora (50%):** " . number_format($share, 0, ',', '.') . " €

" .
               "El reparto estándar en Compra Captación es del 50/50, asegurando que ambas agencias ganan con total transparencia.";
    }

    if (strpos($m, 'privacidad') !== false || strpos($m, 'proteg') !== false || strpos($m, 'direccion') !== false || strpos($m, 'propietario') !== false || strpos($m, 'puente') !== false || strpos($m, 'segur') !== false) {
        return "En Compra Captación la **privacidad registral** está protegida por diseño:

" .
               "1. Solo se muestran datos ciegos del inmueble (zona aproximada y características generales).
" .
               "2. La dirección exacta y los datos del propietario permanecen cifrados.
" .
               "3. Solo se liberan cuando ambas partes firman el acuerdo digital de colaboración mutua.";
    }

    if (strpos($m, 'publicar') !== false || strpos($m, 'subir') !== false || strpos($m, 'captacion') !== false) {
        return "Para publicar una captación:

" .
               "1. Accede a **'Publicar Captación o Demanda'**.
" .
               "2. Selecciona Comunidad, Provincia y Municipio oficiales del INE.
" .
               "3. Introduce el precio, características y porcentaje de comisión.
" .
               "4. La plataforma cruzará automáticamente tu propiedad con las demandas solventes activas.";
    }

    if (strpos($m, 'demanda') !== false || strpos($m, 'comprador') !== false || strpos($m, 'buscar') !== false || strpos($m, 'cliente') !== false) {
        return "Para encontrar compradores o propiedades para tus clientes:

" .
               "• Publica una **Demanda de Búsqueda** con el presupuesto y zona del cliente.
" .
               "• Explora el catálogo en **Oportunidades** para ver inmuebles compartidos por otros profesionales.
" .
               "• Solicita colaboración con un clic para formalizar el acuerdo al 50/50.";
    }

    if (strpos($m, 'credito') !== false || strpos($m, 'precio') !== false || strpos($m, 'plan') !== false || strpos($m, 'cuesta') !== false || strpos($m, 'gratis') !== false) {
        return "Recibes **3 Créditos de Bienvenida gratuitos** al registrarte, válidos durante 30 días y no acumulables.

" .
               "• Puedes utilizarlos para solicitar colaboraciones o acceder a cruces de operaciones.
" .
               "• Si requieres más créditos, dispones de planes mensuales y packs adaptados al ritmo de tu agencia.";
    }

    return "¡Hola " . htmlspecialchars($name) . "! Soy Vera, tu analista inmobiliaria de IA.

" .
           "Puedo ayudarte a calcular comisiones, verificar la protección de datos de tus inmuebles o buscar cruces entre tu cartera y la de otros profesionales colegiados.

" .
           "¿Qué consulta te gustaría realizar?";
}
