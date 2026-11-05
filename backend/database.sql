-- init database
CREATE DATABASE IF NOT EXISTS SalesDB;
USE SalesDB;

-- drop tables
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS admin_businesses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS businesses;

-- business table
CREATE TABLE businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- admin links
CREATE TABLE admin_businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_businesses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_businesses_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_business (user_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    price DECIMAL(10,2) NOT NULL,
    stock_qty INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_products_business (business_id),
    INDEX idx_products_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    charged_per VARCHAR(50) NOT NULL DEFAULT 'Page',
    price DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_services_business (business_id),
    INDEX idx_services_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sales table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    recorded_by INT NULL,
    item_type ENUM('product', 'service') NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    sold_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sales_business (business_id),
    INDEX idx_sales_sold_at (sold_at),
    INDEX idx_sales_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seed businesses
INSERT INTO businesses (id, name) VALUES
(1, 'School Supplies near at BSU'),
(2, 'School Supplies near at Caltex');

-- seed users
-- admin123 hash
-- staff123 hash
INSERT INTO users (id, business_id, username, password_hash, role) VALUES
(1, NULL, 'admin', '$2y$10$9XJBzMH.D9zl81xkKwuWeutu0PquaMSW0sRVtetSzSt07j3gunbRO', 'admin'),
(2, 1, 'staff_bsu', '$2y$10$wySTz.0poOLVul98ij7sVuHNzUNxI/7oj5vFakGRsH8yXU0uRx4gq', 'staff'),
(3, 2, 'staff_caltex', '$2y$10$wySTz.0poOLVul98ij7sVuHNzUNxI/7oj5vFakGRsH8yXU0uRx4gq', 'staff');

-- seed admin
INSERT INTO admin_businesses (user_id, business_id) VALUES
(1, 1),
(1, 2);

-- seed products
INSERT INTO products (id, business_id, name, category, price, stock_qty, status) VALUES
(1, 1, 'Ballpen black', 'Writing', 8.00, 14, 'active'),
(2, 1, 'A4 paper', 'Paper', 285.00, 30, 'active'),
(3, 1, 'Crayons', 'Art supplies', 35.00, 60, 'active'),
(4, 1, 'Pencil', 'Writing', 16.00, 6, 'active'),
(5, 1, 'Eraser', 'Writing', 10.00, 4, 'active'),
(6, 1, 'Yellow Pad', 'Paper', 45.00, 25, 'active'),
(7, 2, 'Ballpen black', 'Writing', 8.00, 40, 'active'),
(8, 2, 'A4 paper', 'Paper', 285.00, 15, 'active');

-- seed services
INSERT INTO services (id, business_id, name, charged_per, price, description, status) VALUES
(1, 1, 'Colored Printing', 'Page', 15.00, 'Full-color print on short or A4 paper.', 'active'),
(2, 1, 'Photocopy', 'Page', 2.00, 'Black and white photocopy.', 'active'),
(3, 1, 'Black & White Printing', 'Page', 5.00, 'Standard black text print.', 'active'),
(4, 1, 'Lamination', 'Sheet', 20.00, 'Hot lamination for IDs and docs.', 'active'),
(5, 1, 'Spiral Binding', 'Copy', 35.00, 'Spiral bind with plastic cover.', 'active'),
(6, 1, 'ID Picture Print', 'Sheet', 30.00, 'Rush ID photos 1x1 and 2x2.', 'inactive'),
(7, 2, 'Colored Printing', 'Page', 15.00, 'Full-color document print.', 'active'),
(8, 2, 'Photocopy', 'Page', 2.00, 'Photocopy per page.', 'active');

-- seed sales
INSERT INTO sales (business_id, recorded_by, item_type, item_id, quantity, unit_price, total, sold_at) VALUES
(1, 2, 'product', 1, 10, 8.00, 80.00, '2026-10-04 19:41:00'),
(1, 2, 'service', 1, 12, 15.00, 180.00, '2026-10-04 17:12:00'),
(1, 2, 'service', 2, 50, 2.00, 100.00, '2026-10-03 14:30:00'),
(1, 2, 'product', 2, 2, 285.00, 570.00, '2026-10-02 10:05:00'),
(1, 2, 'product', 3, 3, 35.00, 105.00, '2026-09-30 13:18:00'),
(1, 2, 'product', 1, 5, 8.00, 40.00, '2026-11-04 11:02:00'),
(1, 2, 'service', 1, 8, 15.00, 120.00, '2026-11-04 10:45:00'),
(1, 2, 'service', 2, 30, 2.00, 60.00, '2026-11-04 09:30:00'),
(1, 2, 'product', 2, 2, 285.00, 570.00, '2026-11-03 16:12:00'),
(1, 2, 'product', 3, 3, 35.00, 105.00, '2026-11-03 13:50:00');
