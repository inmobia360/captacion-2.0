<?php
/**
 * Compra Captación - Pasarela de Pagos Stripe Segura
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

define('STRIPE_PK', getenv('STRIPE_PUBLISHABLE_KEY') ?: (getenv('STRIPE_PUBLIC_KEY') ?: ''));
define('STRIPE_SK', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = CaptacionDB::get();

function stripe_verify_signature(string $payload, string $header, string $secret, int $tolerance = 300): bool {
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't') $timestamp = (int)$value;
        if ($key === 'v1' && $value !== '') $signatures[] = $value;
    }
    if (!$timestamp || abs(time() - $timestamp) > $tolerance || !$signatures) return false;
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) return true;
    }
    return false;
}

function stripe_credit_amount_for_price(string $priceId): float {
    $map = [
        getenv('STRIPE_PRICE_AUTONOMO_MONTHLY') ?: '' => 5,
        getenv('STRIPE_PRICE_AUTONOMO_ANNUAL') ?: '' => 5,
        getenv('STRIPE_PRICE_AGENCIA_MONTHLY') ?: '' => 10,
        getenv('STRIPE_PRICE_AGENCIA_ANNUAL') ?: '' => 10,
        getenv('STRIPE_PRICE_BROKER_MONTHLY') ?: '' => 15,
        getenv('STRIPE_PRICE_BROKER_ANNUAL') ?: '' => 15,
    ];
    return ($priceId !== '' && isset($map[$priceId])) ? (float)$map[$priceId] : 0.0;
}

// 1. OBTENER CONFIGURACIÓN PÚBLICA DE STRIPE
if ($action === 'config') {
    echo json_encode([
        'ok' => true,
        'publishable_key' => STRIPE_PK,
    ]);
    exit;
}

// 2. CREAR SESIÓN DE CHECKOUT STRIPE
if ($action === 'create_checkout_session' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true) ?: $_POST;
    $idempotencyKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $idempotencyKey)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Idempotency-Key obligatorio y no válido.']);
        exit;
    }
    $requestHash = hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $idem = $db->prepare("SELECT request_hash FROM stripe_idempotency_keys WHERE user_id = ? AND idempotency_key = ?");
    $idem->execute([(int)$user['id'], $idempotencyKey]);
    $existingHash = $idem->fetchColumn();
    if ($existingHash !== false) {
        if (!hash_equals((string)$existingHash, $requestHash)) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'La clave de idempotencia ya se utilizó con otra petición.']);
            exit;
        }
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Petición de pago duplicada.']);
        exit;
    }
    $planId = $input['plan_id'] ?? 'credit_single';

    $packs = [
        'credit_single' => ['name' => '1 Crédito de Contacto Compra Captación', 'price_cents' => 1000, 'credits' => 1, 'price_id' => getenv('STRIPE_PRICE_CREDIT_SINGLE') ?: ''],
        'plan_autonomo' => ['name' => 'Profesionales Autónomos', 'price_cents' => 2900, 'credits' => 5, 'price_id' => getenv('STRIPE_PRICE_AUTONOMO_MONTHLY') ?: ''],
        'plan_autonomo_annual' => ['name' => 'Profesionales Autónomos — Anual', 'price_cents' => 22800, 'credits' => 5, 'price_id' => getenv('STRIPE_PRICE_AUTONOMO_ANNUAL') ?: ''],
        'plan_agencia' => ['name' => 'Agencias Inmobiliarias', 'price_cents' => 4400, 'credits' => 10, 'price_id' => getenv('STRIPE_PRICE_AGENCIA_MONTHLY') ?: ''],
        'plan_agencia_annual' => ['name' => 'Agencias Inmobiliarias — Anual', 'price_cents' => 34800, 'credits' => 10, 'price_id' => getenv('STRIPE_PRICE_AGENCIA_ANNUAL') ?: ''],
        'plan_broker' => ['name' => 'Brokers y Grandes Agencias', 'price_cents' => 7400, 'credits' => 15, 'price_id' => getenv('STRIPE_PRICE_BROKER_MONTHLY') ?: ''],
        'plan_broker_annual' => ['name' => 'Brokers y Grandes Agencias — Anual', 'price_cents' => 58800, 'credits' => 15, 'price_id' => getenv('STRIPE_PRICE_BROKER_ANNUAL') ?: ''],
    ];

    if (!isset($packs[$planId])) {
        echo json_encode(['ok' => false, 'error' => 'Plan de créditos no válido.']);
        exit;
    }
    if (STRIPE_SK === '') {
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'Pasarela de pago no configurada.']);
        exit;
    }
    $db->prepare("INSERT INTO stripe_idempotency_keys (user_id, idempotency_key, request_hash) VALUES (?, ?, ?)")
       ->execute([(int)$user['id'], $idempotencyKey, $requestHash]);

    $pack = $packs[$planId];
    if ($pack['price_id'] === '') {
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'El precio Stripe del producto no está configurado.']);
        exit;
    }
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'khaki-parrot-519933.hostingersite.com';
    $baseUrl = "$protocol://$host";

    // Llamada a la API de Stripe
    $postFields = [
        'success_url' => "$baseUrl/#/panel?payment=success&session_id={CHECKOUT_SESSION_ID}",
        'cancel_url' => "$baseUrl/#/panel?payment=cancelled",
        'mode' => $planId === 'credit_single' ? 'payment' : 'subscription',
        'customer_email' => $user['email'],
        'client_reference_id' => (string)$user['id'],
        'metadata[user_id]' => (string)$user['id'],
        'metadata[plan_id]' => $planId,
        'metadata[credits]' => (string)$pack['credits'],
        'line_items[0][price]' => $pack['price_id'],
        'line_items[0][quantity]' => '1',
    ];

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($result['url'])) {
        echo json_encode([
            'ok' => true,
            'checkout_url' => $result['url'],
            'session_id' => $result['id']
        ]);
    } else {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'Stripe no pudo crear la sesión de pago.']);
    }
    exit;
}

// 3. CONFIRMACIÓN Y WEBHOOK DE STRIPE
if ($action === 'webhook' || $action === 'confirm_payment') {
    $rawInput = file_get_contents('php://input');
    if ($action === 'confirm_payment') {
        http_response_code(410);
        echo json_encode(['ok' => false, 'error' => 'La confirmación debe proceder exclusivamente del webhook de Stripe.']);
        exit;
    }
    $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    if (STRIPE_WEBHOOK_SECRET === '' || !stripe_verify_signature($rawInput, $signature, STRIPE_WEBHOOK_SECRET)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Firma de webhook no válida.']);
        exit;
    }
    $event = json_decode($rawInput, true);

    // Proceso de Webhook estándar de Stripe
    if (!empty($event['type']) && $event['type'] === 'checkout.session.completed') {
        $session = $event['data']['object'];
        $metadata = is_array($session['metadata'] ?? null) ? $session['metadata'] : [];
        $userId = (int)($metadata['user_id'] ?? 0);
        $planId = (string)($metadata['plan_id'] ?? '');
        $packs = [
            'credit_single' => ['credits' => 1],
            'plan_autonomo' => ['credits' => 5],
            'plan_autonomo_annual' => ['credits' => 5],
            'plan_agencia' => ['credits' => 10],
            'plan_agencia_annual' => ['credits' => 10],
            'plan_broker' => ['credits' => 15],
            'plan_broker_annual' => ['credits' => 15],
        ];
        if ($userId <= 0 || !isset($packs[$planId])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Webhook sin plan o usuario interno válido.']);
            exit;
        }
        $credits = (float)$packs[$planId]['credits'];
        $amount = ((float)($session['amount_total'] ?? 0)) / 100;
        $sessionId = (string)($session['id'] ?? '');
        $paymentStatus = (string)($session['payment_status'] ?? '');

        if ($userId > 0 && $sessionId !== '' && in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
            $userStmt = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([$userId]);
            if (!$userStmt->fetchColumn()) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Usuario interno del pago no existe.']);
                exit;
            }
            $stmt = $db->prepare("SELECT id FROM payments WHERE stripe_session_id = ?");
            $stmt->execute([$sessionId]);
            if (!$stmt->fetch()) {
                $db->prepare("INSERT INTO payments (user_id, stripe_session_id, amount, currency, status, credits_amount, metadata_json) VALUES (?, ?, ?, 'eur', 'succeeded', ?, ?)")
                   ->execute([$userId, $sessionId, $amount, $credits, $rawInput]);

                $db->prepare("UPDATE wallets SET available_balance = available_balance + ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
                   ->execute([$credits, $userId]);

                $stmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
                $stmt->execute([$userId]);
                $newBalance = (float)$stmt->fetchColumn();

                $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, metadata) VALUES (?, 'topup', 'stripe_webhook', ?, ?, ?)")
                   ->execute([$userId, $credits, $newBalance, json_encode(['session_id' => $sessionId])]);
            }
        }
    }

    if (!empty($event['type']) && $event['type'] === 'invoice.paid') {
        $invoice = $event['data']['object'];
        $invoiceId = (string)($invoice['id'] ?? '');
        $metadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $userId = (int)($metadata['user_id'] ?? 0);
        if ($userId <= 0 && !empty($invoice['customer_email'])) {
            $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $userStmt->execute([strtolower(trim((string)$invoice['customer_email']))]);
            $userId = (int)$userStmt->fetchColumn();
        }
        $credits = 0.0;
        foreach (($invoice['lines']['data'] ?? []) as $line) {
            $credits += stripe_credit_amount_for_price((string)($line['price']['id'] ?? ''));
        }
        if ($invoiceId !== '' && $userId > 0 && $credits > 0) {
            $exists = $db->prepare("SELECT id FROM payments WHERE stripe_session_id = ? LIMIT 1");
            $exists->execute([$invoiceId]);
            if (!$exists->fetchColumn()) {
                $amount = ((float)($invoice['amount_paid'] ?? 0)) / 100;
                $db->prepare("INSERT INTO payments (user_id, stripe_session_id, amount, currency, status, credits_amount, metadata_json) VALUES (?, ?, ?, 'eur', 'succeeded', ?, ?)")
                   ->execute([$userId, $invoiceId, $amount, ''.$credits, $rawInput]);
                $db->prepare("UPDATE wallets SET available_balance = available_balance + ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?")
                   ->execute([$credits, $userId]);
                $walletStmt = $db->prepare("SELECT available_balance FROM wallets WHERE user_id = ?");
                $walletStmt->execute([$userId]); $balance = (float)$walletStmt->fetchColumn();
                $db->prepare("INSERT INTO ledger (user_id, movement_type, credit_source, amount, balance_after, status, metadata) VALUES (?, 'topup', 'stripe_subscription', ?, ?, 'completed', ?)")
                   ->execute([$userId, $credits, $balance, json_encode(['invoice_id' => $invoiceId])]);
            }
        }
    }

    echo json_encode(['received' => true]);
    exit;
}
