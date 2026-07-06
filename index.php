<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/public-page.php';
require_once __DIR__ . '/lib/security.php';

$htmlFile = __DIR__ . '/index.html';
if (!is_file($htmlFile)) {
    http_response_code(500);
    echo 'Page not found.';
    exit;
}

$html = file_get_contents($htmlFile);
if ($html === false) {
    http_response_code(500);
    echo 'Page not found.';
    exit;
}

$csrfField = hgay_csrf_field();
$html = preg_replace(
    '/(<form[^>]*id="preorder-form"[^>]*>)/i',
    '$1' . "\n            " . $csrfField,
    $html,
    1
);

require_once __DIR__ . '/lib/seo.php';
$page = hgay_seo_page('home');
echo hgay_seo_inject_html($html, $page, ['page_key' => 'home']);
