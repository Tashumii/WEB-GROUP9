( User )

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id CHAR(36) NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff'
);


( Admin_Business )

CREATE TABLE admin_businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id CHAR(36) NOT NULL,

    CONSTRAINT fk_admin_businesses_user
        FOREIGN KEY (user_id) REFERENCES users(id),

    CONSTRAINT fk_admin_businesses_business
        FOREIGN KEY (business_id) REFERENCES business(id)
);

( Service )

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    CONSTRAINT fk_services_business
        FOREIGN KEY (business_id) REFERENCES business(id)
);


( SALE )

CREATE TABLE sale (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    item_id CHAR(36) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    sold_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    recorded_by CHAR(36) NOT NULL,
    FOREIGN KEY (item_id) REFERENCES product(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

( Business )

CREATE TABLE business (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL
);

( Products )

CREATE TABLE product (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_qty INT NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);



