<?php
/**
 * Compra Captación CRM - Stats, KPIs & System Telemetry
 */

require_once dirname(__DIR__) . '/database.php';

header('Content-Type: application/json; charset=UTF-8');

$db = CaptacionDB::get();

try {
    // 1. Conteo de Usuarios
    $usersCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $activeAgencies = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'agency'")->fetchColumn();
    $independentPros = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'professional'")->fetchColumn();
    $pendingVerification = (int)$db->query("SELECT COUNT(*) FROM users WHERE email_verified = 0 OR verification_status = 'pending'")->fetchColumn();

    // 2. Conteo de Registros Inmobiliarios
    $propertiesCount = (int)$db->query("SELECT COUNT(*) FROM records WHERE record_type = 'property' AND status != 'deleted'")->fetchColumn();
    $demandsCount = (int)$db->query("SELECT COUNT(*) FROM records WHERE record_type = 'need' AND status != 'deleted'")->fetchColumn();
    
    // Valor acumulado de cartera
    $portfolioValue = (float)$db->query("SELECT COALESCE(SUM(price), 0) FROM records WHERE record_type = 'property' AND status = 'active'")->fetchColumn();

    // 3. Créditos y Finanzas
    $totalCreditsInWallets = (float)$db->query("SELECT COALESCE(SUM(available_balance), 0) FROM wallets")->fetchColumn();
    $totalBonusCredits = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movement_type = 'bonus'")->fetchColumn();
    $totalPurchasedCredits = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM ledger WHERE movement_type = 'purchase'")->fetchColumn();

    // 4. Feeds XML
    $xmlBatchesCount = (int)$db->query("SELECT COUNT(*) FROM import_batches WHERE status != 'deleted'")->fetchColumn();
    $xmlPropertiesImported = (int)$db->query("SELECT COALESCE(SUM(records_imported), 0) FROM import_batches WHERE status != 'deleted'")->fetchColumn();

    // 5. Soporte y Tickets
    $openTickets = 0;
    try {
        $openTickets = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')")->fetchColumn();
    } catch(Exception $e) {}

    // Operaciones, pagos y señales de riesgo para el cuadro CEO
    $operationsInProgress = 0;
    $operationsClosed = 0;
    $closedCommission = 0.0;
    try {
        $operationsInProgress = (int)$db->query("SELECT COUNT(*) FROM operations WHERE status IN ('in_progress', 'pending')")->fetchColumn();
        $operationsClosed = (int)$db->query("SELECT COUNT(*) FROM operations WHERE status IN ('completed', 'closed')")->fetchColumn();
        $closedCommission = (float)$db->query("SELECT COALESCE(SUM(commission_total), 0) FROM operations WHERE status IN ('completed', 'closed')")->fetchColumn();
    } catch (Throwable $e) {}

    $paidRevenue = 0.0;
    $failedPayments = 0;
    try {
        $paidRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('paid', 'succeeded', 'completed')")->fetchColumn();
        $failedPayments = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status IN ('failed', 'canceled')")->fetchColumn();
    } catch (Throwable $e) {}

    $diskTotal = @disk_total_space(dirname(__DIR__, 2));
    $diskFree = @disk_free_space(dirname(__DIR__, 2));
    $disk = ($diskTotal && $diskFree !== false) ? [
        'total_bytes' => (int)$diskTotal,
        'free_bytes' => (int)$diskFree,
        'free_mb' => round($diskFree / 1024 / 1024),
        'used_percent' => round((1 - ($diskFree / $diskTotal)) * 100, 1)
    ] : null;

    // 6. Estado del Sistema / Telemetría
    $systemHealth = [
        'database' => 'connected',
        'database_driver' => $db->getAttribute(PDO::ATTR_DRIVER_NAME),
        'server_time' => date('c'),
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'stripe_configured' => !empty(getenv('STRIPE_SECRET_KEY')),
        'upload_dir_writable' => is_writable(dirname(__DIR__, 2) . '/assets'),
        'disk' => $disk
    ];

    $alerts = [];
    if ($openTickets > 0) $alerts[] = ['level' => 'warning', 'title' => 'Tickets pendientes', 'detail' => $openTickets . ' requieren atención.'];
    if ($failedPayments > 0) $alerts[] = ['level' => 'critical', 'title' => 'Pagos fallidos', 'detail' => $failedPayments . ' pagos fallaron o fueron cancelados.'];
    if ($disk && $disk['used_percent'] >= 90) $alerts[] = ['level' => 'critical', 'title' => 'Almacenamiento crítico', 'detail' => 'El hosting está al ' . $disk['used_percent'] . '% de uso.'];
    if (!$systemHealth['stripe_configured']) $alerts[] = ['level' => 'critical', 'title' => 'Stripe no configurado', 'detail' => 'No se ha detectado la clave secreta del servidor.'];

    echo json_encode([
        'ok' => true,
        'stats' => [
            'users' => [
                'total' => $usersCount,
                'agencies' => $activeAgencies,
                'independent' => $independentPros,
                'pending' => $pendingVerification
            ],
            'records' => [
                'properties' => $propertiesCount,
                'demands' => $demandsCount,
                'portfolio_value' => $portfolioValue
            ],
            'finance' => [
                'circulating_credits' => $totalCreditsInWallets,
                'purchased_credits' => $totalPurchasedCredits,
                'bonus_credits' => $totalBonusCredits,
                'estimated_mrr' => ($activeAgencies * 29) + ($independentPros * 19),
                'paid_revenue' => $paidRevenue
            ],
            'xml_feeds' => [
                'batches_count' => $xmlBatchesCount,
                'properties_imported' => $xmlPropertiesImported
            ],
            'support' => ['open_tickets' => $openTickets],
            'operations' => ['in_progress' => $operationsInProgress, 'closed' => $operationsClosed, 'closed_commission' => $closedCommission],
            'payments' => ['failed' => $failedPayments],
            'alerts' => $alerts,
            'telemetry' => $systemHealth
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
