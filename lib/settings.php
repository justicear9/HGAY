<?php
/**
 * Site settings (price, etc.) from database.
 */
function settingsGet(PDO $pdo, string $key, ?string $default = null): ?string {
  $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
  $stmt->execute([$key]);
  $row = $stmt->fetch();
  return $row ? (string) $row['setting_value'] : $default;
}

function settingsSet(PDO $pdo, string $key, string $value): void {
  $stmt = $pdo->prepare("
    INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
  ");
  $stmt->execute([$key, $value]);
}

function getProductPriceGhs(PDO $pdo): float {
  $v = settingsGet($pdo, 'product_price_ghs', '100');
  return max(1, (float) $v);
}

function getProductPricePesewas(PDO $pdo): int {
  return (int) round(getProductPriceGhs($pdo) * 100);
}
