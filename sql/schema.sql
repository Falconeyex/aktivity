-- AKTIVITY — new schema only. Do not run any legacy scripts.
-- MariaDB / MySQL 8+ compatible. Import this file first, then run /seed.php once.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS card_history;
DROP TABLE IF EXISTS cards;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS auth_attempts;
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_settings (
    user_id INT UNSIGNED NOT NULL,
    language ENUM('cs', 'en') NOT NULL DEFAULT 'en',
    theme ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    ai_sidebar_pinned TINYINT(1) NOT NULL DEFAULT 1,
    hidden_columns VARCHAR(255) NOT NULL DEFAULT '[]',
    PRIMARY KEY (user_id),
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cards (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    status_column ENUM('backlog', 'todo', 'in_progress', 'review', 'done', 'postponed') NOT NULL,
    order_index INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    remind_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_cards_board (user_id, deleted_at, status_column, order_index),
    CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE card_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    card_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(32) NOT NULL,
    previous_state JSON NULL,
    new_state JSON NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_card (card_id, timestamp),
    CONSTRAINT fk_history_card FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(32) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_lookup (email, ip, action, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_reset_token (token_hash),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
