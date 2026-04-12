-- =============================================================
-- PDL_Helpdesk Database Schema
-- Pantex Dress Ltd. Internal Helpdesk System
-- =============================================================

CREATE DATABASE IF NOT EXISTS pdl_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pdl_helpdesk;

-- -------------------------------------------------------------
-- USERS TABLE
-- Stores all system users across all roles
-- -------------------------------------------------------------
CREATE TABLE users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150)  NOT NULL,
    username      VARCHAR(80)   NOT NULL UNIQUE,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('normal_user','it','mis','admin','super_admin') NOT NULL DEFAULT 'normal_user',
    department    ENUM('IT','MIS','CLICK','GENERAL') NOT NULL DEFAULT 'GENERAL',
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    avatar        VARCHAR(255)  NULL,
    last_login_at DATETIME      NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_department (department),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TICKETS TABLE
-- Core ticket entity
-- -------------------------------------------------------------
CREATE TABLE tickets (
    ticket_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_code         VARCHAR(20)  NOT NULL UNIQUE,         -- e.g. PDL-000123
    title               VARCHAR(255) NOT NULL,
    description         TEXT         NOT NULL,
    department          ENUM('IT','MIS','CLICK') NOT NULL,   -- initial target dept
    assigned_department ENUM('IT','MIS','CLICK') NOT NULL,   -- current assigned dept
    status              ENUM('open','in_progress','solved','closed') NOT NULL DEFAULT 'open',
    priority            ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    created_by          INT UNSIGNED NOT NULL,
    resolved_by         INT UNSIGNED NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at         DATETIME     NULL,
    closed_at           DATETIME     NULL,
    FOREIGN KEY (created_by)  REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_department (assigned_department),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TICKET COMMENTS TABLE
-- Threaded comments per ticket
-- -------------------------------------------------------------
CREATE TABLE ticket_comments (
    comment_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    comment     TEXT         NOT NULL,
    is_internal TINYINT(1)   NOT NULL DEFAULT 0,  -- internal notes (IT/MIS only)
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_ticket (ticket_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TICKET ATTACHMENTS TABLE
-- Supports multiple images per ticket and per comment
-- -------------------------------------------------------------
CREATE TABLE ticket_attachments (
    attachment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id     INT UNSIGNED NOT NULL,
    comment_id    INT UNSIGNED NULL,              -- NULL = attached to ticket itself
    uploaded_by   INT UNSIGNED NOT NULL,
    file_name     VARCHAR(255) NOT NULL,          -- original filename
    stored_name   VARCHAR(255) NOT NULL,          -- hashed/safe stored filename
    file_size     INT UNSIGNED NOT NULL,          -- bytes
    mime_type     VARCHAR(100) NOT NULL,
    file_path     VARCHAR(500) NOT NULL,          -- relative path under uploads/
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id)  REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES ticket_comments(comment_id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_ticket (ticket_id),
    INDEX idx_comment (comment_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- TICKET TRANSFERS TABLE
-- Tracks department-to-department transfers for audit trail
-- -------------------------------------------------------------
CREATE TABLE ticket_transfers (
    transfer_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id         INT UNSIGNED NOT NULL,
    transferred_by    INT UNSIGNED NOT NULL,
    from_department   ENUM('IT','MIS','CLICK') NOT NULL,
    to_department     ENUM('IT','MIS','CLICK') NOT NULL,
    reason            TEXT         NULL,
    transferred_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id)      REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (transferred_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- NOTIFICATIONS TABLE
-- In-app notifications per user
-- -------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    ticket_id       INT UNSIGNED NULL,
    type            ENUM('ticket_created','ticket_commented','ticket_solved','ticket_closed','ticket_transferred') NOT NULL,
    message         VARCHAR(500) NOT NULL,
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- AUDIT LOGS TABLE
-- Immutable event log for compliance and traceability
-- -------------------------------------------------------------
CREATE TABLE audit_logs (
    log_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,               -- NULL if system action
    ticket_id   INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,           -- e.g. 'ticket.status_changed'
    description TEXT         NOT NULL,
    old_value   TEXT         NULL,               -- JSON or plain text
    new_value   TEXT         NULL,
    ip_address  VARCHAR(45)  NULL,               -- supports IPv6
    user_agent  VARCHAR(500) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_ticket (ticket_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- SESSIONS TABLE (optional: DB-backed sessions for reliability)
-- -------------------------------------------------------------
CREATE TABLE user_sessions (
    session_id  VARCHAR(128) PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    ip_address  VARCHAR(45)  NULL,
    user_agent  VARCHAR(500) NULL,
    payload     TEXT         NULL,
    last_active DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_last_active (last_active)
) ENGINE=InnoDB;

-- =============================================================
-- SEED DATA: Super Admin account
-- Password: Admin@PDL2024  (bcrypt hashed below)
-- Change immediately after first login.
-- =============================================================
INSERT INTO users (full_name, username, email, password_hash, role, department, is_active)
VALUES (
    'Super Administrator',
    'superadmin',
    'superadmin@pantexdress.local',
    '$2y$12$eImiTXuWVxfM37uY4JANjQ.Lk9v.j4FTaTyMXHd4L5ERUv7j5qiNS',  -- Admin@PDL2024
    'super_admin',
    'GENERAL',
    1
);
