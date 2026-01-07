<?php

// A simple one-time setup script to initialize the database and schema.

require_once __DIR__ . '/src/Database.php';

echo "Organon Setup Script\n";
echo "====================\n\n";

try {
    $pdo = Database::getInstance();
    echo "[SUCCESS] Database connection established.\n";

    $commands = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );",

        // Departments table for the organigram
        "CREATE TABLE IF NOT EXISTS departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            parent_id INTEGER,
            manager_id INTEGER,
            FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL,
            FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE (manager_id)
        );",
        
        // Junction table for assigning users to departments (e.g., as members)
        "CREATE TABLE IF NOT EXISTS user_departments (
            user_id INTEGER NOT NULL,
            department_id INTEGER NOT NULL,
            PRIMARY KEY (user_id, department_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        );",

        // Goals table
        "CREATE TABLE IF NOT EXISTS goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            assignee_id INTEGER NOT NULL,
            manager_id INTEGER,
            status TEXT NOT NULL DEFAULT 'new',
            due_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
        );",

        // Action items from 1:1s or meetings
        "CREATE TABLE IF NOT EXISTS action_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            owner_id INTEGER NOT NULL,
            due_date DATE,
            status TEXT NOT NULL DEFAULT 'new',
            context TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        // Recognitions / Kudos
        "CREATE TABLE IF NOT EXISTS recognitions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            giver_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (giver_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        );"
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }
    
    echo "[SUCCESS] All tables created successfully or already exist.\n";

} catch (PDOException $e) {
    die("\n[ERROR] A database error occurred: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("\n[ERROR] An unexpected error occurred: " . $e->getMessage() . "\n");
}

echo "\nSetup finished.\n";