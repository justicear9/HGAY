<?php
/**
 * URL helpers for extensionless paths (see .htaccess).
 */
function hgay_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    if ($doc !== '' && strpos($root, $doc) === 0) {
        $base = substr($root, strlen($doc));
    } else {
        $base = '';
    }
    return $base === '' ? '' : rtrim($base, '/');
}

function site_url(string $path = ''): string
{
    $hash = '';
    if (($i = strpos($path, '#')) !== false) {
        $hash = substr($path, $i);
        $path = substr($path, 0, $i);
    }
    $path = ltrim($path, '/');
    $base = hgay_base_path();
    if ($path === '') {
        if ($hash !== '') {
            return ($base === '' ? '/' : $base . '/') . $hash;
        }
        return ($base === '' ? '/' : $base . '/');
    }
    $url = ($base === '' ? '' : $base) . '/' . $path;
    return $url . $hash;
}

function admin_url(string $page = ''): string
{
    $page = ltrim($page, '/');
    return site_url('admin' . ($page !== '' ? '/' . $page : ''));
}
