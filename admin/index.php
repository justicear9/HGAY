<?php
/**
 * /admin and /admin/ entry: send guests to login, signed-in users to dashboard.
 */
require_once dirname(__DIR__) . '/lib/session.php';

hgay_session_start();

if (empty($_SESSION['admin_user_id'])) {
    header('Location: ' . admin_url('login'), true, 302);
    exit;
}

header('Location: ' . admin_url('dashboard'), true, 302);
exit;
