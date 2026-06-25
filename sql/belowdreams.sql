CREATE DATABASE IF NOT EXISTS belowdreams
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE belowdreams;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('preorder', 'stock') DEFAULT 'preorder',
    sizes VARCHAR(100),
    image VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100),
    lastname VARCHAR(100),
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(190) NOT NULL,
    size VARCHAR(10),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

INSERT INTO products (
    name,
    slug,
    category,
    price,
    description,
    status,
    sizes,
    image,
    is_featured
)
VALUES

(
    'Pantalon à jambe large unisexe',
    'pantalon-jambe-large-unisexe',
    'pantalon',
    44.90,
    'Pantalon unisexe à jambes larges et cordon de serrage. Confort moderne, tissu doux et coupe ample.',
    'preorder',
    'S,M,L',
    'assets/images/product/pantalon-oversize.png',
    1
),

(
    'T-shirt oversize unisexe',
    'tshirt-oversize-unisexe',
    'tshirt',
    29.90,
    'T-shirt oversize unisexe avec coupe ample et épaules tombantes inspirées du streetwear moderne.',
    'preorder',
    'S,M,L',
    'assets/images/product/tshirt-sport.jpg',
    1
),

(
    'Sweat à capuche oversize unisexe',
    'sweat-capuche-oversize-unisexe',
    'hoodie',
    54.90,
    'Sweat à capuche oversize unisexe inspiré du streetwear contemporain.',
    'preorder',
    'S,M,L',
    'assets/images/product/hoodie-oversize-2.PNG',
    1
),

(
    'T-shirt de sport homme',
    'tshirt-sport-homme',
    'tshirt',
    19.90,
    'Maillot sans manches léger en polyester conçu pour le sport et le quotidien.',
    'preorder',
    'S,M,L',
    'assets/images/product/tshirt-sport-homme.png',
    0
),

(
    'Short de sport homme',
    'short-sport-homme',
    'short',
    24.90,
    'Short léger et confortable avec coupe ample et tissu polyester respirant.',
    'preorder',
    'S,M,L,XL',
    'assets/images/product/short-sport.PNG',
    0
);