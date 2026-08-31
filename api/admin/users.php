<?php
/**
 * Compra Captación CRM - User Management & Master Admin Moderation
 * Gestión integral de usuarios: Creación, Edición, Eliminación, Suspensión,
 * Reactivación, Recuperación de contraseña y Ajuste de Saldos.
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
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// 1. VERIFICACIÓN DE SESIÓN STAFF Y PERMISOS DE MASTER ADMIN
$staffUserId = $_SESSION['staff_user_id'] ?? $_SESSION['admin_user_id'] ?? $_SESSION['user_id'] ?? null;
$isMasterAdmin = false;
$currentStaff = null;

if ($staffUserId) {
    $stmt = $db->prepare("SELECT id, email, role, staff_category, verification_status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$staffUserId]);
    $currentStaff = $stmt->fetch();
    if ($currentStaff) {
        $email = strtolower(trim((string)($currentStaff['email'] ?? '')));
        if ($email === 'inmobia360@gmail.com' || $email === 'inmobia360@mail.com' || $email === 'admin@compracaptacion.com') {
            $isMasterAdmin = true;
        } elseif (($currentStaff['staff_category'] ?? '') === 'master_admin' || ($currentStaff['role'] ?? '') === 'admin') {
            $isMasterAdmin = true;
        }
    }
}

// 2. LISTADO DE USUARIOS
if ($action === 'list') {
    $search = trim($_GET['q'] ?? '');
    $sql = "SELECT u.id, u.email, u.full_name, u.agency_name, u.cif_nif, u.phone, u.role, u.staff_category, u.verification_status, u.email_verified, u.created_at, COALESCE(w.available_balance, 0) as credits 
            FROM users u 
            LEFT JOIN wallets w ON u.id = w.user_id";
    $params = [];
    if ($search) {
        $sql .= " WHERE u.email LIKE ? OR u.full_name LIKE ? OR u.agency_name LIKE ? OR u.cif_nif LIKE ? OR u.phone LIKE ?";
        $term = "%$search%";
        $params = [$term, $term, $term, $term, $term];
    }
    $sql .= " ORDER BY u.id DESC LIMIT 200";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    echo json_encode([
        'ok' => true,
        'users' => $users,
        'is_master_admin' => $isMasterAdmin
    ]);
    exit;
}

// 3. CREACIÓN DE NUEVO USUARIO (EXCLUSIVO MASTER ADMIN)
if ($action === 'create_user') {
    if (!$isMasterAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro (Master Admin) tiene permisos para crear usuarios manualmente.']);
        exit;
    }

    $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $fullName = trim((string)($input['full_name'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $phone = trim((string)($input['phone'] ?? ''));
    $agencyName = trim((string)($input['agency_name'] ?? ''));
    $cifNif = trim((string)($input['cif_nif'] ?? ''));
    $role = (string)($input['role'] ?? 'professional');
    $staffCategory = trim((string)($input['staff_category'] ?? ''));
    $allowedStaffCategories = ['', 'master_pro', 'staff_operaciones', 'staff_gerente', 'staff_editor', 'staff_financiero', 'staff_matching', 'staff_integraciones', 'staff_soporte'];
    if (!in_array($staffCategory, $allowedStaffCategories, true)) $staffCategory = '';
    $credits = max(0.0, (float)($input['credits'] ?? 10.0));
    $status = (string)($input['status'] ?? 'approved');

    if (!$email || !$fullName || !$password) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Nombre, correo electrónico y contraseña son campos obligatorios.']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'La contraseña debe contener al menos 6 caracteres.']);
        exit;
    }

    if (!in_array($role, ['professional', 'agency', 'staff', 'admin'])) {
        $role = 'professional';
    }

    if (!in_array($status, ['approved', 'pending', 'suspended'])) {
        $status = 'approved';
    }

    // Comprobar email duplicado
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Ya existe un usuario registrado con este correo electrónico.']);
        exit;
    }

    $pwdHash = password_hash($password, PASSWORD_BCRYPT);

    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, agency_name, cif_nif, phone, role, staff_category, verification_status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$email, $pwdHash, $fullName, $agencyName, $cifNif, $phone, $role, $staffCategory, $status]);
    $newUserId = (int)$db->lastInsertId();

    $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance, total_granted) VALUES (?, ?, ?)")
       ->execute([$newUserId, $credits, $credits]);

    if ($credits > 0) {
        $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, metadata) VALUES (?, 'master_admin_grant', 'crm_master', ?, ?, ?)")
           ->execute([$newUserId, $credits, $credits, json_encode(['granted_by' => $currentStaff['email'] ?? 'Master Admin'])]);
    }

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'user_created_by_master', ?, ?, ?)")
       ->execute([$currentStaff['id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['created_user_id' => $newUserId, 'email' => $email, 'role' => $role, 'category' => $staffCategory])]);

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => "Usuario '$fullName' ($email) creado con éxito con $credits créditos iniciales.",
        'user' => [
            'id' => $newUserId,
            'email' => $email,
            'full_name' => $fullName,
            'agency_name' => $agencyName,
            'cif_nif' => $cifNif,
            'phone' => $phone,
            'role' => $role,
            'staff_category' => $staffCategory,
            'verification_status' => $status,
            'credits' => $credits
        ]
    ]);
    exit;
}

// 4. EDICIÓN DE USUARIO (EXCLUSIVO MASTER ADMIN)
if ($action === 'update_user') {
    if (!$isMasterAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro (Master Admin) tiene permisos para modificar datos de usuarios.']);
        exit;
    }

    $userId = (int)($input['user_id'] ?? 0);
    $email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $fullName = trim((string)($input['full_name'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $agencyName = trim((string)($input['agency_name'] ?? ''));
    $cifNif = trim((string)($input['cif_nif'] ?? ''));
    $role = (string)($input['role'] ?? 'professional');
    $staffCategory = trim((string)($input['staff_category'] ?? ''));
    $allowedStaffCategories = ['', 'master_pro', 'staff_operaciones', 'staff_gerente', 'staff_editor', 'staff_financiero', 'staff_matching', 'staff_integraciones', 'staff_soporte'];
    if (!in_array($staffCategory, $allowedStaffCategories, true)) $staffCategory = '';
    $status = (string)($input['status'] ?? 'approved');
    $newPassword = (string)($input['new_password'] ?? '');

    if (!$userId || !$email || !$fullName) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de usuario, correo y nombre son obligatorios.']);
        exit;
    }

    // Verificar si el email pertenece a otro usuario
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'El correo electrónico ya está en uso por otra cuenta.']);
        exit;
    }

    $db->beginTransaction();

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            exit;
        }
        $pwdHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET email = ?, full_name = ?, phone = ?, agency_name = ?, cif_nif = ?, role = ?, staff_category = ?, verification_status = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$email, $fullName, $phone, $agencyName, $cifNif, $role, $staffCategory, $status, $pwdHash, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE users SET email = ?, full_name = ?, phone = ?, agency_name = ?, cif_nif = ?, role = ?, staff_category = ?, verification_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$email, $fullName, $phone, $agencyName, $cifNif, $role, $staffCategory, $status, $userId]);
    }

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'user_updated_by_master', ?, ?, ?)")
       ->execute([$currentStaff['id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['target_user_id' => $userId, 'email' => $email, 'role' => $role, 'status' => $status])]);

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => "Datos del usuario #$userId actualizados correctamente."
    ]);
    exit;
}

// 5. ELIMINACIÓN DE USUARIO (EXCLUSIVO MASTER ADMIN)
if ($action === 'delete_user') {
    if (!$isMasterAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro (Master Admin) puede eliminar usuarios.']);
        exit;
    }

    $userId = (int)($input['user_id'] ?? 0);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de usuario no válido.']);
        exit;
    }

    // Proteger cuenta del Master Admin de ser eliminada
    $stmt = $db->prepare("SELECT email, role, staff_category FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'El usuario no existe.']);
        exit;
    }

    if ($targetUser['staff_category'] === 'master_admin' || $targetUser['email'] === 'inmobia360@mail.com') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No es posible eliminar la cuenta principal de Master Admin.']);
        exit;
    }

    $db->beginTransaction();
    $db->prepare("DELETE FROM wallets WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM records WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM support_tickets WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'user_deleted_by_master', ?, ?, ?)")
       ->execute([$currentStaff['id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['deleted_user_id' => $userId, 'email' => $targetUser['email']])]);

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => "Usuario #$userId ({$targetUser['email']}) eliminado permanentemente de la plataforma."
    ]);
    exit;
}

// 6. ENVIAR RECUPERACIÓN / RESET DE CONTRASEÑA (EXCLUSIVO MASTER ADMIN)
if ($action === 'send_password_reset') {
    if (!$isMasterAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro puede emitir enlaces de recuperación de contraseña.']);
        exit;
    }

    $userId = (int)($input['user_id'] ?? 0);
    $stmt = $db->prepare("SELECT id, email, full_name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado.']);
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600 * 24); // 24 horas

    $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?")
       ->execute([$token, $expiresAt, $userId]);

    $resetUrl = "https://compracaptacion.com/#restablecer-password?token=" . $token;

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'password_reset_generated_by_master', ?, ?, ?)")
       ->execute([$currentStaff['id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['target_user_id' => $userId, 'email' => $targetUser['email']])]);

    echo json_encode([
        'ok' => true,
        'message' => "Enlace de recuperación generado para {$targetUser['email']}.",
        'reset_url' => $resetUrl,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);
    exit;
}

// 7. AJUSTAR SALDO DE CRÉDITOS
if ($action === 'adjust_credits') {
    $userId = (int)($input['user_id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    $reason = trim((string)($input['reason'] ?? 'Ajuste administrativo'));

    if (!$userId || $amount == 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Usuario y cantidad válida requeridos.']);
        exit;
    }

    $db->beginTransaction();
    $stmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();

    if ($existing !== false) {
        $db->prepare("UPDATE wallets SET available_balance = MAX(0.0, available_balance + ?), updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
           ->execute([$amount, $userId]);
    } else {
        $db->prepare("INSERT INTO wallets (user_id, available_balance, consumed_balance, pending_balance) VALUES (?, ?, 0.0, 0.0)")
           ->execute([$userId, max(0.0, $amount)]);
    }

    $wallet = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
    $wallet->execute([$userId]);
    $newBal = (float)$wallet->fetchColumn();

    $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, metadata) VALUES (?, 'admin_adjustment', 'crm_admin', ?, ?, ?)")
       ->execute([$userId, $amount, $newBal, json_encode(['reason' => $reason, 'by' => $currentStaff['email'] ?? 'Admin'])]);

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => "Saldo ajustado correctamente. Nuevo balance: $newBal créditos.",
        'new_balance' => $newBal
    ]);
    exit;
}

// 8. PAUSAR / SUSPENDER / REACTIVAR ESTADO INDIVIDUAL
if ($action === 'set_status' || $action === 'toggle_status') {
    if (!$isMasterAdmin && ($currentStaff['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro puede cambiar el estado de acceso de los usuarios.']);
        exit;
    }

    $userId = (int)($input['user_id'] ?? 0);
    $status = (string)($input['status'] ?? 'approved');

    if (!in_array($status, ['approved', 'suspended', 'pending'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Estado no válido.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET verification_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$status, $userId]);

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'user_status_changed_by_master', ?, ?, ?)")
       ->execute([$currentStaff['id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['target_user_id' => $userId, 'new_status' => $status])]);

    $statusLabel = ($status === 'approved') ? 'Activado / Aprobado' : (($status === 'suspended') ? 'Suspendido / Pausado' : 'Pendiente');

    echo json_encode([
        'ok' => true,
        'message' => "El estado del usuario #$userId ha sido cambiado a: $statusLabel.",
        'new_status' => $status
    ]);
    exit;
}

// 9. ACCIONES MASIVAS
if ($action === 'bulk_delete') {
    if (!$isMasterAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro puede ejecutar borrado masivo de usuarios.']);
        exit;
    }

    $userIds = $input['user_ids'] ?? [];
    if (!is_array($userIds) || empty($userIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Lista de IDs de usuario requerida.']);
        exit;
    }

    $cleanIds = array_filter(array_map('intval', $userIds), fn($id) => $id > 0);
    if (empty($cleanIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Ningún ID válido proporcionado.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $stmt = $db->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role != 'admin' AND staff_category != 'master_admin'");
    $stmt->execute($cleanIds);
    $affected = $stmt->rowCount();

    $db->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) VALUES (?, 'users_bulk_deleted', ?, ?, ?)")
       ->execute([$currentStaff['id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode(['count' => $affected, 'ids' => $cleanIds])]);

    echo json_encode([
        'ok' => true,
        'message' => "$affected usuario(s) eliminados correctamente.",
        'affected' => $affected
    ]);
    exit;
}

if ($action === 'bulk_status') {
    if (!$isMasterAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo el Administrador Maestro puede cambiar estados masivamente.']);
        exit;
    }

    $userIds = $input['user_ids'] ?? [];
    $newStatus = (string)($input['status'] ?? 'approved');

    if (!is_array($userIds) || empty($userIds) || !in_array($newStatus, ['approved', 'suspended', 'pending'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Parámetros no válidos.']);
        exit;
    }

    $cleanIds = array_filter(array_map('intval', $userIds), fn($id) => $id > 0);
    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $params = array_merge([$newStatus], $cleanIds);

    $stmt = $db->prepare("UPDATE users SET verification_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders) AND role != 'admin' AND staff_category != 'master_admin'");
    $stmt->execute($params);
    $affected = $stmt->rowCount();

    echo json_encode([
        'ok' => true,
        'message' => "$affected usuario(s) actualizados a estado '$newStatus'.",
        'affected' => $affected
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => "Acción '$action' no reconocida."]);


