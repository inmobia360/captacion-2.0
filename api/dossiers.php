<?php
/** Enlaces privados de dossier post-operación, revocables y con caducidad. */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
$db = CaptacionDB::get(); $action = $_GET['action'] ?? 'create';

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = require_auth(); $operationId = (int)($_GET['operation_id'] ?? 0);
    $stmt = $db->prepare("SELECT id, operation_id, record_id, expires_at, revoked_at, created_at, last_accessed_at FROM dossier_access_tokens WHERE operation_id=? AND created_by=? ORDER BY id DESC");
    $stmt->execute([$operationId, (int)$user['id']]);
    echo json_encode(['ok'=>true,'tokens'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth(); $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $operationId = (int)($input['operation_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM operations WHERE id=? AND status='closed' AND contract_signed=1 AND captador_signed=1 AND colaborador_signed=1 AND (captador_user_id=? OR colaborador_user_id=?) LIMIT 1");
    $stmt->execute([$operationId,(int)$user['id'],(int)$user['id']]); $op = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$op) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo se puede crear un dossier para una operación cerrada y firmada.']); exit; }
    $raw = bin2hex(random_bytes(32)); $hash = hash('sha256',$raw); $expires = date('Y-m-d H:i:s', time()+7*86400);
    $db->prepare("INSERT INTO dossier_access_tokens (token_hash,operation_id,record_id,created_by,expires_at) VALUES (?,?,?,?,?)")->execute([$hash,$operationId,(int)$op['record_id'],(int)$user['id'],$expires]);
    echo json_encode(['ok'=>true,'token'=>$raw,'expires_at'=>$expires,'url'=>'/dossier.php?id='.(int)$op['record_id'].'&token='.$raw]); exit;
}

if ($action === 'revoke' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth(); $input = json_decode(file_get_contents('php://input'), true) ?: $_POST; $tokenId = (int)($input['token_id'] ?? 0);
    $stmt=$db->prepare("UPDATE dossier_access_tokens SET revoked_at=CURRENT_TIMESTAMP WHERE id=? AND created_by=? AND revoked_at IS NULL"); $stmt->execute([$tokenId,(int)$user['id']]);
    echo json_encode(['ok'=>$stmt->rowCount()>0,'status'=>$stmt->rowCount()>0?'revoked':'not_found']); exit;
}

http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Acción no válida.']);
