-- init.sql
CREATE DATABASE IF NOT EXISTS engaja
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE engaja;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS newsletter_items;
DROP TABLE IF EXISTS newsletters;
DROP TABLE IF EXISTS company_recipients;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  header_image_path VARCHAR(255) NULL, -- ex: /uploads/headers/abc.png

  -- redes:
  -- social_1_url = instagram
  -- social_2_url = facebook
  -- social_3_url = linkedin
  -- social_4_url = site (se você quiser usar)
  social_1_url VARCHAR(255) NULL,
  social_2_url VARCHAR(255) NULL,
  social_3_url VARCHAR(255) NULL,
  social_4_url VARCHAR(255) NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE company_recipients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_company_email (company_id, email),
  CONSTRAINT fk_recip_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE newsletters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,

  -- quem criou e quem enviou
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  sent_by_user_id BIGINT UNSIGNED NULL,

  -- agenda e envio
  send_at DATETIME NULL,
  sent_at DATETIME NULL,

  status ENUM('draft','scheduled','sending','sent','failed') NOT NULL DEFAULT 'draft',
  error_message TEXT NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_news_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_news_created_by
    FOREIGN KEY (created_by_user_id) REFERENCES users(id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_news_sent_by
    FOREIGN KEY (sent_by_user_id) REFERENCES users(id)
    ON DELETE SET NULL,

  INDEX idx_status_sendat (status, send_at),
  INDEX idx_sentat (sent_at),
  INDEX idx_created_by (created_by_user_id),
  INDEX idx_sent_by (sent_by_user_id)
) ENGINE=InnoDB;

CREATE TABLE newsletter_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  newsletter_id BIGINT UNSIGNED NOT NULL,
  portal VARCHAR(120) NULL,
  news_date DATE NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  link_url VARCHAR(500) NULL,
  pdf_path VARCHAR(255) NULL, -- ex: /uploads/pdfs/abc.pdf
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_item_newsletter
    FOREIGN KEY (newsletter_id) REFERENCES newsletters(id)
    ON DELETE CASCADE,
  INDEX idx_newsletter_sort (newsletter_id, sort_order)
) ENGINE=InnoDB;

-- Admin seed
-- senha: admin123
INSERT INTO users (name, email, password_hash)
VALUES ('Admin', 'admin@admin.com', '$2y$10$EJS5R.6DtzLhPzpRdZpF4uk53iPLe3xDVMNMlRI92BjqPvbIAxL56');
