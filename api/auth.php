<?php
/**
 * Compra Captación - API de Autenticación, Roles y Verificación
 */
require_once __DIR__ . '/database.php';

// Puente de sesión seguro para el Panel Premium: solo permite el origen exacto del subdominio.
$premiumOrigin = 'https://pro.compracaptacion.com';
if (($_SERVER['HTTP_ORIGIN'] ?? '') === $premiumOrigin) {
    header('Access-Control-Allow-Origin: ' . $premiumOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '.compracaptacion.com',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Defensa CSRF de capa transversal para peticiones con cookies de sesión.
// Las peticiones sin Origin (CLI/webhook interno) siguen siendo compatibles;
// cualquier navegador cross-origin no autorizado queda bloqueado.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $requestOrigin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    $requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($requestOrigin !== '') {
        $originHost = strtolower((string)(parse_url($requestOrigin, PHP_URL_HOST) ?: ''));
        $allowedHosts = [$requestHost, 'compracaptacion.com', 'www.compracaptacion.com', 'pro.compracaptacion.com', 'crm.compracaptacion.com'];
        if (!in_array($originHost, $allowedHosts, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'Origen no autorizado.']);
            exit;
        }
    }
}

function get_auth_user(): ?array {
    $db = CaptacionDB::get();
    if (!empty($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT id, email, full_name, agency_name, cif_nif, phone, license_number, province, municipality, role, verification_status, email_verified, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $bearerToken = '';
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $bearerToken = trim($matches[1]);
    }
    $token = trim($_SERVER['HTTP_X_USER_TOKEN'] ?? $bearerToken);

    if ($token !== '' && strlen($token) >= 16) {
        $stmt = $db->prepare("SELECT id, email, full_name, agency_name, cif_nif, phone, license_number, province, municipality, role, verification_status, email_verified, avatar_url FROM users WHERE verification_token = ? AND verification_token != ''");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

function require_auth(): array {
    $user = get_auth_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado. Por favor inicia sesión.']);
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_auth();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Acceso denegado. Se requieren permisos de administrador.']);
        exit;
    }
    return $user;
}

// Router de acciones (solo ejecutar si auth.php es el script principal invocado)
$isDirectAuthCall = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'auth.php' || basename($_SERVER['PHP_SELF'] ?? '') === 'auth.php' || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'auth.php') !== false);

if ($isDirectAuthCall) {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'me') {
            $user = get_auth_user();
            if ($user) {
                $db = CaptacionDB::get();
            $stmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $wallet = $stmt->fetch();
            $user['credits'] = $wallet ? (float)$wallet['available_balance'] : 0.0;
            echo json_encode(['ok' => true, 'authenticated' => true, 'user' => $user]);
        } else {
            echo json_encode(['ok' => true, 'authenticated' => false, 'user' => null]);
        }
        exit;
    }

    if ($action === 'validate_reset_token') {
        $token = trim($_GET['token'] ?? '');
        if (!$token || strlen($token) < 16) {
            echo json_encode(['ok' => false, 'valid' => false, 'error' => 'Token no proporcionado o formato inválido.']);
            exit;
        }
        $db = CaptacionDB::get();
        $stmt = $db->prepare("SELECT id, email, full_name, password_reset_expires_at FROM users WHERE password_reset_token = ? AND password_reset_token != ''");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['ok' => false, 'valid' => false, 'error' => 'El enlace de recuperación es inválido o ya ha sido utilizado.']);
            exit;
        }

        $expiresAt = strtotime($user['password_reset_expires_at'] ?? '1970-01-01');
        if ($expiresAt < time()) {
            echo json_encode(['ok' => false, 'valid' => false, 'error' => 'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.']);
            exit;
        }

        // Token válido
        echo json_encode([
            'ok' => true,
            'valid' => true,
            'email' => $user['email'],
            'full_name' => $user['full_name']
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $db = CaptacionDB::get();

    if ($action === 'register') {
        $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $input['password'] ?? '';
        $fullName = trim($input['full_name'] ?? '');
        $agencyName = trim($input['agency_name'] ?? '');
        $cifNif = trim($input['cif_nif'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $role = in_array($input['role'] ?? '', ['professional', 'agency']) ? $input['role'] : 'professional';
        $province = trim($input['province'] ?? '');
        $municipality = trim($input['municipality'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Introduce un correo electrónico válido.']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
            exit;
        }

        // Verificar si ya existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'Este correo electrónico ya está registrado.']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(24));

        // Registrar usuario pendiente de activación por email
        $licenseRegistryType = trim($input['license_registry_type'] ?? '');
        $licenseNumber = trim($input['license_number'] ?? '');
        $taxId = strtoupper(trim($input['tax_id'] ?? $cifNif));
        $referralCode = strtoupper(trim($input['referral_code'] ?? ''));

        $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, agency_name, cif_nif, tax_id, license_registry_type, license_number, phone, province, municipality, role, verification_status, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, ?)");
        $stmt->execute([$email, $passwordHash, $fullName, $agencyName, $taxId, $taxId, $licenseRegistryType, $licenseNumber, $phone, $province, $municipality, $role, $token]);
        $newUserId = (int)$db->lastInsertId();

        // Inicializar monedero con 3 créditos de bienvenida
        $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance, total_granted) VALUES (?, 3.0, 3.0)")->execute([$newUserId]);

        // Vincular código de referido si existe
        if ($referralCode) {
            $stmtReferrer = $db->query("SELECT id FROM users");
            $allUsers = $stmtReferrer->fetchAll();
            $matchedReferrerId = null;
            foreach ($allUsers as $u) {
                $generatedCode = 'CC-' . strtoupper(substr(md5((string)$u['id'] . 'captacion_salt_2026'), 0, 8));
                if ($generatedCode === $referralCode && (int)$u['id'] !== $newUserId) {
                    $matchedReferrerId = (int)$u['id'];
                    break;
                }
            }

            if ($matchedReferrerId) {
                $db->prepare("INSERT INTO referral_milestones (referrer_user_id, referred_user_id, referred_email, milestone_type, status, metadata_json) VALUES (?, ?, ?, 'milestone_a_xml', 'pending', ?)")
                   ->execute([$matchedReferrerId, $newUserId, $email, json_encode(['registered_at' => date('Y-m-d H:i:s'), 'source' => 'web_registration'])]);
            }
        }

        // Enviar email de activación
        $activationUrl = "https://compracaptacion.com/api/auth.php?action=verify_email&token=" . urlencode($token);
        $subject = "=?UTF-8?B?" . base64_encode("Activa tu cuenta en Compra Captación (+3 créditos de bienvenida / 30 días)") . "?=";
        $message = "Hola " . ($fullName ?: 'Profesional') . ",\n\n"
                 . "Gracias por registrarte en Compra Captación.\n\n"
                 . "Para activar tu cuenta profesional y desbloquear tus 3 créditos gratis de bienvenida (válidos durante tus primeros 30 días, no acumulables), haz clic en el siguiente enlace:\n\n"
                 . $activationUrl . "\n\n"
                 . "Si no has solicitado esta cuenta, puedes ignorar este mensaje de forma segura.\n\n"
                 . "Atentamente,\n"
                 . "El equipo de Compra Captación\nhttps://compracaptacion.com";
        $headers = "From: Compra Captación <no-reply@compracaptacion.com>\r\n"
                 . "Reply-To: soporte@compracaptacion.com\r\n"
                 . "X-Mailer: PHP/" . phpversion();
        @mail($email, $subject, $message, $headers);

        // Registrar auditoría
        $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'register_pending_email', ?, ?, ?)")
           ->execute([$newUserId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['role' => $role, 'email' => $email])]);

        echo json_encode([
            'ok' => true,
            'requires_email_verification' => true,
            'message' => 'Te hemos enviado un enlace a tu correo para activar tu cuenta y desbloquear los 3 créditos de bienvenida (válidos 30 días).'
        ]);
        exit;
    }

    if ($action === 'verify_email') {
        $token = trim($_GET['token'] ?? '');
        if (!$token) {
            header("Location: /#/login?error=" . urlencode("Token de activación no válido"));
            exit;
        }

        $stmt = $db->prepare("SELECT id, email, full_name, agency_name, role, email_verified FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            header("Location: /#/login?error=" . urlencode("El enlace de activación ha expirado o no es válido"));
            exit;
        }

        if (!$user['email_verified']) {
            $db->prepare("UPDATE users SET email_verified = 1, verification_status = 'approved', verification_token = NULL WHERE id = ?")->execute([$user['id']]);

            // Asignar bono de bienvenida (3 créditos / 30 días, no acumulables)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            $db->prepare("INSERT INTO wallets (user_id, available_balance) VALUES (?, 3.0) ON DUPLICATE KEY UPDATE available_balance = available_balance + 3.0")->execute([$user['id']]);
            $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, related_entity_type, related_entity_id, metadata) VALUES (?, 'bonus', 'welcome_bonus', 3.0, 3.0, 'welcome', ?, ?)")
               ->execute([$user['id'], 'welcome_' . $user['id'] . '_' . time(), json_encode(['description' => 'Bono de bienvenida de 3 créditos (válido 30 días, no acumulable)', 'validity_days' => 30, 'expires_at' => $expiresAt, 'cumulative' => false])]);
        }

        $_SESSION['user_id'] = (int)$user['id'];
        header("Location: /#/area-privada?activated=1");
        exit;
    }

    if ($action === 'login') {
        $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $input['password'] ?? '';

        $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rateStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_failed' AND ip_address = ? AND created_at >= datetime('now', '-15 minutes')");
        $rateStmt->execute([$remoteIp]);
        if ((int)$rateStmt->fetchColumn() >= 10) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, email, password_hash, full_name, agency_name, cif_nif, phone, role, staff_category, verification_status, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (0, 'login_failed', ?, ?, ?)")
               ->execute([$remoteIp, $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['email_hash' => hash('sha256', strtolower($email))])]);
            echo json_encode(['ok' => false, 'error' => 'Credenciales incorrectas. Comprueba tu correo y contraseña.']);
            exit;
        }

        if ($user['verification_status'] === 'suspended') {
            echo json_encode(['ok' => false, 'error' => 'Tu cuenta se encuentra suspendida por moderación. Contacta con soporte.']);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];

        // Obtener créditos y normalizar saldos antiguos de bienvenida
        $stmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $wallet = $stmt->fetch();
        if ($wallet && (float)$wallet['available_balance'] == 250.0 && $user['role'] !== 'admin') {
            $db->prepare("UPDATE wallets SET available_balance = 3.0, total_granted = 3.0, expires_at = datetime('now', '+30 days'), cumulative = 0 WHERE user_id = ?")->execute([$user['id']]);
            $wallet['available_balance'] = 3.0;
        }
        $user['credits'] = $wallet ? (float)$wallet['available_balance'] : 3.0;
        unset($user['password_hash']);

        $isMasterPro = (($user['staff_category'] ?? '') === 'master_pro');
        $planType = ($user['role'] === 'admin' || $user['role'] === 'agency' || $isMasterPro) ? 'premium' : 'professional_plus';

        echo json_encode([
            'ok' => true,
            'message' => 'Inicio de sesión correcto.',
            'displayName' => $user['full_name'] ?: $user['email'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'businessName' => $user['agency_name'] ?? 'Compra Captación VIP',
            'profileType' => ($user['role'] === 'admin' || $user['role'] === 'agency' || $isMasterPro) ? 'agency' : 'independent',
            'profileComplete' => true,
            'accessState' => [
                'has_access' => true,
                'plan_type' => $planType,
                'plan_name' => ($user['role'] === 'admin') ? 'SuperAdmin VIP (Acceso Total)' : (($isMasterPro) ? 'Master Pro' : 'Plan Agencia Pro'),
                'is_unlimited' => ($user['role'] === 'admin' || $isMasterPro),
                'unlocked_records' => []
            ],
            'user' => $user
        ]);
        exit;
    }

    if ($action === 'logout') {
        $_SESSION = [];
        if (session_id()) session_destroy();
        echo json_encode(['ok' => true, 'message' => 'Sesión cerrada correctamente.']);
        exit;
    }

    if ($action === 'complete_onboarding') {
        $user = require_auth();
        $db->prepare("UPDATE users SET onboarding_completed = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);
        echo json_encode(['ok' => true, 'message' => 'Onboarding completado con éxito.']);
        exit;
    }

    if ($action === 'update_profile') {
        $user = require_auth();
        $fullName = trim($input['full_name'] ?? $user['full_name']);
        $agencyName = trim($input['agency_name'] ?? $user['agency_name']);
        $cifNif = trim($input['cif_nif'] ?? $user['cif_nif']);
        $phone = trim($input['phone'] ?? $user['phone']);
        $license = trim($input['license_number'] ?? '');
        $province = trim($input['province'] ?? '');
        $municipality = trim($input['municipality'] ?? '');

        $stmt = $db->prepare("UPDATE users SET full_name = ?, agency_name = ?, cif_nif = ?, phone = ?, license_number = ?, province = ?, municipality = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$fullName, $agencyName, $cifNif, $phone, $license, $province, $municipality, $user['id']]);

        echo json_encode(['ok' => true, 'message' => 'Perfil profesional actualizado correctamente.']);
        exit;
    }

    if ($action === 'reset_password' || $action === 'request_password_reset') {
        $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $source = trim($input['source'] ?? 'crm');
        $redirectTo = trim($input['redirect_to'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Introduce un correo electrónico válido.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, email, full_name, agency_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generar token criptográfico único con validez de 60 minutos
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora

            $stmtUpdate = $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?");
            $stmtUpdate->execute([$resetToken, $expiresAt, $user['id']]);

            // Construir URL de recuperación
            $baseUrl = ($source === 'crm' || strpos($_SERVER['HTTP_HOST'] ?? '', 'crm.') !== false) 
                ? 'https://crm.compracaptacion.com/' 
                : 'https://compracaptacion.com/';
            
            $resetUrl = $redirectTo ?: ($baseUrl . '?reset_token=' . urlencode($resetToken));

            // Enviar email transaccional seguro
            $subject = "=?UTF-8?B?" . base64_encode("Restablece tu contraseña en Compra Captación") . "?=";
            $userName = $user['full_name'] ?: ($user['agency_name'] ?: 'Profesional');
            $message = "Hola {$userName},\n\n"
                     . "Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Compra Captación.\n\n"
                     . "Para crear una nueva contraseña segura, haz clic en el siguiente enlace:\n\n"
                     . $resetUrl . "\n\n"
                     . "Este enlace de seguridad es de un solo uso y expirará en 60 minutos.\n\n"
                     . "Si tú no has solicitado este cambio, puedes ignorar este mensaje de forma segura. Tu contraseña actual seguirá siendo la misma.\n\n"
                     . "Atentamente,\n"
                     . "El equipo de Compra Captación\nhttps://compracaptacion.com";
            $headers = "From: Compra Captación <no-reply@compracaptacion.com>\r\n"
                     . "Reply-To: soporte@compracaptacion.com\r\n"
                     . "X-Mailer: PHP/" . phpversion();
            @mail($email, $subject, $message, $headers);

            // Auditoría
            $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'password_reset_requested', ?, ?, ?)")
               ->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['email' => $email, 'source' => $source])]);
        }

        // Respuesta uniforme neutra (timing attack & email enumeration protection)
        echo json_encode([
            'ok' => true,
            'message' => 'Si el correo electrónico está registrado, recibirás un enlace de recuperación seguro en tu bandeja de entrada.'
        ]);
        exit;
    }

    if ($action === 'confirm_password_reset') {
        $token = trim($input['token'] ?? '');
        $password = (string)($input['password'] ?? '');
        $passwordConfirm = (string)($input['password_confirm'] ?? $input['password'] ?? '');

        if (!$token || strlen($token) < 16) {
            echo json_encode(['ok' => false, 'error' => 'El enlace o token de recuperación no es válido.']);
            exit;
        }

        if (strlen($password) < 8) {
            echo json_encode(['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
            exit;
        }

        if ($password !== $passwordConfirm) {
            echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden. Por favor, revísalas.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, email, full_name, password_reset_expires_at FROM users WHERE password_reset_token = ? AND password_reset_token != ''");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['ok' => false, 'error' => 'El enlace de recuperación es inválido o ya fue utilizado.']);
            exit;
        }

        $expiresAt = strtotime($user['password_reset_expires_at'] ?? '1970-01-01');
        if ($expiresAt < time()) {
            echo json_encode(['ok' => false, 'error' => 'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.']);
            exit;
        }

        // Actualizar contraseña y revocar token
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $stmtUpdate = $db->prepare("UPDATE users SET password_hash = ?, password_reset_token = '', password_reset_expires_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmtUpdate->execute([$newHash, $user['id']]);

        // Revocar sesiones activas previas
        if (session_id()) {
            session_destroy();
        }

        // Registrar en auditoría
        $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'password_reset_completed', ?, ?, ?)")
           ->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['email' => $user['email']])]);

        echo json_encode([
            'ok' => true,
            'message' => '¡Tu contraseña se ha restablecido correctamente! Ya puedes iniciar sesión con tu nueva contraseña.'
        ]);
        exit;
    }
}
}
