<?php
/**
 * Admin layout — collapsible sidebar. Requires auth.php first.
 * $adminTitle, $adminPage
 */
$adminTitle = $adminTitle ?? 'Admin';
$adminPage = $adminPage ?? '';
$username = $_SESSION['admin_username'] ?? 'Admin';
$logoSrc = '../HGAY ASSETS/Card Pictures and Video/howghrupng.png';
$faviconSrc = $logoSrc;

$pendingOrders = 0;
try {
  if (!function_exists('dbConnection')) {
    require_once dirname(__DIR__) . '/../config/database.php';
  }
  $pendingOrders = (int) dbConnection()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {
}

require_once __DIR__ . '/icons.php';

$navMain = [
  ['page' => 'dashboard', 'href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'home'],
  ['page' => 'orders', 'href' => 'orders.php', 'label' => 'Orders', 'icon' => 'cart', 'badge' => $pendingOrders],
  ['page' => 'settings', 'href' => 'settings.php', 'label' => 'Settings', 'icon' => 'settings'],
  ['page' => 'fact-cards', 'href' => 'fact-cards.php', 'label' => 'Fact Check', 'icon' => 'book'],
  ['page' => 'card-references', 'href' => 'card-references.php', 'label' => 'References', 'icon' => 'tag'],
  ['page' => 'view-site', 'href' => '../index.html', 'label' => 'View site', 'icon' => 'external', 'external' => true],
];

$navAccount = [
  ['href' => 'logout.php', 'label' => 'Log out', 'icon' => 'logout'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="<?php echo htmlspecialchars($faviconSrc); ?>" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($faviconSrc); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <title><?php echo htmlspecialchars($adminTitle); ?> — HGAY Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body">
  <div class="admin-app" id="admin-app">
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
      <div class="admin-sidebar-brand">
        <a href="dashboard.php" class="admin-sidebar-brand-link">
          <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="" class="admin-sidebar-logo" width="40" height="40">
          <span class="admin-sidebar-brand-text">
            <span class="admin-sidebar-brand-name">How Ghanaian Are You</span>
            <span class="admin-sidebar-brand-user"><?php echo htmlspecialchars($username); ?></span>
          </span>
        </a>
      </div>

      <nav class="admin-sidebar-nav">
        <?php foreach ($navMain as $item): ?>
        <?php
          $isActive = ($adminPage === $item['page']);
          $isExternal = !empty($item['external']);
        ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
           class="admin-nav-item<?php echo $isActive ? ' is-active' : ''; ?>"
           <?php echo $isExternal ? ' target="_blank" rel="noopener"' : ''; ?>>
          <span class="admin-nav-icon"><?php echo admin_icon($item['icon']); ?></span>
          <span class="admin-nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
          <?php if (!empty($item['badge']) && (int) $item['badge'] > 0): ?>
          <span class="admin-nav-badge"><?php echo (int) $item['badge']; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </nav>

      <div class="admin-sidebar-section">
        <p class="admin-sidebar-section-title">Account</p>
        <?php foreach ($navAccount as $item): ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>" class="admin-nav-item">
          <span class="admin-nav-icon"><?php echo admin_icon($item['icon']); ?></span>
          <span class="admin-nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
        <?php endforeach; ?>
      </div>

      <button type="button" class="admin-sidebar-toggle" id="admin-sidebar-toggle" aria-expanded="true" aria-controls="admin-sidebar">
        <span class="admin-nav-icon admin-sidebar-toggle-icon"><?php echo admin_icon('chevrons'); ?></span>
        <span class="admin-nav-label admin-sidebar-toggle-label">Collapse</span>
      </button>
    </aside>

    <div class="admin-sidebar-backdrop" id="admin-sidebar-backdrop" hidden></div>

    <main class="admin-main">
      <div class="admin-mobile-bar">
        <button type="button" class="admin-mobile-menu" id="admin-mobile-menu" aria-label="Open menu">
          <?php echo admin_icon('chevrons'); ?>
        </button>
        <span class="admin-mobile-title"><?php echo htmlspecialchars($adminTitle); ?></span>
      </div>
