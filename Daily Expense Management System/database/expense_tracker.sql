-- ============================================================
-- Daily Expense Management System - Database Schema
-- Database: expense_tracker
-- ============================================================

CREATE DATABASE IF NOT EXISTS `expense_tracker`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `expense_tracker`;

-- -------------------------------------------------------
-- Table: users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)     NOT NULL,
  `email`      VARCHAR(150)     NOT NULL UNIQUE,
  `password`   VARCHAR(255)     NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: expenses
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED   NOT NULL,
  `title`          VARCHAR(200)   NOT NULL,
  `amount`         DECIMAL(12,2)  NOT NULL,
  `category`       ENUM('Food','Transport','Shopping','Education','Medical','Bills','Entertainment','Travel','Other') NOT NULL DEFAULT 'Other',
  `payment_method` ENUM('Cash','UPI','Card','Bank Transfer') NOT NULL DEFAULT 'Cash',
  `expense_date`   DATE           NOT NULL,
  `notes`          TEXT           NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_expenses_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_expenses_user_date` (`user_id`, `expense_date`),
  INDEX `idx_expenses_category`  (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: income
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `income` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED   NOT NULL,
  `title`         VARCHAR(200)   NOT NULL,
  `amount`        DECIMAL(12,2)  NOT NULL,
  `category`      ENUM('Salary','Allowance','Freelance','Gift','Other') NOT NULL DEFAULT 'Other',
  `income_date`   DATE           NOT NULL,
  `notes`         TEXT           NULL,
  `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_income_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_income_user_date` (`user_id`, `income_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: budgets
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `budgets` (
  `id`         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED   NOT NULL,
  `month`      TINYINT        NOT NULL COMMENT '1-12',
  `year`       SMALLINT       NOT NULL,
  `amount`     DECIMAL(12,2)  NOT NULL,
  `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_budget_user_month_year` (`user_id`, `month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
