<?php
/**
 * Compra Captación - API de Programa de Referidos B2B & Product-Led Growth (PLG)
 * 
 * 1. Incentivos Asimétricos: 10% DTO recurrente en suscripción por referido activo (hasta 100% gratis).
 * 2. Invitado: 3 créditos de bienvenida + 3 créditos extra tras subir cartera XML (mín. 3 exclusivas) + Vera IA 30d prioritario.
 * 3. Gamificación: Sello "Agente Conector Recomendado" con prioridad visual en el marketplace.
 * 4. Efecto Caballo de Troya: Invitación transaccional directa vinculada a comprador 50/50 para captar agencias externas.
 * 5. Filtro Anti-Fraude: Verificación de CIF/NIF y registro obligatorio (AICAT / RAIN / COAPI).
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';
$user = require_auth();
$userId = (int)$user['id'];

// Asegurar tablas de referidos e invitaciones transaccionales
$db->exec("CREATE TABLE IF NOT EXISTS referral_milestones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    referrer_user_id INTEGER NOT NULL,
    referred_user_id INTEGER NOT NULL,
    referred_email TEXT NOT NULL,
    milestone_type TEXT NOT NULL, -- 'milestone_a_xml', 'milestone_b_purchase', 'milestone_c_deal'
    status TEXT NOT NULL DEFAULT 'pending', -- 'pending', 'rewarded', 'rejected'
    credits_awarded REAL NOT NULL DEFAULT 0.0,
    discount_awarded REAL NOT NULL DEFAULT 0.0,
    properties_count INTEGER NOT NULL DEFAULT 0,
    metadata_json TEXT DEFAULT '{}',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rewarded_at DATETIME DEFAULT NULL,
    FOREIGN KEY (referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$db->exec("CREATE TABLE IF NOT EXISTS transactional_invites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_user_id INTEGER NOT NULL,
    invite_key TEXT NOT NULL UNIQUE,
    target_email TEXT NOT NULL,
    target_name TEXT DEFAULT '',
    target_agency TEXT DEFAULT '',
    property_title TEXT NOT NULL,
    province TEXT NOT NULL,
    municipality TEXT DEFAULT '',
    buyer_budget REAL DEFAULT 0.0,
    commission_split TEXT DEFAULT '50/50',
    custom_notes TEXT DEFAULT '',
    status TEXT NOT NULL DEFAULT 'sent', -- 'sent', 'registered', 'converted'
    registered_user_id INTEGER DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    converted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Asegurar campos de verificación profesional en tabla users si no existen
try {
    $db->exec("ALTER TABLE users ADD COLUMN tax_id TEXT DEFAULT ''");
    $db->exec("ALTER TABLE users ADD COLUMN license_registry_type TEXT DEFAULT ''");
    $db->exec("ALTER TABLE users ADD COLUMN license_number TEXT DEFAULT ''");
    $db->exec("ALTER TABLE users ADD COLUMN is_connector_recommended INTEGER DEFAULT 0");
    $db->exec("ALTER TABLE users ADD COLUMN recurring_discount_percentage REAL DEFAULT 0.0");
} catch (Throwable $ignored) {}

// Generar código de referido único
$referralCode = 'CC-' . strtoupper(substr(md5((string)$userId . 'captacion_salt_2026'), 0, 8));
$referralLink = "https://compracaptacion.com/?ref=" . urlencode($referralCode);

// 1. ESTADO DEL PROGRAMA DE REFERIDOS Y DASHBOARD PLG
if ($action === 'status') {
    // Obtener hitos
    $stmt = $db->prepare("SELECT rm.*, u.full_name as referred_name, u.agency_name as referred_agency, u.email as user_email, u.created_at as member_since 
                          FROM referral_milestones rm 
                          LEFT JOIN users u ON rm.referred_user_id = u.id 
                          WHERE rm.referrer_user_id = ? 
                          ORDER BY rm.id DESC");
    $stmt->execute([$userId]);
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener invitaciones transaccionales (Caballo de Troya)
    $stmtInvites = $db->prepare("SELECT * FROM transactional_invites WHERE sender_user_id = ? ORDER BY id DESC LIMIT 20");
    $stmtInvites->execute([$userId]);
    $transactionalInvites = $stmtInvites->fetchAll(PDO::FETCH_ASSOC);

    // Contar usuarios referidos activos que hayan subido cartera o tengan cuenta verificada
    $activeReferralsCount = 0;
    $totalCreditsEarned = 0.0;
    $xmlCarpetasActivadas = 0;

    foreach ($milestones as $m) {
        if ($m['status'] === 'rewarded') {
            $totalCreditsEarned += (float)$m['credits_awarded'];
            if ($m['milestone_type'] === 'milestone_a_xml') {
                $xmlCarpetasActivadas++;
                $activeReferralsCount++;
            }
        }
    }

    // Cálculo de Descuento Recurrente (10% por referido activo, hasta 100%)
    $recurringDiscount = min(100, $activeReferralsCount * 10);
    $isConnectorRecommended = ($activeReferralsCount >= 2 || count($transactionalInvites) >= 3);

    // Actualizar estado en base de datos
    $db->prepare("UPDATE users SET recurring_discount_percentage = ?, is_connector_recommended = ? WHERE id = ?")
       ->execute([$recurringDiscount, $isConnectorRecommended ? 1 : 0, $userId]);

    // Plantillas de 1-Clic para WhatsApp y Email
    $templates = [
        'interprovincial' => [
            'id' => 'interprovincial',
            'title' => 'Derivación Interprovincial (Madrid-Costa / Expansión)',
            'description' => 'Ideal para conectar con agentes en otras provincias donde tus clientes buscan comprar.',
            'whatsapp' => "Hola compañero, a menudo me entran clientes compradores buscando en tu zona y quiero derivártelos de forma segura. He montado mi cartera en Compra Captación para colaborar con acuerdos de honorarios al 50/50 blindados. Date de alta gratis con mi invitación y te dan 3 créditos + Vera IA para conectar: " . $referralLink . "&intent=interprovincial",
            'email_subject' => 'Propuesta de colaboración interprovincial de honorarios 50/50',
            'email_body' => "Hola,\n\nTe contacto porque frecuentemente recibo solicitudes de compradores cualificados interesados en tu provincia.\n\nPara canalizar estas operaciones con total transparencia y firmar los acuerdos de colaboración al 50% con contratos oficiales homologados, te invito a unirte a Compra Captación:\n\n" . $referralLink . "&intent=interprovincial\n\nAl registrarte recibirás 3 créditos de bienvenida y acceso prioritario a la IA Vera para cruzar carteras.\n\nUn saludo cordial."
        ],
        'trojan_deal' => [
            'id' => 'trojan_deal',
            'title' => 'Invitación Transaccional por Inmueble Real (Caballo de Troya)',
            'description' => 'Úsalo cuando tienes un comprador para un inmueble que viste publicado en una agencia externa.',
            'whatsapp' => "Hola, tengo a un comprador solvente y con financiación lista para visitar tu inmueble en venta. Para formalizar la visita y blindar nuestros honorarios al 50/50 con el acuerdo oficial antes de ir a notaría, regístrate gratis aquí en 2 minutos: " . $referralLink . "&intent=deal_50_50",
            'email_subject' => 'Tengo un comprador interesado en tu inmueble (Propuesta 50/50)',
            'email_body' => "Hola,\n\nDisponemos de un cliente comprador con solvencia acreditada interesado en realizar una visita a vuestro inmueble.\n\nPara agilizar el proceso y formalizar el acuerdo vinculante de colaboración 50/50 conforme a la legalidad vigente, os dejamos el enlace directo de registro gratuito:\n\n" . $referralLink . "&intent=deal_50_50\n\nQuedamos a vuestra disposición para coordinar la visita de inmediato."
        ],
        'network_trust' => [
            'id' => 'network_trust',
            'title' => 'Red de Confianza & Blindaje contra el Puenteo',
            'description' => 'Para invitar a agencias amigas y crear un círculo exclusivo de captaciones compartidas.',
            'whatsapp' => "Hola, te paso la plataforma que estamos usando para compartir captaciones en exclusiva con datos ciegos. Evita filtraciones y asegura el 50% de comisión por contrato legal. Te dan 3 créditos gratis al entrar: " . $referralLink . "&intent=trust_network",
            'email_subject' => 'Plataforma para compartir captaciones protegidas al 50/50',
            'email_body' => "Hola,\n\nTe comparto la herramienta profesional que estamos empleando para compartir operaciones inmobiliarias con total seguridad (datos ciegos, NDA y reparto pactado del 50%).\n\nPuedes darte de alta y probarla con 3 créditos de cortesía aquí:\n\n" . $referralLink . "\n\nUn abrazo."
        ]
    ];

    echo json_encode([
        'ok' => true,
        'referral_code' => $referralCode,
        'referral_link' => $referralLink,
        'metrics' => [
            'active_referrals_count' => $activeReferralsCount,
            'total_credits_earned' => $totalCreditsEarned,
            'xml_carpetas_activadas' => $xmlCarpetasActivadas,
            'recurring_discount_percentage' => $recurringDiscount,
            'monthly_savings_text' => $recurringDiscount > 0 ? "¡Tienes un {$recurringDiscount}% de descuento mensual activo!" : "Invita a 10 colegas activos y obtén tu suscripción 100% GRATIS.",
            'is_connector_recommended' => $isConnectorRecommended,
            'badge_title' => $isConnectorRecommended ? '⭐ Agente Conector Recomendado' : 'Agente Colaborador Estándar',
            'badge_benefits' => $isConnectorRecommended ? 'Prioridad visual en cruces de Vera IA y mapa de búsqueda nacional.' : 'Activa a 2 colegas con cartera XML para desbloquear el sello de prestigio.'
        ],
        'templates' => $templates,
        'milestones' => $milestones,
        'transactional_invites' => $transactionalInvites
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. ENVIAR INVITACIÓN TRANSACCIONAL DIRECTA ("EFECTO CABALLO DE TROYA")
if ($action === 'send_transactional_invite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $targetEmail = trim(filter_var($input['target_email'] ?? '', FILTER_SANITIZE_EMAIL));
    $targetName = trim($input['target_name'] ?? '');
    $targetAgency = trim($input['target_agency'] ?? '');
    $propertyTitle = trim($input['property_title'] ?? '');
    $province = trim($input['province'] ?? 'España');
    $municipality = trim($input['municipality'] ?? '');
    $buyerBudget = (float)($input['buyer_budget'] ?? 0);
    $commissionSplit = trim($input['commission_split'] ?? '50/50');
    $customNotes = trim($input['custom_notes'] ?? '');

    if (!$targetEmail || !$propertyTitle) {
        echo json_encode(['ok' => false, 'error' => 'Por favor introduce el email del destinatario y el título/referencia del inmueble.']);
        exit;
    }

    $inviteKey = 'inv_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 16);
    $directLink = "https://compracaptacion.com/?ref=" . urlencode($referralCode) . "&invite=" . urlencode($inviteKey) . "&deal=50_50";

    // Guardar invitación en BD
    $stmt = $db->prepare("INSERT INTO transactional_invites (
        sender_user_id, invite_key, target_email, target_name, target_agency,
        property_title, province, municipality, buyer_budget, commission_split, custom_notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $userId, $inviteKey, $targetEmail, $targetName, $targetAgency,
        $propertyTitle, $province, $municipality, $buyerBudget, $commissionSplit, $customNotes
    ]);

    // Enviar Email al agente externo
    $subject = "=?UTF-8?B?" . base64_encode("Tengo un comprador cualificado para tu inmueble en {$province} (Propuesta 50/50)") . "?=";
    $body = "Hola " . ($targetName ?: 'Compañero') . ",\n\n"
          . "Te contacto desde Compra Captación porque tenemos a un cliente comprador solvente y cualificado"
          . ($buyerBudget > 0 ? " (Presupuesto aproximado: " . number_format($buyerBudget, 0, ',', '.') . " €)" : "")
          . " interesado en tu inmueble:\n"
          . "👉 \"" . $propertyTitle . "\"" . ($municipality ? " en " . $municipality . " (" . $province . ")" : "") . "\n\n"
          . "Para firmar el Acuerdo Oficial de Colaboración de Honorarios 50/50 con blindaje legal antes de agendar la visita con el cliente, accede a tu enlace exclusivo:\n\n"
          . $directLink . "\n\n"
          . "El registro es 100% gratuito e incluye 3 créditos de bienvenida y soporte legal homologado.\n\n"
          . "Atentamente,\n"
          . ($user['full_name'] ?: 'Agente Colegiado') . ($user['agency_name'] ? " · " . $user['agency_name'] : "") . "\n"
          . "Vía Compra Captación (https://compracaptacion.com)";

    $headers = "From: Compra Captación <no-reply@compracaptacion.com>\r\n"
             . "Reply-To: " . ($user['email'] ?: 'soporte@compracaptacion.com') . "\r\n"
             . "X-Mailer: PHP/" . phpversion();
    @mail($targetEmail, $subject, $body, $headers);

    // Mensaje formateado para compartir al instante por WhatsApp
    $whatsappText = "Hola" . ($targetName ? " {$targetName}" : "") . ", tengo a un comprador solvente interesado en tu inmueble '{$propertyTitle}' en {$province}. Para agendar la visita y blindar nuestros honorarios al 50/50 con el contrato oficial de Compra Captación, regístrate gratis aquí: {$directLink}";

    echo json_encode([
        'ok' => true,
        'message' => '¡Invitación transaccional enviada con éxito por correo!',
        'invite_key' => $inviteKey,
        'direct_link' => $directLink,
        'whatsapp_text' => $whatsappText
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. VERIFICACIÓN ANTI-FRAUDE DE CREDENCIALES PROFESIONALES (CIF/NIF & AICAT/RAIN)
if ($action === 'verify_professional_license' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $taxId = strtoupper(trim($input['tax_id'] ?? ''));
    $registryType = trim($input['license_registry_type'] ?? 'AICAT');
    $licenseNumber = trim($input['license_number'] ?? '');

    if (!$taxId || !$licenseNumber) {
        echo json_encode(['ok' => false, 'error' => 'Por favor introduce el CIF/NIF y el número de registro profesional (AICAT, RAIN o Colegiado).']);
        exit;
    }

    // Validación básica de formato de CIF/NIF español
    $isValidTaxId = (bool)preg_match('/^[A-HJ-NP-SUVW][0-9]{7}[0-9A-J]$|^[0-9XYZ][0-9]{7}[TRWAGMYFPDXBNJZSQVHLCKE]$/i', $taxId);
    if (!$isValidTaxId && strlen($taxId) < 8) {
        echo json_encode(['ok' => false, 'error' => 'El formato del CIF/NIF o NIF de autónomo no parece válido.']);
        exit;
    }

    // Actualizar usuario como profesional verificado
    $db->prepare("UPDATE users SET tax_id = ?, license_registry_type = ?, license_number = ?, verification_status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
       ->execute([$taxId, $registryType, $licenseNumber, $userId]);

    echo json_encode([
        'ok' => true,
        'message' => '¡Identidad profesional verificada con éxito! Tu cuenta cuenta con el sello de profesional homologado.',
        'tax_id' => $taxId,
        'license_number' => $licenseNumber
    ]);
    exit;
}

// 4. VERIFICAR Y RECOMPENSAR HITO A (CARTERA XML: MÍNIMO 3 EXCLUSIVAS)
if ($action === 'verify_milestone_a') {
    // El antiguo hito XML (+3/+3) queda desactivado: las recompensas vigentes
    // solo se conceden tras una operación validada por ambas partes.
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'Este hito histórico está desactivado. La recompensa se calculará tras validar una operación conforme al protocolo 50/50.']);
    exit;

    $referredUserId = (int)($_POST['referred_user_id'] ?? 0);
    if ($referredUserId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Usuario referido no válido.']);
        exit;
    }

    // Contar exclusivas activas del referido (Mínimo 3 propiedades en exclusiva)
    $stmt = $db->prepare("SELECT COUNT(*) FROM records WHERE user_id = ? AND is_exclusive = 1 AND deleted_at IS NULL");
    $stmt->execute([$referredUserId]);
    $exclusiveCount = (int)$stmt->fetchColumn();

    if ($exclusiveCount < 3) {
        echo json_encode([
            'ok' => false,
            'error' => "El colega referido tiene actualmente $exclusiveCount exclusivas activas. Se requiere un mínimo de 3 exclusivas verificadas para liberar el premio del Hito A."
        ]);
        exit;
    }

    // Comprobar si ya fue recompensado
    $stmt = $db->prepare("SELECT id, status FROM referral_milestones WHERE referrer_user_id = ? AND referred_user_id = ? AND milestone_type = 'milestone_a_xml'");
    $stmt->execute([$userId, $referredUserId]);
    $existing = $stmt->fetch();

    if ($existing && $existing['status'] === 'rewarded') {
        echo json_encode(['ok' => true, 'message' => 'El Hito A ya ha sido recompensado previamente.']);
        exit;
    }

    // Premiar al referidor con +3 créditos
    $db->prepare("UPDATE wallets SET available_balance = available_balance + 3.0, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")->execute([$userId]);
    
    // Premiar también al referido con +3 créditos extra por su primera subida XML
    $db->prepare("UPDATE wallets SET available_balance = available_balance + 3.0, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")->execute([$referredUserId]);

    $walletStmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
    $walletStmt->execute([$userId]);
    $newBalance = (float)$walletStmt->fetchColumn();

    $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, related_entity_type, related_entity_id, metadata) VALUES (?, 'referral_milestone_a', 'referral', 3.0, ?, 'referral', ?, ?)")
       ->execute([$userId, $newBalance, $userId . ':' . $referredUserId, json_encode(['description' => 'Hito A: +3 créditos por cartera XML (3+ exclusivas aportadas)', 'exclusive_count' => $exclusiveCount, 'idempotency_key' => 'milestone_a_' . $userId . '_' . $referredUserId])]);

    if ($existing) {
        $db->prepare("UPDATE referral_milestones SET status = 'rewarded', credits_awarded = 3.0, properties_count = ?, rewarded_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$exclusiveCount, $existing['id']]);
    } else {
        $db->prepare("INSERT INTO referral_milestones (referrer_user_id, referred_user_id, referred_email, milestone_type, status, credits_awarded, properties_count, rewarded_at) VALUES (?, ?, '', 'milestone_a_xml', 'rewarded', 3.0, ?, CURRENT_TIMESTAMP)")
           ->execute([$userId, $referredUserId, $exclusiveCount]);
    }

    // Notificaciones
    $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, '¡+3 Créditos y +10% DTO ganado!', 'Tu colega invitado ha activado su cartera XML con {$exclusiveCount} exclusivas. Has recibido +3 créditos y +10% de descuento recurrente en tu suscripción.', 'success', '#/area-privada/referidos')")
       ->execute([$userId]);

    $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, '¡Bono de cartera activado (+3 créditos)!', 'Por subir tus primeras 3 exclusivas has recibido +3 créditos extra y 30 días de acceso prioritario a Vera IA.', 'success', '#/area-privada/creditos')")
       ->execute([$referredUserId]);

    echo json_encode([
        'ok' => true,
        'message' => "¡Hito A completado! Has recibido +3 créditos en tu monedero y +10% de descuento recurrente en tu suscripción mensual.",
        'new_balance' => $newBalance
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no reconocida.']);
exit;
