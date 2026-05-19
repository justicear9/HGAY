<?php
/**
 * Admin session check. Include at top of any admin page that requires login.
 */
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
  session_start();
}
if (empty($_SESSION['admin_user_id'])) {
  header('Location: login.php');
  exit;
}
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
