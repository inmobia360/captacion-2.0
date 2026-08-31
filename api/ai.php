<?php
/**
 * Compra Captación - Asistente IA Inmobiliaria Vera (llama-3.3-70b-versatile)
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'chat';
$user = get_auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $message = trim($input['message'] ?? '');
    $contextType = $input['context'] ?? 'general'; // general, valuation, copywriting, matching

    if (!$message) {
        echo json_encode(['ok' => false, 'error' => 'Por favor introduce una consulta para Vera.']);
        exit;
    }

    $groqKey = getenv('GROQ_API_KEY') ?: 'gsk_demo';
    $model = 'llama-3.3-70b-versatile';

    $systemPrompt = "Eres Vera, la asistente de Inteligencia Artificial experta en el sector inmobiliario español de Compra Captación (compracaptacion.com).
Tu misión es asesorar a agentes inmobiliarios, agencias y APIs en:
1. Colaboraciones 50/50 de honorarios compartidos y compraventas de captaciones 100%.
2. Valoración de inmuebles y fijación de precios competitivos en municipios españoles.
3. Redacción de títulos y descripciones atractivas que maximicen el contacto de compradores.
4. Cruce inteligente entre cartera de captaciones y demandas activas de compradores.
5. Asesoramiento sobre acuerdos de confidencialidad (NDA), hojas de encargo y seguridad jurídica.
Responde de forma concisa, profesional, cercana y con datos precisos del mercado inmobiliario español.";

    // Intento de conexión con Groq / OpenAI endpoint compatible con llama-3.3-70b-versatile
    $aiResponse = null;
    if ($groqKey && $groqKey !== 'gsk_demo') {
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $groqKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.6,
            'max_tokens' => 800
        ]));
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $data = json_decode($res, true);
            $aiResponse = $data['choices'][0]['message']['content'] ?? null;
        }
    }

    // Fallback inteligente contextual si no hay clave Groq externa activa
    if (!$aiResponse) {
        $msgLower = strtolower($message);
        if (strpos($msgLower, '50/50') !== false || strpos($msgLower, 'colabora') !== false || strpos($msgLower, 'honorario') !== false) {
            $aiResponse = "En Compra Captación la colaboración 50/50 funciona de forma protegida: publicas tu captación con los datos sensibles ocultos. Cuando otro agente solicita colaborar, reserva 1 crédito durante 72 horas; la colaboración se acepta y ambas partes firman la misma versión del acuerdo antes de habilitar los datos de contacto o compartir documentación sensible.";
        } elseif (strpos($msgLower, 'precio') !== false || strpos($msgLower, 'valor') !== false || strpos($msgLower, 'tasar') !== false) {
            $aiResponse = "Para estimar el precio óptimo de captación te sugiero analizar los testigos recientes en la misma zona registral, ajustar según estado de conservación, orientación y planta, y calcular un margen de negociación del 3% al 5% sobre el precio de publicación.";
        } elseif (strpos($msgLower, 'descrip') !== false || strpos($msgLower, 'texto') !== false || strpos($msgLower, 'anuncio') !== false) {
            $aiResponse = "Un anuncio de alto impacto debe estructurarse en 3 bloques: 1) Gancho con la propuesta única (ej: 'Ático reformado con luz natural todo el día y terraza orientada a sur'), 2) Distribución y características clave con viñetas claras, 3) Condiciones de colaboración 50/50 y llamada a la acción para agencias colaboradoras.";
        } else {
            $aiResponse = "¡Hola! Soy Vera, tu asistente inmobiliaria en Compra Captación con motor Llama-3.3-70B. ¿En qué puedo ayudarte hoy? Puedo ayudarte a redactar una captación, calcular honorarios de colaboración 50/50 o redactar las condiciones de una operación.";
        }
    }

    echo json_encode([
        'ok' => true,
        'model' => $model,
        'reply' => $aiResponse
    ]);
    exit;
}
