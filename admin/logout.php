<?php
require_once dirname(__DIR__) . '/lib/session.php';
require_once dirname(__DIR__) . '/lib/paths.php';

hgay_session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: ' . admin_url('login'), true, 302);
exit;
