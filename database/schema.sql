-- ============================================================
-- Fact2PDF - Schema Base de Données
-- MySQL 8.0 | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---- Users ----
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)      NOT NULL,
    `email`         VARCHAR(150)     NOT NULL UNIQUE,
    `password_hash` VARCHAR(255)     NOT NULL,
    `role`          ENUM('admin','user','viewer') NOT NULL DEFAULT 'user',
    `is_active`     TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Clients ----
CREATE TABLE IF NOT EXISTS `clients` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)  NOT NULL,
    `email`       VARCHAR(150)  NULL,
    `phone`       VARCHAR(30)   NULL,
    `address`     VARCHAR(255)  NULL,
    `city`        VARCHAR(100)  NULL,
    `postal_code` VARCHAR(20)   NULL,
    `country`     CHAR(2)       NOT NULL DEFAULT 'FR',
    `logo_path`   VARCHAR(500)  NULL        COMMENT 'Chemin relatif vers le logo uploadé',
    `notes`       TEXT          NULL,
    `created_by`  INT UNSIGNED  NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_clients_name`       (`name`),
    INDEX `idx_clients_created_by` (`created_by`),
    CONSTRAINT `fk_clients_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Contacts (multi-contacts par client) ----
CREATE TABLE IF NOT EXISTS `contacts` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `client_id`  INT UNSIGNED  NOT NULL,
    `name`       VARCHAR(150)  NOT NULL,
    `email`      VARCHAR(150)  NULL,
    `phone`      VARCHAR(30)   NULL,
    `role`       VARCHAR(100)  NULL        COMMENT 'Ex: Directeur, Comptable',
    `is_primary` TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_contacts_client` (`client_id`),
    CONSTRAINT `fk_contacts_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Invoices (Factures) ----
CREATE TABLE IF NOT EXISTS `invoices` (
    `id`          INT UNSIGNED          NOT NULL AUTO_INCREMENT,
    `client_id`   INT UNSIGNED          NOT NULL,
    `number`      VARCHAR(30)           NOT NULL UNIQUE  COMMENT 'Ex: FACT-2026-0001',
    `status`      ENUM('draft','pending','paid','overdue') NOT NULL DEFAULT 'draft',
    `issue_date`  DATE                  NOT NULL,
    `due_date`    DATE                  NOT NULL,
    `subtotal`    DECIMAL(12,2)         NOT NULL DEFAULT 0.00,
    `tax_rate`    DECIMAL(5,2)          NOT NULL DEFAULT 20.00  COMMENT 'Taux TVA %',
    `tax_amount`  DECIMAL(12,2)         NOT NULL DEFAULT 0.00,
    `total`       DECIMAL(12,2)         NOT NULL DEFAULT 0.00,
    `notes`       TEXT                  NULL,
    `created_by`  INT UNSIGNED          NULL,
    `created_at`  TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_invoices_client`     (`client_id`),
    INDEX `idx_invoices_status`     (`status`),
    INDEX `idx_invoices_issue_date` (`issue_date`),
    CONSTRAINT `fk_invoices_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_invoices_user`   FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Invoice Items (Lignes de facture) ----
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `invoice_id`  INT UNSIGNED  NOT NULL,
    `position`    TINYINT       NOT NULL DEFAULT 0  COMMENT 'Ordre d affichage',
    `description` VARCHAR(500)  NOT NULL,
    `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    INDEX `idx_items_invoice` (`invoice_id`),
    CONSTRAINT `fk_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Invoice Comments (Suivi / notes) ----
CREATE TABLE IF NOT EXISTS `invoice_comments` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `invoice_id` INT UNSIGNED  NOT NULL,
    `user_id`    INT UNSIGNED  NULL,
    `content`    TEXT          NOT NULL,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_comments_invoice` (`invoice_id`),
    CONSTRAINT `fk_comments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Projects (Suivi de projets) ----
CREATE TABLE IF NOT EXISTS `projects` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `client_id`   INT UNSIGNED  NOT NULL,
    `invoice_id`  INT UNSIGNED  NULL       COMMENT 'Facture liée (optionnel)',
    `name`        VARCHAR(200)  NOT NULL,
    `description` TEXT          NULL,
    `status`      ENUM('todo','in_progress','review','done','archived') NOT NULL DEFAULT 'todo',
    `priority`    ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    `start_date`  DATE          NULL,
    `end_date`    DATE          NULL,
    `timeline`    JSON          NULL       COMMENT 'Étapes [{label, date, done}]',
    `created_by`  INT UNSIGNED  NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_projects_client`  (`client_id`),
    INDEX `idx_projects_status`  (`status`),
    CONSTRAINT `fk_projects_client`  FOREIGN KEY (`client_id`)  REFERENCES `clients`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_projects_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_projects_user`    FOREIGN KEY (`created_by`) REFERENCES `users`    (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Notifications (polling) ----
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED  NOT NULL,
    `type`       VARCHAR(50)   NOT NULL COMMENT 'invoice_created, project_updated, etc.',
    `title`      VARCHAR(200)  NOT NULL,
    `body`       TEXT          NULL,
    `link`       VARCHAR(500)  NULL,
    `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
