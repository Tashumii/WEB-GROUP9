( User )

CREATE TABLE users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    role ENUM('Admin', 'Staff') NOT NULL,
    business_id CHAR(36) NOT NULL
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



