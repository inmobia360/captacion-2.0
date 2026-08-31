<?php
/**
 * Compra Captación CRM - Auth & RBAC Guard para Staff HQ
 * Control de acceso de 1 solo Master Admin y Categorías de Staff de Operaciones
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'me';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$ALLOWED_STAFF_CATEGORIES = [
    'staff_operaciones' => 'Agente de Operaciones',
    'staff_agente_operaciones' => 'Agente de Operaciones',
    'staff_gerente' => 'Gerente de Operaciones',
    'staff_editor' => 'Editor y Moderador de Cartera',
    'staff_financiero' => 'Gestor Financiero y Liquidaciones',
    'staff_matching' => 'Gestor de Demandas y Matching 50/50',
    'staff_integraciones' => 'Especialista en Feeds XML e Integraciones CRM',
    'staff_soporte' => 'Gestor de Soporte y Atención a Agencias'
];

function get_current_staff(PDO $db): ?array {
    $userId = $_SESSION['staff_user_id'] ?? $_SESSION['admin_user_id'] ?? null;
    if (!$userId) {
        return null;
    }
    $stmt = $db->prepare("SELECT id, email, full_name, phone, role, staff_category, verification_status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user && ($user['role'] === 'admin' || $user['role'] === 'staff') && $user['verification_status'] === 'approved') {
        return $user;
    }
    return null;
}

function require_staff_auth(PDO $db): array {
    $staff = get_current_staff($db);
    if ($staff) {
        return $staff;
    }
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sesión de Staff requerida.']);
    exit;
}

function check_is_master_admin(array $user): bool {
    $email = strtolower(trim((string)($user['email'] ?? '')));
    if ($email === 'inmobia360@gmail.com' || $email === 'inmobia360@mail.com' || $email === 'admin@compracaptacion.com') {
        return true;
    }
    if (($user['staff_category'] ?? '') === 'master_admin') {
        return true;
    }
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }
    return false;
}

// 1. OBTENER SESIÓN ACTUAL
if ($action === 'me') {
    $staff = get_current_staff($db);
    if ($staff) {
        $isMaster = check_is_master_admin($staff);
        echo json_encode([
            'ok' => true,
            'authenticated' => true,
            'user' => [
                'id' => (int)$staff['id'],
                'email' => $staff['email'],
                'full_name' => $staff['full_name'],
                'phone' => $staff['phone'] ?? '',
                'role' => $isMaster ? 'admin' : $staff['role'],
                'staff_category' => $isMaster ? 'master_admin' : ($staff['staff_category'] ?: 'staff_gerente'),
                'is_master_admin' => $isMaster
            ]
        ]);
    } else {
        echo json_encode(['ok' => false, 'authenticated' => false]);
    }
    exit;
}

// 2. ACCESO / LOGIN DE STAFF
if ($action === 'login') {
    $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = (string)($input['password'] ?? '');

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Introduce tu email y contraseña.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, email, password_hash, full_name, phone, role, staff_category, verification_status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // El acceso del Master Admin se configura únicamente mediante entorno.
    $masterAdminEmail = (string)(getenv('CAPTACION_MASTER_ADMIN_EMAIL') ?: '');
    $masterAdminPassword = (string)(getenv('CAPTACION_MASTER_ADMIN_PASSWORD') ?: '');
    if ($masterAdminEmail !== '' && $masterAdminPassword !== '' && $email === $masterAdminEmail) {
        if (hash_equals($masterAdminPassword, $password)) {
            $masterPass = password_hash($masterAdminPassword, PASSWORD_BCRYPT);
            if (!$user) {
                $db->prepare("INSERT INTO users (email, password_hash, full_name, agency_name, role, staff_category, verification_status, email_verified) VALUES (?, ?, 'Master Admin', 'Compra Captación Central HQ', 'admin', 'master_admin', 'approved', 1)")
                   ->execute([$email, $masterPass]);
                $userId = (int)$db->lastInsertId();
            } else {
                $userId = (int)$user['id'];
                $db->prepare("UPDATE users SET password_hash = ?, role = 'admin', staff_category = 'master_admin', verification_status = 'approved', email_verified = 1 WHERE id = ?")
                   ->execute([$masterPass, $userId]);
            }
            $user = [
                'id' => $userId,
                'email' => $email,
                'password_hash' => $masterPass,
                'full_name' => 'Master Admin',
                'phone' => '+34 600 000 000',
                'role' => 'admin',
                'staff_category' => 'master_admin',
                'verification_status' => 'approved'
            ];
        }
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (0, 'staff_login_failed', ?, ?, ?)")
           ->execute([$_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['email' => $email, 'reason' => 'invalid_credentials'])]);

        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Credenciales no válidas para el Portal Staff.']);
        exit;
    }

    if ($user['role'] !== 'admin' && $user['role'] !== 'staff') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Esta cuenta no pertenece al Staff de operaciones de Compra Captación.']);
        exit;
    }

    if ($user['verification_status'] === 'pending') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Tu solicitud de cuenta Staff está pendiente de aprobación por el Administrador Maestro.']);
        exit;
    }

    if ($user['verification_status'] === 'suspended') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Tu acceso de Staff ha sido desactivado temporalmente. Contacta con Dirección.']);
        exit;
    }

    $_SESSION['staff_user_id'] = (int)$user['id'];
    $_SESSION['admin_user_id'] = (int)$user['id'];
    $_SESSION['user_id'] = (int)$user['id'];

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'staff_login_success', ?, ?, ?)")
       ->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['email' => $email, 'role' => $user['role'], 'category' => $user['staff_category']])]);

    $isMaster = check_is_master_admin($user);
    echo json_encode([
        'ok' => true,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'phone' => $user['phone'] ?? '',
            'role' => $isMaster ? 'admin' : $user['role'],
            'staff_category' => $isMaster ? 'master_admin' : ($user['staff_category'] ?: 'staff_gerente'),
            'is_master_admin' => $isMaster
        ]
    ]);
    exit;
}

// 3. SOLICITUD DE ALTA / CREACIÓN DE CUENTA STAFF SEGÚN CATEGORÍA
if ($action === 'register_staff') {
    $fullName = trim((string)($input['full_name'] ?? ''));
    $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $phone = trim((string)($input['phone'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $staffCategory = trim((string)($input['staff_category'] ?? 'staff_gerente'));

    if (!$fullName || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Todos los campos marcados con (*) son obligatorios.']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'La contraseña debe contener al menos 8 caracteres.']);
        exit;
    }

    if (!isset($ALLOWED_STAFF_CATEGORIES[$staffCategory])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Categoría de trabajo de Staff no válida.']);
        exit;
    }

    // Regla de Oro: Solo 1 Master Admin en todo el sistema (exclusivo inmobia360@mail.com)
    if ($staffCategory === 'master_admin' && $email !== 'inmobia360@mail.com') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'La categoría de Administrador Maestro está reservada exclusivamente para la Dirección General.']);
        exit;
    }

    // Comprobar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Ya existe una cuenta registrada con este correo electrónico.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $role = ($staffCategory === 'master_admin') ? 'admin' : 'staff';
    $status = ($staffCategory === 'master_admin') ? 'approved' : 'pending';

    $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, phone, agency_name, role, staff_category, verification_status, email_verified) VALUES (?, ?, ?, ?, 'Staff Compra Captación HQ', ?, ?, ?, 1)");
    $stmt->execute([$email, $hash, $fullName, $phone, $role, $staffCategory, $status]);
    $newId = (int)$db->lastInsertId();

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'staff_registered', ?, ?, ?)")
       ->execute([$newId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['email' => $email, 'category' => $staffCategory, 'status' => $status])]);

    if ($status === 'approved') {
        $_SESSION['staff_user_id'] = $newId;
        $_SESSION['admin_user_id'] = $newId;
        echo json_encode([
            'ok' => true,
            'message' => '¡Cuenta de Administrador Maestro creada e iniciada con éxito!',
            'auto_login' => true
        ]);
    } else {
        echo json_encode([
            'ok' => true,
            'message' => 'Tu solicitud de cuenta Staff ha sido registrada. El Administrador Maestro revisará y activará tu acceso a la brevedad.',
            'auto_login' => false
        ]);
    }
    exit;
}

// 4. CIERRE DE SESIÓN STAFF
if ($action === 'logout') {
    unset($_SESSION['staff_user_id'], $_SESSION['admin_user_id'], $_SESSION['user_id']);
    session_destroy();
    echo json_encode(['ok' => true, 'message' => 'Sesión cerrada correctamente.']);
    exit;
}

// 5. EDICIÓN DE PERFIL STAFF
if ($action === 'update_profile') {
    $staff = require_staff_auth($db);
    $fullName = trim($input['full_name'] ?? $staff['full_name']);
    $phone = trim($input['phone'] ?? ($staff['phone'] ?? ''));
    $newPassword = (string)($input['new_password'] ?? '');

    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
            exit;
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$fullName, $phone, $hash, $staff['id']]);
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$fullName, $phone, $staff['id']]);
    }

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'staff_profile_updated', ?, ?, ?)")
       ->execute([$staff['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['full_name' => $fullName])]);

    echo json_encode([
        'ok' => true,
        'message' => 'Perfil de Staff actualizado correctamente.',
        'user' => [
            'id' => (int)$staff['id'],
            'email' => $staff['email'],
            'full_name' => $fullName,
            'phone' => $phone,
            'role' => $staff['role'],
            'staff_category' => $staff['staff_category']
        ]
    ]);
    exit;
}

// 6. GESTIÓN DE SOLICITUDES PENDIENTES (SOLO MASTER ADMIN)
if ($action === 'list_pending_staff') {
    $staff = require_staff_auth($db);
    if ($staff['role'] !== 'admin' && $staff['staff_category'] !== 'master_admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Permiso denegado. Solo el Administrador Maestro puede aprobar personal.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, email, full_name, phone, role, staff_category, verification_status, created_at FROM users WHERE role = 'staff' AND verification_status = 'pending' ORDER BY id DESC");
    $stmt->execute();
    $pending = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'pending_staff' => $pending]);
    exit;
}

if ($action === 'set_staff_status') {
    $staff = require_staff_auth($db);
    if ($staff['role'] !== 'admin' && $staff['staff_category'] !== 'master_admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Permiso denegado.']);
        exit;
    }

    $targetId = (int)($input['user_id'] ?? 0);
    $status = (string)($input['status'] ?? 'approved'); // approved | suspended | rejected

    if ($status === 'rejected') {
        $db->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'")->execute([$targetId]);
    } else {
        $db->prepare("UPDATE users SET verification_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND role = 'staff'")->execute([$status, $targetId]);
    }

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'staff_status_changed', ?, ?, ?)")
       ->execute([$staff['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['target_id' => $targetId, 'status' => $status])]);

    echo json_encode(['ok' => true, 'message' => 'Estado de Staff actualizado.']);
    exit;
}
