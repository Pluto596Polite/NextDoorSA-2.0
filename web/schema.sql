-- Database schema for NextDoorSA
-- Run via: php web/init_database.php

CREATE TABLE IF NOT EXISTS users (
    userID INT PRIMARY KEY AUTO_INCREMENT,
    userEmail VARCHAR(100) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    phoneNumber VARCHAR(15) NOT NULL,
    walletBalance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS category (
    categoryID INT PRIMARY KEY AUTO_INCREMENT,
    categoryName VARCHAR(50) NOT NULL,
    categoryImage VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product (
    productID INT PRIMARY KEY AUTO_INCREMENT,
    sellerID INT NOT NULL,
    categoryID INT NOT NULL,
    categoryTitle VARCHAR(100) NOT NULL,
    productDescription TEXT NOT NULL,
    imageURL VARCHAR(255) NOT NULL DEFAULT 'default-product.png',
    cityArea VARCHAR(100),
    productStatus ENUM('Available', 'Pending', 'Sold') NOT NULL DEFAULT 'Available',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_seller FOREIGN KEY (sellerID) REFERENCES users(userID),
    CONSTRAINT fk_product_category FOREIGN KEY (categoryID) REFERENCES category(categoryID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart (
    cartID INT PRIMARY KEY AUTO_INCREMENT,
    buyerID INT NOT NULL,
    productID INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_cart_buyer FOREIGN KEY (buyerID) REFERENCES users(userID),
    CONSTRAINT fk_cart_product FOREIGN KEY (productID) REFERENCES product(productID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    orderID INT PRIMARY KEY AUTO_INCREMENT,
    buyerID INT NOT NULL,
    totalAmount DECIMAL(10,2) NOT NULL,
    shippingAddress TEXT NOT NULL,
    escrowStatus ENUM('Held', 'Released', 'Disputed', 'Refunded') NOT NULL DEFAULT 'Held',
    collectionCode VARCHAR(10) UNIQUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyerID) REFERENCES users(userID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    orderItemID INT PRIMARY KEY AUTO_INCREMENT,
    orderID INT NOT NULL,
    productID INT NOT NULL,
    priceAtPurchase DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (orderID) REFERENCES orders(orderID),
    CONSTRAINT fk_order_items_product FOREIGN KEY (productID) REFERENCES product(productID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

