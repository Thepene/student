<?php
require 'config.php';

function columnExists(PDO $pdo, string $table, string $column): bool {
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    return in_array($column, $cols, true);
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_name VARCHAR(255),
        phone VARCHAR(100),
        email VARCHAR(255),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff', 'manager') NOT NULL DEFAULT 'staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        sku VARCHAR(100) UNIQUE,
        category VARCHAR(100),
        price DECIMAL(10,2) DEFAULT 0.00,
        supplier_id INT NULL,
        image VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS stock_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type ENUM('IN', 'OUT') NOT NULL,
        quantity INT NOT NULL,
        reference_no VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
");

if (!columnExists($pdo, 'products', 'supplier_id')) {
    $pdo->exec('ALTER TABLE products ADD COLUMN supplier_id INT NULL AFTER category');
}

if (!columnExists($pdo, 'products', 'image')) {
    $pdo->exec('ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER supplier_id');
}

if (!columnExists($pdo, 'products', 'price')) {
    $pdo->exec('ALTER TABLE products ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00 AFTER category');
}

$adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
if (!$adminExists) {
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)")
        ->execute(['System Admin', 'admin', 'admin@inventory.local', $adminHash, 'admin']);
}

echo "Database ready. Default admin login: username = admin, password = admin123";
