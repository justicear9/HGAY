-- How Ghanaian Are You — Orders and Admin
-- Run once (e.g. in phpMyAdmin or: mysql -u root hgay < schema.sql)

CREATE DATABASE IF NOT EXISTS hgay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hgay;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone_country VARCHAR(5) NOT NULL COMMENT 'e.g. GH, US',
  phone_full VARCHAR(20) NOT NULL COMMENT 'e.g. +233201234567',
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  amount_pesewas INT UNSIGNED NOT NULL,
  currency VARCHAR(3) NOT NULL DEFAULT 'GHS',
  delivery_country VARCHAR(5) NOT NULL,
  delivery_region VARCHAR(255) NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_postcode VARCHAR(32) DEFAULT NULL,
  paystack_reference VARCHAR(64) DEFAULT NULL,
  status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- No default admin. Run admin/setup.php once to create the first admin user.

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(64) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
  ('product_price_ghs', '100'),
  ('product_currency', 'GHS')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

CREATE TABLE IF NOT EXISTS fact_cards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(64) NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  keywords VARCHAR(500) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_search (question, answer, keywords),
  INDEX idx_category (category),
  INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- After import: log in to admin → Settings → Seed Fact Check cards (or admin/seed_fact_cards.php)
