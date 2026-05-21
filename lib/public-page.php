<?php
/**
 * Serve a static HTML template with injected technical SEO tags.
 */
declare(strict_types=1);

require_once __DIR__ . '/seo.php';

/**
 * @param array<string, mixed> $opts Passed to hgay_seo_inject_html()
 */
function public_page_render(string $pageKey, string $htmlFile, array $opts = []): void
{
    if (!is_file($htmlFile)) {
        http_response_code(500);
        echo 'Page not found.';
        return;
    }

    $html = file_get_contents($htmlFile);
    if ($html === false) {
        http_response_code(500);
        echo 'Page not found.';
        return;
    }

    $page = hgay_seo_page($pageKey);
    $opts['page_key'] = $pageKey;
    echo hgay_seo_inject_html($html, $page, $opts);
}
