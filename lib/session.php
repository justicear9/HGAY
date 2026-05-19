<?php
/**
 * Shared session bootstrap for admin (cookie path + PHP version compatibility).
 */
require_once __DIR__ . '/paths.php';

function hgay_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $base = hgay_base_path();
    $cookiePath = ($base === '') ? '/' : $base . '/';
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure,
        ]);
    } else {
        session_set_cookie_params(0, $cookiePath, '', $secure, true);
    }

    session_start();
}
