-- ============================================================
-- Complaint/Feedback Management System
-- Database Schema (with Authentication)
-- ============================================================

CREATE DATABASE IF NOT EXISTS complaint
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE complaint;

-- ------------------------------------------------------------
-- Table: users
-- role: 'admin' | 'staff' | 'public'
-- password stored as bcrypt hash via password_hash()
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','staff','public') NOT NULL DEFAULT 'public',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)   NOT NULL UNIQUE,
    description VARCHAR(255)  NOT NULL DEFAULT '',
    color       VARCHAR(7)    NOT NULL DEFAULT '#6366f1',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: complaints
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  NOT NULL,
    category_id INT UNSIGNED  NOT NULL,
    title       VARCHAR(200)  NOT NULL,
    description TEXT          NOT NULL,
    status      ENUM('open','in_review','resolved','closed') NOT NULL DEFAULT 'open',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_complaint_user     FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_complaint_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: responses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS responses (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    complaint_id    INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    message         TEXT         NOT NULL,
    is_admin_reply  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_response_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    CONSTRAINT fk_response_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seed Accounts
-- Passwords are all:  Password123!
-- Generated via: password_hash('Password123!', PASSWORD_BCRYPT)
-- ------------------------------------------------------------
INSERT IGNORE INTO users (name, email, password, role) VALUES
    ('System Admin',  'admin@clearvoice.com',  '$2y$12$LEGaL2D4mMc43U264jBWQOzDpWGnJg6lCZstjMCoK/7mTXFF7cwHe', 'admin'),
    ('Staff Member',  'staff@clearvoice.com',  '$2y$12$.81OIRgY5DHrtTxhJTzqq.SCvC8xySEG.nJx1RwFhf2sZ6kYYMkqW', 'staff'),
    ('Alice Reyes',   'alice@example.com',     '$2y$12$sXTFVkzWimQtdo8QbIq7wOjSbIPH3Y74d4xdkemzzrYrMXxj5SDS2', 'public'),
    ('Bob Santos',    'bob@example.com',       '$2y$12$KnguZjns2lET3t/I1gCNp.h82yzg4MIKVEmEW.yNrXoaH5futpedG', 'public');

INSERT IGNORE INTO categories (name, description, color) VALUES
    ('Technical Issue',   'Bugs, errors, or system failures',              '#ef4444'),
    ('Billing',           'Payment disputes and invoice concerns',          '#f97316'),
    ('Service Quality',   'Feedback on service delivery and staff conduct', '#eab308'),
    ('Feature Request',   'Suggestions and improvement ideas',              '#22c55e'),
    ('General Feedback',  'All other comments and observations',            '#6366f1');

INSERT INTO complaints (user_id, category_id, title, description, status) VALUES
    (3, 1, 'Login page crashes on mobile', 'The login button triggers a white screen on iOS Safari 17.', 'open'),
    (4, 2, 'Double charged last month',    'Invoice #2045 shows two identical charges of ₱1,200.00.',   'in_review'),
    (3, 3, 'Support response took 5 days', 'My ticket was left unanswered for almost a week.',          'resolved');

INSERT INTO responses (complaint_id, user_id, message, is_admin_reply) VALUES
    (1, 2, 'We have reproduced the issue and a fix will be deployed within 48 hours.', 1),
    (2, 1, 'Our billing team is reviewing the duplicate charge. Expect a refund in 3–5 business days.', 1),
    (3, 3, 'I understand, but the issue was resolved eventually.', 0),
    (3, 2, 'We apologise for the delay. Processes have been improved to prevent recurrence.', 1);
