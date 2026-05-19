<?php
/**
 * Admin layout start — requires auth.php before include.
 * $adminTitle, $adminPage (for nav active state)
 */
$adminTitle = $adminTitle ?? 'Admin';
$adminPage = $adminPage ?? '';
$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <title><?php echo htmlspecialchars($adminTitle); ?> — HGAY Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body">
  <div class="admin-app">
    <aside class="admin-sidebar">
      <a href="dashboard.php" class="admin-brand">HGAY Admin</a>
      <nav class="admin-nav">
        <a href="dashboard.php" class="<?php echo $adminPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="orders.php" class="<?php echo $adminPage === 'orders' ? 'active' : ''; ?>">Orders</a>
        <a href="settings.php" class="<?php echo $adminPage === 'settings' ? 'active' : ''; ?>">Settings</a>
        <a href="fact-cards.php" class="<?php echo $adminPage === 'fact-cards' ? 'active' : ''; ?>">Fact Check cards</a>
        <a href="card-references.php" class="<?php echo $adminPage === 'card-references' ? 'active' : ''; ?>">Card references</a>
      </nav>
      <div class="admin-sidebar-footer">
        <span class="admin-user"><?php echo htmlspecialchars($username); ?></span>
        <a href="../index.html" class="admin-link-muted">View site</a>
        <a href="logout.php" class="admin-link-muted">Log out</a>
      </div>
    </aside>
    <main class="admin-main">
