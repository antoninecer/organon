<?php

// A simple script to seed the database with initial data (e.g., the admin user).

require_once __DIR__ . '/src/Database.php';

echo "Organon Seeding Script\n";
echo "======================\n\n";

try {
    $pdo = Database::getInstance();
    echo "[SUCCESS] Database connection established.\n";

    // --- Seed Admin User ---
    $adminUsername = 'admin';
    $adminFullName = 'Administrator';
    $adminPassword = 'admin'; // The password to be hashed

    // Check if the admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$adminUsername]);

    if ($stmt->fetch()) {
        echo "[INFO] Admin user already exists. Skipping.\n";
    } else {
        $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare(
            "INSERT INTO users (username, full_name, password_hash) VALUES (?, ?, ?)"
        );
        $insertStmt->execute([$adminUsername, $adminFullName, $passwordHash]);
        echo "[SUCCESS] Admin user created successfully (admin/admin).\n";
    }

} catch (PDOException $e) {
    die("\n[ERROR] A database error occurred: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("\n[ERROR] An unexpected error occurred: " . $e->getMessage() . "\n");
}

echo "\nSeeding finished.\n";

