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
