<?php
declare(strict_types=1);

$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$isStaging = str_contains($host, 'hostingersite.com');
header('Content-Type: text/plain; charset=UTF-8');

if ($isStaging) {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /api/\n";
echo "Disallow: /admin/\n";
echo "Disallow: /area-privada/\n";
echo "Disallow: /crm/\n";
echo "Disallow: /wp-admin/\n";
echo "Disallow: /wp-login.php\n\n";
echo "Sitemap: https://compracaptacion.com/sitemap.xml\n";
