<?php
/** Reputación profesional: métricas derivadas, nunca editables desde el cliente. */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$db = CaptacionDB::get();
$action = $_GET['action'] ?? $_POST['action'] ?? 'public';

function reputation_calculate(PDO $db, int $userId): array {
    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]); $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new RuntimeException('Professional not found');

    $profileFields = ['full_name', 'phone', 'province', 'municipality', 'license_number', 'tax_id'];
    $filled = 0; foreach ($profileFields as $field) if (trim((string)($user[$field] ?? '')) !== '') $filled++;
    $profileComplete = $filled === count($profileFields) && (int)($user['email_verified'] ?? 0) === 1;
    $profileRate = $filled / count($profileFields);

    $op = $db->prepare("SELECT COUNT(*) FROM operations WHERE (captador_user_id = ? OR colaborador_user_id = ?) AND status = 'closed'");
    $op->execute([$userId, $userId]); $completedOperations = (int)$op->fetchColumn();
    $accepted = $db->prepare("SELECT COUNT(*) FROM operations WHERE (captador_user_id = ? OR colaborador_user_id = ?) AND status IN ('agreed','in_progress','closed')");
    $accepted->execute([$userId, $userId]); $acceptedRequests = (int)$accepted->fetchColumn();

    $pub = $db->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN title != '' AND description_public != '' AND province != '' AND municipality != '' AND price > 0 THEN 1 ELSE 0 END) AS complete, MAX(updated_at) AS last_activity FROM records WHERE user_id = ? AND deleted_at IS NULL");
    $pub->execute([$userId]); $publication = $pub->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'complete'=>0,'last_activity'=>null];
    $publicationCompleteness = (int)$publication['total'] > 0 ? ((int)$publication['complete'] / (int)$publication['total']) : 0;

    $reviews = $db->prepare("SELECT COUNT(*) AS total, COALESCE(AVG(score), 0) AS average FROM professional_reviews WHERE subject_user_id = ? AND status = 'approved'");
    $reviews->execute([$userId]); $review = $reviews->fetch(PDO::FETCH_ASSOC);
    $reviewCount = (int)$review['total']; $reviewAverage = (float)$review['average'];

    $score = $profileComplete ? 15 : 0;
    $score += min(35, $completedOperations * 7);
    $score += min(15, $acceptedRequests * 3);
    $score += (int)round($publicationCompleteness * 15);
    $score += $reviewCount ? (int)round(min(10, ($reviewAverage / 5) * 10)) : 0;
    $score = max(0, min(100, $score));
    if (!$profileComplete) $category = 'limited_activity';
    elseif ($completedOperations >= 5 && $score >= 80) $category = 'featured_professional';
    elseif ($score >= 65) $category = 'verified_professional';
    elseif ($score >= 45) $category = 'active_professional';
    elseif ($score >= 15) $category = 'growing_professional';
    else $category = 'new_professional';

    $data = ['user_id'=>$userId,'score'=>$score,'category'=>$category,'profile_complete'=>$profileComplete?1:0,'completed_operations'=>$completedOperations,'accepted_requests'=>$acceptedRequests,'response_rate'=>0,'publication_completeness'=>round($publicationCompleteness*100,2),'verified_reviews_count'=>$reviewCount,'verified_reviews_average'=>round($reviewAverage,2),'relevant_matches'=>0,'incidents_count'=>0,'last_activity_at'=>$publication['last_activity'] ?: ($user['updated_at'] ?? null),'verification_badge'=>($user['verification_status'] ?? '') === 'approved' ? 1 : 0,'review_status'=>'normal'];
    $exists = $db->prepare("SELECT user_id FROM professional_reputation WHERE user_id = ?"); $exists->execute([$userId]);
    $values = [$data['score'],$data['category'],$data['profile_complete'],$data['completed_operations'],$data['accepted_requests'],$data['response_rate'],$data['publication_completeness'],$data['verified_reviews_count'],$data['verified_reviews_average'],$data['relevant_matches'],$data['incidents_count'],$data['last_activity_at'],$data['verification_badge'],$data['review_status'],$userId];
    if ($exists->fetchColumn()) {
        $db->prepare("UPDATE professional_reputation SET score=?,category=?,profile_complete=?,completed_operations=?,accepted_requests=?,response_rate=?,publication_completeness=?,verified_reviews_count=?,verified_reviews_average=?,relevant_matches=?,incidents_count=?,last_activity_at=?,verification_badge=?,review_status=?,calculated_at=CURRENT_TIMESTAMP WHERE user_id=?")->execute($values);
    } else {
        array_unshift($values, $userId);
        $db->prepare("INSERT INTO professional_reputation (user_id,score,category,profile_complete,completed_operations,accepted_requests,response_rate,publication_completeness,verified_reviews_count,verified_reviews_average,relevant_matches,incidents_count,last_activity_at,verification_badge,review_status,calculated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)")->execute($values);
    }
    return $data;
}

$isDirectRequest = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__);

if ($isDirectRequest && $action === 'public') {
    $userId = (int)($_GET['user_id'] ?? 0); if ($userId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Profesional no válido.']); exit; }
    try { $data = reputation_calculate($db, $userId); unset($data['user_id']); echo json_encode(['ok'=>true,'reputation'=>$data]); } catch (Throwable $e) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Reputación no disponible.']); } exit;
}

if ($isDirectRequest && $action === 'me') { $user = require_auth(); try { echo json_encode(['ok'=>true,'reputation'=>reputation_calculate($db,(int)$user['id'])]); } catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'No se pudo calcular la reputación.']); } exit; }

if ($isDirectRequest && $action === 'review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth(); $input = json_decode(file_get_contents('php://input'), true) ?: $_POST; $operationId=(int)($input['operation_id']??0); $subjectId=(int)($input['subject_user_id']??0); $score=(int)($input['score']??0); $comment=trim((string)($input['comment']??''));
    if ($operationId<=0 || $subjectId<=0 || $subjectId===(int)$user['id'] || $score<1 || $score>5 || mb_strlen($comment)>1000) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Valoración no válida.']); exit; }
    $stmt=$db->prepare("SELECT id FROM operations WHERE id=? AND status='closed' AND ((captador_user_id=? AND colaborador_user_id=?) OR (captador_user_id=? AND colaborador_user_id=?))"); $stmt->execute([$operationId,(int)$user['id'],$subjectId,$subjectId,(int)$user['id']]);
    if (!$stmt->fetchColumn()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo se permiten valoraciones de operaciones cerradas verificadas.']); exit; }
    try { $db->prepare("INSERT INTO professional_reviews (reviewer_user_id,subject_user_id,operation_id,score,comment,status) VALUES (?,?,?,?,?,'pending')")->execute([(int)$user['id'],$subjectId,$operationId,$score,$comment]); echo json_encode(['ok'=>true,'status'=>'pending']); } catch (Throwable $e) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'Esta operación ya fue valorada.']); } exit;
}

if ($isDirectRequest && $action === 'moderate_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo administración puede moderar valoraciones.']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $reviewId = (int)($input['review_id'] ?? 0);
    $status = in_array($input['status'] ?? '', ['approved','rejected'], true) ? $input['status'] : '';
    if ($reviewId <= 0 || $status === '') { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Moderación no válida.']); exit; }
    $stmt = $db->prepare("SELECT subject_user_id FROM professional_reviews WHERE id = ? AND status = 'pending'"); $stmt->execute([$reviewId]); $subjectId = (int)$stmt->fetchColumn();
    if ($subjectId <= 0) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Valoración no encontrada o ya moderada.']); exit; }
    $db->prepare("UPDATE professional_reviews SET status = ? WHERE id = ?")->execute([$status, $reviewId]);
    if ($status === 'approved') { try { reputation_calculate($db, $subjectId); } catch (Throwable $e) { error_log('Reputation recalculation deferred after review moderation.'); } }
    echo json_encode(['ok'=>true,'status'=>$status]); exit;
}

if ($isDirectRequest) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Acción no válida.']); }
