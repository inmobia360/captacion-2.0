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
$documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$homeDir = rtrim((string)(getenv('HOME') ?: ($_SERVER['HOME'] ?? '')), '/\\');

$configCandidates = array_values(array_unique(array_filter([
    $homeDir !== '' ? $homeDir . '/vera-config.php' : null,
    dirname(__DIR__, 2) . '/vera-config.php', // Directorio del dominio (junto a public_html)
    dirname(__DIR__, 3) . '/vera-config.php', // Directorio de cuenta/usuario
    $documentRoot !== '' ? $documentRoot . '/../vera-config.php' : null,
    $documentRoot !== '' ? $documentRoot . '/../../vera-config.php' : null,
    dirname(__DIR__, 1) . '/vera-config.php', // Raíz de public_html
    $documentRoot !== '' ? $documentRoot . '/vera-config.php' : null,
    dirname(__DIR__, 4) . '/vera-config.php'
])));
$configPath = '';
foreach ($configCandidates as $candidate) {
    if (is_string($candidate) && $candidate !== '' && $candidate !== '/vera-config.php' && is_file($candidate) && is_readable($candidate)) {
        $configPath = $candidate;
        break;
    }
}
$veraConfig = ($configPath !== '' && file_exists($configPath)) ? require $configPath : [];
$onboarding = is_array($input['onboarding'] ?? null) ? $input['onboarding'] : [];
$guideId = preg_replace('/[^a-z0-9_-]/i', '', (string)($onboarding['guide_id'] ?? ''));
$phaseId = preg_replace('/[^a-z0-9_-]/i', '', (string)($onboarding['phase_id'] ?? ''));
$userLevel = in_array(($onboarding['user_level'] ?? ''), ['junior', 'senior'], true) ? $onboarding['user_level'] : 'junior';
$currentRoute = substr(preg_replace('/[^a-z0-9_\/#?=&.-]/i', '', (string)($onboarding['current_route'] ?? '')), 0, 160);
$currentStep = max(0, min(20, (int)($onboarding['current_step'] ?? 0)));

if ($message === '') {
    if (!defined('VERA_LIB_MODE')) {
        echo json_encode(['ok' => false, 'error' => 'Mensaje vacío.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return;
}
$openRouterKey = getenv('OPENROUTER_API_KEY') ?: ($veraConfig['openrouter_api_key'] ?? '');
$veraProvider = strtolower(trim($veraConfig['provider'] ?? getenv('VERA_PROVIDER') ?: 'ollama'));
$ollamaUrl = trim($veraConfig['url'] ?? getenv('OLLAMA_URL') ?: 'http://127.0.0.1:11434');
if (!preg_match('#/api/(chat|generate)#i', $ollamaUrl)) {
    $ollamaUrl = rtrim($ollamaUrl, '/') . '/api/chat';
}
$ollamaApiKey = trim($veraConfig['api_key'] ?? getenv('OLLAMA_API_KEY') ?: '');
$ollamaModel = trim($veraConfig['model'] ?? getenv('OLLAMA_MODEL') ?: 'qwen3:4b');

if (($_GET['health'] ?? '') === '1') {
    $healthToken = trim($veraConfig['health_token'] ?? getenv('VERA_HEALTH_TOKEN') ?: '');
    $providedToken = trim((string)($_SERVER['HTTP_X_VERA_HEALTH_TOKEN'] ?? ''));
    if ($healthToken === '' || $providedToken === '' || !hash_equals($healthToken, $providedToken)) {
        http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Diagnóstico no autorizado.'], JSON_UNESCAPED_UNICODE); exit;
    }
    $startedAt = microtime(true);
    $health = callVeraJsonEndpoint($ollamaUrl, ['model'=>$ollamaModel,'messages'=>[['role'=>'user','content'=>'Responde únicamente: OK']],'stream'=>false,'options'=>['temperature'=>0]], $ollamaApiKey ? ['Authorization: Bearer '.$ollamaApiKey] : [], 8);
    $healthy = !empty($health['message']['content']) || !empty($health['choices'][0]['message']['content']);
    echo json_encode(['ok'=>true,'provider'=>$veraProvider,'model'=>$ollamaModel,'ollama_reachable'=>$healthy,'latency_ms'=>round((microtime(true)-$startedAt)*1000),'diagnostic'=>$healthy?'Ollama respondió correctamente.':'Ollama no devolvió una respuesta válida.'], JSON_UNESCAPED_UNICODE); exit;
}

$normalizedMessage = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);
$normalizedMessage = trim(preg_replace('/\s+/u', ' ', $normalizedMessage));
$isGreeting = (bool)preg_match('/^(hola|buenas|buenos dias|buenas tardes|buenas noches|que tal|hey|hello|necesito ayuda)[!.?,\s]*$/u', $normalizedMessage);
$topicSignal = (bool)preg_match('/(compra captaci[oó]n|captaci[oó]n|demanda|propiedad|inmueble|agencia|agente|inmobili|matching|coincidencia|50\s*\/\s*50|honorario|cr[eé]dito|vera|dossier|publicar|desbloquear|n[dñ]a|colaboraci[oó]n|perfil|stripe|operaci[oó]n|ficha|contacto|soporte)/u', $normalizedMessage);
if ($isGreeting) {
    echo json_encode(['ok'=>true,'response'=>'¡Hola! Soy Vera. Puedo ayudarte con Compra Captación y con la productividad de tu negocio inmobiliario. ¿Qué necesitas hacer hoy?','provider'=>'vera_conversation_guard','timestamp'=>date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE); exit;
}
if (!$topicSignal) {
    echo json_encode(['ok'=>true,'response'=>'Ahora estoy especializada en Compra Captación y en ayudarte a mejorar la productividad de tu negocio inmobiliario. Más adelante incorporaré nuevas funciones relacionadas con el sector. ¿Quieres trabajar con una propiedad, una demanda o una coincidencia?','provider'=>'vera_scope_guard','timestamp'=>date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE); exit;
}
$aiResponse = null;
$usedProvider = 'vera_real_estate_engine';
$suggestedAction = null;
$nextStep = null;

function inferVeraAction($phaseId) {
    if (in_array($phaseId, ['phase-2-profile', 'step-2-profile', 'profile'], true)) {
        return [
            'action' => ['type' => 'navigate', 'target' => '#/area-privada?panel=profile', 'label' => 'Ir a mi perfil profesional'],
            'next_step' => 'phase-3-captation'
        ];
    } elseif (in_array($phaseId, ['phase-3-captation', 'publish-captation', 'captacion'], true)) {
        return [
            'action' => ['type' => 'navigate', 'target' => '#/ofrecer-captacion', 'label' => 'Publicar captación 50/50'],
            'next_step' => 'phase-4-demand'
        ];
    } elseif (in_array($phaseId, ['phase-4-demand', 'publish-demand', 'demand'], true)) {
        return [
            'action' => ['type' => 'navigate', 'target' => '#/buscar-captaciones', 'label' => 'Publicar demanda de cliente'],
            'next_step' => 'phase-5-matches'
        ];
    } elseif (in_array($phaseId, ['phase-5-matches', 'matches'], true)) {
        return [
            'action' => ['type' => 'navigate', 'target' => '#/coincidencias-ventas', 'label' => 'Revisar coincidencias'],
            'next_step' => 'phase-6-collaboration'
        ];
    } elseif (in_array($phaseId, ['phase-6-collaboration', 'collaboration'], true)) {
        return [
            'action' => ['type' => 'navigate', 'target' => '#/marketplace', 'label' => 'Explorar marketplace'],
            'next_step' => 'phase-7-closing'
        ];
    } elseif (in_array($phaseId, ['phase-1-orientation', 'orientation', 'welcome'], true)) {
        return [
            'action' => ['type' => 'navigate', 'target' => '#/area-privada?panel=academy', 'label' => 'Ver Academia Compra Captación'],
            'next_step' => 'phase-2-profile'
        ];
    }
    return ['action' => null, 'next_step' => null];
}

$inferred = inferVeraAction($phaseId);
$suggestedAction = $inferred['action'];
$nextStep = $inferred['next_step'];

$contextPrompt = "\n[CONTEXTO ACADEMIA COMPRA CAPTACIÓN]: Guía={$guideId}, Fase={$phaseId}, Paso={$currentStep}, NivelUsuario={$userLevel}, RutaActual={$currentRoute}. Responde con un tono muy humano, cálido, empático y cercano, como una colega experta en el sector inmobiliario español que asesora a otro profesional de tú a tú. Sé pedagógica y paciente si es Junior; concisa, analítica y ejecutiva si es Senior. Destaca siempre que la comisión se comparte al 50/50 con total ética, los datos ciegos protegen la exclusiva contra puenteos y disponen de 3 créditos de bienvenida para empezar.";
$systemPrompt = "Eres Vera, la asesora e inteligencia artificial inmobiliaria de Compra Captación (https://compracaptacion.com/). Tu trato es natural, profesional, empático y humano. Hablas de tú a tú con agentes y brokers inmobiliarios en España. Evitas sonar rígida, formulista o como un manual de instrucciones. Transmites tranquilidad, confianza y espíritu de colaboración entre agencias para cerrar operaciones conjuntas al 50/50." . $contextPrompt;

// Ollama/Qwen es el proveedor principal cuando está habilitado. La API queda
// detrás de PHP para no exponer el VPS ni las credenciales al navegador.
if (in_array($veraProvider, ['ollama', 'qwen', 'ollama_qwen3:4b'], true)) {
    $ollamaMessages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        array_slice($history, -6),
        [['role' => 'user', 'content' => $message]]
    );
    $ollamaPayload = [
        'model' => $ollamaModel,
        'messages' => $ollamaMessages,
        'stream' => false,
        'options' => [
            'temperature' => 0.65,
            'top_p' => 0.9
        ]
    ];
    $ollamaHeaders = [];
    if (!empty($ollamaApiKey)) {
        $ollamaHeaders[] = 'Authorization: Bearer ' . $ollamaApiKey;
    }
    $res = callVeraJsonEndpoint($ollamaUrl, $ollamaPayload, $ollamaHeaders, 8);
    if (!empty($res['message']['content'])) {
        $aiResponse = trim($res['message']['content']);
        $usedProvider = 'ollama_' . $ollamaModel;
    } elseif (!empty($res['choices'][0]['message']['content'])) {
        $aiResponse = trim($res['choices'][0]['message']['content']);
        $usedProvider = 'ollama_' . $ollamaModel;
    }
}

// Respaldo secundario: OpenRouter
if (empty($aiResponse) && !empty($openRouterKey)) {
    $orMessages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        array_slice($history, -6),
        [['role' => 'user', 'content' => $message]]
    );
    $orPayload = [
        'model' => 'meta-llama/llama-3.3-70b-instruct:free',
        'messages' => $orMessages,
        'temperature' => 0.65
    ];
    $res = callVeraJsonEndpoint('https://openrouter.ai/api/v1/chat/completions', $orPayload, [
        'Authorization: Bearer ' . $openRouterKey,
        'HTTP-Referer: https://compracaptacion.com',
        'X-Title: Compra Captacion Vera'
    ], 10);
    if (!empty($res['choices'][0]['message']['content'])) {
        $aiResponse = trim($res['choices'][0]['message']['content']);
        $usedProvider = 'openrouter_llama3';
    }
}

if (empty($aiResponse)) {
    $aiResponse = generateLocalRealEstateResponse($message, $userName, $phaseId, $userLevel);
}

$needsSupport = (bool)preg_match('/(no puedo|no tengo informaci[oó]n|no he podido|no dispongo|no s[eé] responder|fuera de mi alcance)/iu', $aiResponse);
$supportDraft = $needsSupport ? ['category'=>'Consulta sobre Vera / Compra Captación','message'=>'Consulta para el equipo: '.$message] : null;

echo json_encode([
    'ok' => true,
    'response' => $aiResponse,
    'suggested_action' => $suggestedAction,
    'next_step' => $nextStep,
    'support_draft' => $supportDraft,
    'provider' => $usedProvider,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
exit;

function callVeraJsonEndpoint($url, $payload, $headers = [], $timeout = 8) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) return [];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $requestHeaders = array_merge(['Content-Type: application/json'], $headers);

    // Método 1: cURL (si la extensión está disponible)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode >= 200 && $httpCode < 300 && $res) {
            $decoded = json_decode($res, true);
            if (is_array($decoded)) return $decoded;
        }
    }

    // Método 2: stream_context (fallback seguro)
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $requestHeaders) . "\r\nContent-Length: " . strlen($jsonPayload) . "\r\n",
            'content' => $jsonPayload,
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $res = @file_get_contents($url, false, $context);
    if ($res !== false && $res !== '') {
        $decoded = json_decode($res, true);
        return is_array($decoded) ? $decoded : [];
    }

    return [];
}

function generateLocalRealEstateResponse($msg, $name, $phaseId = '', $userLevel = 'junior') {
    $m = function_exists('mb_strtolower') ? mb_strtolower($msg, 'UTF-8') : strtolower($msg);
    $n = htmlspecialchars($name);

    // 1. Libro Mayor y Créditos (Prioridad Alta)
    if (strpos($m, 'libro mayor') !== false || strpos($m, 'libro_mayor') !== false || strpos($m, 'mayor') !== false || strpos($m, 'saldo') !== false || strpos($m, 'credito') !== false || strpos($m, 'crédito') !== false || strpos($m, 'recarga') !== false) {
        return "Mira {$n}, el Libro Mayor es básicamente el registro donde quedan anotados todos tus movimientos de créditos: cuántos has gastado, cuándo los recargaste y en qué operación los usaste.\n\n" .
               "Para que lo tengas claro, empiezas con 3 créditos de bienvenida que puedes usar durante los primeros 30 días para desbloquear contactos y tantear cómo funciona todo. Cada vez que consumes o recargas uno, queda registrado con la fecha, la referencia del inmueble y el concepto.\n\n" .
               "Lo puedes consultar en cualquier momento desde tu panel privado, en la sección de Créditos. ¿Necesitas que te eche una mano con algo más de este tema?";
    }

    // 2. Seguridad Jurídica, Acuerdo Marco y NDA (Prioridad Alta)
    if (strpos($m, 'acuerdo') !== false || strpos($m, 'nda') !== false || strpos($m, 'contrato') !== false || strpos($m, 'jurid') !== false || strpos($m, 'puente') !== false) {
        return "Entiendo perfectamente tu preocupación, {$n}. Es lo más normal del mundo querer saber cómo queda protegida tu parte antes de compartir datos con otra agencia.\n\n" .
               "Funciona así: antes de que nadie vea la dirección exacta ni datos privados del propietario, las dos agencias firmáis digitalmente un Acuerdo de Colaboración con cláusula de confidencialidad. Esto deja constancia de quién aportó la propiedad y quién al comprador, así que si la operación se cierra, el reparto 50/50 queda blindado desde el primer momento.\n\n" .
               "O sea, nadie puede puentearte porque todo queda registrado antes de desbloquear nada. ¿Te queda alguna duda sobre las cláusulas?";
    }

    // 3. Datos Ciegos y Protección de Exclusivas
    if (strpos($m, 'dato ciego') !== false || strpos($m, 'datos ciegos') !== false || strpos($m, 'direccion') !== false || strpos($m, 'dirección') !== false || strpos($m, 'calle') !== false || strpos($m, 'catastro') !== false || strpos($m, 'fachada') !== false) {
        return "Te lo explico rápido, {$n}. Cuando subes una captación a la plataforma, lo que se muestra públicamente es solo el municipio, el barrio, las características del piso y el rango de precio. Nada más.\n\n" .
               "La calle, el número, el piso, la puerta, el catastro y cualquier foto que identifique el edificio quedan ocultos automáticamente. La dirección exacta solo se desbloquea cuando la otra agencia firma el acuerdo 50/50 contigo.\n\n" .
               "Así que tu exclusiva está protegida al 100% hasta que tú decidas colaborar. ¿Quieres que revisemos juntos alguna ficha para ver cómo queda?";
    }

    // 4. Calculadora de Comisiones (si hay cifras o pide simulación)
    if (preg_match('/(\d+[\d.,]*)\s*(€|eur|euros?|k)/i', $msg, $matches) || (strpos($m, 'comision') !== false && (strpos($m, 'cuanto') !== false || strpos($m, 'reparto') !== false || strpos($m, 'calcul') !== false))) {
        $rawNum = preg_replace('/[^\d]/', '', $matches[1] ?? '210000');
        $price = intval($rawNum);
        if ($price < 1000 && $price > 0) $price = $price * 1000;
        if ($price <= 0) $price = 210000;
        
        $comm = round($price * 0.03);
        $share = round($comm / 2);

        return "¡Vamos a hacer números, {$n}! Para una operación de " . number_format($price, 0, ',', '.') . " €, si aplicamos un 3% estándar de honorarios, estamos hablando de " . number_format($comm, 0, ',', '.') . " € brutos en total.\n\n" .
               "Al repartirse al 50/50, te quedarían " . number_format($share, 0, ',', '.') . " € limpios para tu agencia y otros " . number_format($share, 0, ',', '.') . " € para la agencia colaboradora. Todo queda pactado por escrito antes de compartir ningún dato, así que cada parte sabe exactamente lo que se lleva.\n\n" .
               "¿Quieres que simulemos otro importe o ajustemos el porcentaje?";
    }

    // 5. Perfil Profesional y Verificación
    if (strpos($m, 'perfil') !== false || strpos($m, 'verific') !== false || strpos($m, 'nif') !== false || strpos($m, 'cif') !== false || strpos($m, 'logo') !== false || in_array($phaseId, ['phase-2-profile', 'step-2-profile', 'profile'], true)) {
        return "Te comento, {$n}: completar tu perfil es de lo primero que conviene hacer porque es lo que genera confianza cuando otra agencia ve tus publicaciones. Si ven un perfil verificado con nombre comercial, NIF y logotipo, la tasa de respuesta sube bastante, te lo digo por experiencia.\n\n" .
               "Básicamente necesitas rellenar tu nombre comercial, el NIF o CIF fiscal, seleccionar las zonas donde operas habitualmente y subir tu logotipo. Son dos minutos y marca la diferencia.\n\n" .
               "¿Entramos a completarlo ahora o prefieres hacerlo luego?";
    }

    // 6. Publicar Captación
    if (strpos($m, 'captacion') !== false || strpos($m, 'captación') !== false || strpos($m, 'subir inmueble') !== false || strpos($m, 'publicar propiedad') !== false || in_array($phaseId, ['phase-3-captation', 'publish-captation'], true)) {
        return "Mira, {$n}, para compartir una captación la clave es muy sencilla: describe los puntos fuertes del inmueble como la superficie, las habitaciones, si tiene terraza o garaje, la zona aproximada y el rango de precio.\n\n" .
               "Lo que nunca debes poner es la dirección exacta, el número del portal, el piso o fotos donde se reconozca la fachada. Eso lo protegemos nosotros automáticamente para que nadie pueda identificar el edificio antes de firmar el acuerdo contigo.\n\n" .
               "Así atraes a las agencias que ya tienen compradores cualificados sin arriesgar tu exclusiva. ¿Te ayudo a redactar la descripción del inmueble?";
    }

    // 7. Publicar Demanda
    if (strpos($m, 'demanda') !== false || strpos($m, 'comprador') !== false || strpos($m, 'buscar cliente') !== false || in_array($phaseId, ['phase-4-demand', 'publish-demand'], true)) {
        return "Si tienes un comprador con capacidad real de compra, {$n}, registrar su demanda es la forma más directa de encontrarle vivienda a través de la red.\n\n" .
               "Solo necesitas indicar el presupuesto máximo, qué tipo de vivienda busca y en qué zonas le interesa. A partir de ahí, nuestro sistema cruza esa búsqueda en tiempo real con todas las captaciones protegidas que hay publicadas. Cuando aparezca algo que encaje, te aviso para que puedas solicitar la colaboración.\n\n" .
               "¿Empezamos a registrar la búsqueda de tu cliente?";
    }

    // 8. Coincidencias y Matching
    if (strpos($m, 'coincidencia') !== false || strpos($m, 'cruce') !== false || strpos($m, 'matching') !== false || strpos($m, 'afinidad') !== false || strpos($m, 'porcentaje') !== false || in_array($phaseId, ['phase-5-matches', 'matches'], true)) {
        return "Te cuento, {$n}: el porcentaje de afinidad que ves en cada cruce es una medida de lo bien que encaja la demanda de tu cliente con la captación de otra agencia. Tiene en cuenta el precio, la zona, la tipología y las características principales.\n\n" .
               "Cuando ves un cruce por encima del 75%, merece la pena echarle un vistazo porque la coincidencia en los criterios clave es bastante buena. Puedes abrir la ficha ciega para revisar los detalles sin gastar ningún crédito, y si te convence, entonces sí solicitas la colaboración.\n\n" .
               "¿Quieres que miremos alguno de tus cruces activos juntos?";
    }

    // 9. Cierre y Operaciones
    if (strpos($m, 'cierre') !== false || strpos($m, 'arras') !== false || strpos($m, 'notaria') !== false || strpos($m, 'notaría') !== false || in_array($phaseId, ['phase-7-closing', 'closing'], true)) {
        return "¡Genial que estés en esta fase, {$n}! Cuando la operación llega a reserva o arras, lo ideal es que registres el expediente en tu panel de Operaciones para tener todo controlado.\n\n" .
               "Desde ahí puedes hacer seguimiento de cada hito: la documentación, si hay hipoteca en curso, la fecha de notaría y, lo más importante, la liquidación del 50% de honorarios con la otra agencia. Todo queda trazado para que no haya malentendidos.\n\n" .
               "¿Necesitas que revisemos alguna de tus operaciones en marcha?";
    }

    // 10. Orientación MLS 50/50 y Modelo General
    if (strpos($m, 'mls') !== false || strpos($m, '50/50') !== false || strpos($m, 'compracaptacion') !== false || strpos($m, 'que es') !== false || in_array($phaseId, ['phase-1-orientation', 'orientation'], true)) {
        return "Te lo explico de forma sencilla, {$n}. Compra Captación es una red donde las agencias inmobiliarias colaboran para cerrar ventas que por separado no podrían. La idea es simple: tú tienes el piso pero no el comprador, o al revés. Aquí os encontráis.\n\n" .
               "Los honorarios se reparten siempre al 50/50, pactados por escrito antes de compartir ningún dato privado. Tus captaciones se publican con datos ciegos, o sea, se muestra la zona, las características y el precio, pero la calle y el catastro quedan ocultos hasta que firméis el acuerdo.\n\n" .
               "Además, empiezas con 3 créditos de cortesía para que puedas probar cómo funciona todo sin compromiso. ¿Quieres que te acompañe paso a paso o prefieres explorar por tu cuenta?";
    }

    // Respuesta general
    return "¡Hola {$n}! Soy Vera, encantada de ayudarte. Dime en qué puedo echarte una mano: si necesitas orientación para publicar una captación, registrar una demanda de un comprador, entender cómo funcionan los cruces o resolver cualquier duda operativa, aquí estoy.\n\n" .
           "Pregúntame lo que necesites, sin prisa 😊";
}

