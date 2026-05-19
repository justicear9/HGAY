<?php
/**
 * Admin login. Redirects to index if already logged in.
 */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require_once dirname(__DIR__) . '/config/database.php';

if (!empty($_SESSION['admin_user_id'])) {
  header('Location: dashboard.php');
  exit;
}

try {
  $stmt = dbConnection()->query("SELECT COUNT(*) FROM admin_users");
  if ((int) $stmt->fetchColumn() === 0) {
    header('Location: setup.php');
    exit;
  }
} catch (PDOException $e) {
  // Tables may not exist yet
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string) ($_POST['username'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  if ($username === '' || $password === '') {
    $error = 'Enter username and password.';
  } else {
    $stmt = dbConnection()->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
      $_SESSION['admin_user_id'] = (int) $user['id'];
      $_SESSION['admin_username'] = $username;
      header('Location: dashboard.php');
      exit;
    }
    $error = 'Invalid username or password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <title>Admin login — How Ghanaian Are You</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    body { padding: 2rem; max-width: 360px; margin: 2rem auto; }
    .form-group { margin-bottom: 1rem; }
    label { display: block; margin-bottom: 4px; }
    input { width: 100%; padding: 12px; box-sizing: border-box; }
    .error { color: #dc3545; margin-bottom: 1rem; }
    .btn { margin-top: 8px; }
  </style>
</head>
<body>
  <h1>Admin login</h1>
  <?php if ($error) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
  <form method="post">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autocomplete="username">
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary">Log in</button>
  </form>
  <p style="margin-top: 1.5rem;"><a href="../index.html">← Back to site</a></p>
</body>
</html>
