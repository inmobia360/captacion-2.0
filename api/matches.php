<?php
/** Backend matching persistente e idempotente entre ofertas y demandas. */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$db = CaptacionDB::get();
$user = require_auth();
$action = $_GET['action'] ?? 'list';

function calculate_record_match(array $a, array $b): int {
    $score = 0;
    if (strcasecmp((string)$a['province'], (string)$b['province']) === 0) $score += 35;
    if ($a['municipality'] && strcasecmp((string)$a['municipality'], (string)$b['municipality']) === 0) $score += 20;
    if (strcasecmp((string)$a['property_type'], (string)$b['property_type']) === 0) $score += 15;
    $price = (float)$a['price']; $budget = (float)$b['price'];
    if ($price > 0 && $budget > 0) {
        $delta = abs($price - $budget) / max($price, $budget);
        if ($delta <= .10) $score += 30; elseif ($delta <= .20) $score += 20; elseif ($delta <= .30) $score += 10;
    }
    return min(100, $score);
}

if ($action === 'rebuild' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int)($_POST['record_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM records WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
    $stmt->execute([$recordId, (int)$user['id']]); $source = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$source) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Anuncio no encontrado.']); exit; }
    $opposite = $source['record_type'] === 'property' ? 'need' : 'property';
    $list = $db->prepare("SELECT * FROM records WHERE record_type = ? AND status = 'active' AND deleted_at IS NULL AND user_id != ?");
    $list->execute([$opposite, (int)$user['id']]);
    $created = 0;
    foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
        $score = calculate_record_match($source, $candidate);
        if ($score < 60) continue;
        $left = min($recordId, (int)$candidate['id']); $right = max($recordId, (int)$candidate['id']);
        $key = "match_{$left}_{$right}";
        $insert = $db->prepare("INSERT OR IGNORE INTO record_matches (record_id, matched_record_id, score, idempotency_key) VALUES (?, ?, ?, ?)");
        $insert->execute([$recordId, (int)$candidate['id'], $score, $key]);
        if ($insert->rowCount() > 0) {
            $created++;
            $db->prepare("INSERT INTO notifications (user_id,title,message,type,link) VALUES (?,?,?,?,?)")
               ->execute([(int)$candidate['user_id'], 'Nueva coincidencia compatible', "Hemos detectado una coincidencia del {$score}% con una oportunidad compatible.", 'info', '#/coincidencias-ventas']);
        }
    }
    echo json_encode(['ok'=>true,'created'=>$created]); exit;
}

if ($action === 'list') {
    $stmt = $db->prepare("SELECT rm.*, r.title AS record_title, m.title AS matched_title FROM record_matches rm JOIN records r ON r.id=rm.record_id JOIN records m ON m.id=rm.matched_record_id WHERE r.user_id=? OR m.user_id=? ORDER BY rm.score DESC, rm.created_at DESC LIMIT 100");
    $stmt->execute([(int)$user['id'], (int)$user['id']]);
    echo json_encode(['ok'=>true,'matches'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Acción no válida.']);
