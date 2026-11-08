CREATE DATABASE IF NOT EXISTS SalesDB
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE SalesDB;

SET NAMES utf8mb4;
SET time_zone = '+08:00';

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS auth_attempts;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS memberships;
DROP TABLE IF EXISTS admin_businesses;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS businesses;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE businesses (
    id         INT NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE users (
    id                   INT NOT NULL AUTO_INCREMENT,
    username             VARCHAR(50) NOT NULL,
    password_hash        VARCHAR(255) NOT NULL,
    is_active            TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at        TIMESTAMP NULL DEFAULT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE memberships (
    id          INT NOT NULL AUTO_INCREMENT,
    user_id     INT NOT NULL,
    business_id INT NOT NULL,
    role        ENUM('owner', 'admin', 'staff') NOT NULL DEFAULT 'staff',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_by  INT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_membership (user_id, business_id),
    KEY idx_membership_business_role (business_id, role, is_active),
    KEY idx_membership_user (user_id, is_active),
    CONSTRAINT fk_memberships_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_memberships_business FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE,
    CONSTRAINT fk_memberships_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE products (
    id          INT NOT NULL AUTO_INCREMENT,
    business_id INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    category    VARCHAR(100) NOT NULL DEFAULT 'General',
    price       DECIMAL(10, 2) NOT NULL,
    stock_qty   INT NOT NULL DEFAULT 0,
    status      ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_products_business (business_id),
    KEY idx_products_status (status),
    CONSTRAINT fk_products_business FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE services (
    id          INT NOT NULL AUTO_INCREMENT,
    business_id INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    charged_per VARCHAR(50) NOT NULL DEFAULT 'Page',
    price       DECIMAL(10, 2) NOT NULL,
    description TEXT NULL,
    status      ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_services_business (business_id),
    KEY idx_services_status (status),
    CONSTRAINT fk_services_business FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE sales (
    id          INT NOT NULL AUTO_INCREMENT,
    business_id INT NOT NULL,
    recorded_by INT NULL,
    item_type   ENUM('product', 'service') NOT NULL,
    item_id     INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10, 2) NOT NULL,
    total       DECIMAL(10, 2) NOT NULL,
    sold_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_sales_recorded_by (recorded_by),
    KEY idx_sales_business (business_id),
    KEY idx_sales_sold_at (sold_at),
    KEY idx_sales_type (item_type),
    CONSTRAINT fk_sales_business FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_recorded_by FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE auth_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    action       ENUM('login', 'register') NOT NULL DEFAULT 'login',
    username     VARCHAR(100) NOT NULL DEFAULT '',
    ip_address   VARCHAR(45) NOT NULL,
    success      TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_lookup (action, username, ip_address, attempted_at),
    KEY idx_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE user_sessions (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_hash   CHAR(64)       NOT NULL,
    user_id        INT            NOT NULL,
    ip_address     VARCHAR(45)    NOT NULL DEFAULT '',
    user_agent     VARCHAR(500)   NOT NULL DEFAULT '',
    created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at     TIMESTAMP      NOT NULL,
    revoked_at     TIMESTAMP      NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_user_sessions_hash (session_hash),
    KEY idx_user_sessions_user (user_id, revoked_at, expires_at),
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE rate_limits (
    limit_key    VARCHAR(120) NOT NULL,
    window_start TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hits         INT           NOT NULL DEFAULT 0,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (limit_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- No demo accounts or businesses are seeded.
-- Register through frontend/register.php, then create the first business from frontend/CreateBusiness.php.
