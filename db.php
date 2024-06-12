<?php
$dsn = 'sqlite:database.db';

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create visits table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create users table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    )");

    // Insert default user if not exists
    $default_username = 'admin';
    $default_password = 'password';
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (id, username, password) VALUES (5, :username, :password)");
    $stmt->bindValue(':username', $default_username, PDO::PARAM_STR);
    $stmt->bindValue(':password', $default_password, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}
