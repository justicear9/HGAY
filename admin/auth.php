<?php
/**
 * Admin session check. Include at top of any admin page that requires login.
 */
require_once dirname(__DIR__) . '/lib/session.php';
require_once dirname(__DIR__) . '/lib/paths.php';

hgay_session_start();

if (empty($_SESSION['admin_user_id'])) {
    header('Location: ' . admin_url('login'), true, 302);
    exit;
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
