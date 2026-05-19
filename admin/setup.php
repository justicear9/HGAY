<?php
/**
 * One-time setup: create the first admin user. Delete or restrict after use.
 */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require_once dirname(__DIR__) . '/config/database.php';

$pdo = dbConnection();
$stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
$count = (int) $stmt->fetchColumn();
if ($count > 0) {
  header('Location: login.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string) ($_POST['username'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  if (strlen($username) < 2 || strlen($username) > 64) {
    $error = 'Username must be 2–64 characters.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);
    $_SESSION['admin_user_id'] = (int) $pdo->lastInsertId();
    $_SESSION['admin_username'] = $username;
    header('Location: index.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create admin — HGAY</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style> body { padding: 2rem; max-width: 360px; margin: 0 auto; } .form-group { margin-bottom: 1rem; } label { display: block; margin-bottom: 4px; } input { width: 100%; padding: 10px; } .error { color: #dc3545; margin-bottom: 1rem; } </style>
</head>
<body>
  <h1>Create admin account</h1>
  <?php if ($error) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
  <form method="post">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autocomplete="username">
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary">Create admin</button>
  </form>
</body>
</html>
