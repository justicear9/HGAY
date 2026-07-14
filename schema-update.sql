-- Run after schema.sql (phpMyAdmin: select hgay, run this)
USE hgay;

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

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(500) NOT NULL,
  event_date DATE NOT NULL,
  event_time TIME DEFAULT NULL,
  price_display VARCHAR(64) DEFAULT NULL,
  registration_url VARCHAR(500) DEFAULT NULL,
  registration_label VARCHAR(64) NOT NULL DEFAULT 'Register',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_event_date (event_date),
  INDEX idx_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gallery_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  media_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
  file_path VARCHAR(500) NOT NULL,
  alt_text VARCHAR(255) NOT NULL DEFAULT '',
  caption VARCHAR(128) DEFAULT NULL,
  poster_path VARCHAR(500) DEFAULT NULL,
  layout ENUM('normal', 'wide', 'video') NOT NULL DEFAULT 'normal',
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB;

-- Pay on delivery orders (run once on live DB if not already applied)
ALTER TABLE orders
  MODIFY COLUMN status ENUM('pending', 'paid', 'failed', 'confirmed') NOT NULL DEFAULT 'pending';

-- Optional: set app_secret in config/site.php for order access tokens (see config/site.example.php).

-- Track whether the customer confirmation email was sent (run once on live DB)
ALTER TABLE orders
  ADD COLUMN confirmation_email_sent_at DATETIME NULL DEFAULT NULL AFTER paystack_reference;

