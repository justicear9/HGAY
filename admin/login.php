<?php
/**
 * Admin login. Redirects to dashboard if already logged in.
 */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require_once dirname(__DIR__) . '/config/database.php';

if (!empty($_SESSION['admin_user_id'])) {
  header('Location: dashboard.php');
  exit;
}

try {
  $stmt = dbConnection()->query('SELECT COUNT(*) FROM admin_users');
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
    $stmt = dbConnection()->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
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

$logoSrc = '../HGAY ASSETS/Card Pictures and Video/howghrupng.png';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="<?php echo htmlspecialchars($logoSrc); ?>" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <title>Admin sign in — How Ghanaian Are You?</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-auth-body">
  <main class="admin-auth">
    <div class="admin-auth-shell">
      <article class="admin-auth-card">
        <header class="admin-auth-header">
          <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="How Ghanaian Are You" class="admin-auth-logo" width="180" height="auto">
          <h1 class="admin-auth-heading">Welcome back</h1>
          <p class="admin-auth-subheading">Enter your credentials to access the HGAY admin panel.</p>
        </header>

        <?php if ($error): ?>
        <div class="admin-auth-alert" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" class="admin-auth-form" id="login-form">
          <div class="admin-auth-field">
            <label for="username" class="admin-auth-label">Username</label>
            <input type="text" id="username" name="username" class="admin-auth-input" required autocomplete="username" placeholder="Your username">
          </div>
          <div class="admin-auth-field">
            <label for="password" class="admin-auth-label">Password</label>
            <div class="admin-auth-password-wrap">
              <input type="password" id="password" name="password" class="admin-auth-input admin-auth-input--password" required autocomplete="current-password" placeholder="••••••••••••">
              <button type="button" class="admin-auth-password-toggle" id="password-toggle" aria-label="Show password">
                <svg class="icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" hidden><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>
          <button type="submit" class="admin-auth-btn">Sign in</button>
        </form>

        <footer class="admin-auth-footer">
          <a href="../index.html">← Back to website</a>
        </footer>
      </article>
    </div>
  </main>
  <script>
    (function () {
      var input = document.getElementById('password');
      var btn = document.getElementById('password-toggle');
      if (!input || !btn) return;
      var eye = btn.querySelector('.icon-eye');
      var eyeOff = btn.querySelector('.icon-eye-off');
      btn.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        eye.hidden = show;
        eyeOff.hidden = !show;
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      });
    })();
  </script>
</body>
</html>
