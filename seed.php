<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

echo "Organon Seeding Script\n";
echo "=====================\n\n";

try {
    $pdo = Database::getInstance();
    echo "[INFO] Database connection established.\n";

    // 1. Clean up existing data to ensure a fresh start
    echo "[INFO] Deleting existing data...\n";
    $pdo->exec("DELETE FROM user_departments;");
    $pdo->exec("DELETE FROM departments;");
    $pdo->exec("DELETE FROM users;");
    // Reset autoincrement counters
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('users', 'departments');");
    echo "[SUCCESS] Existing data deleted.\n";


    // 2. Define the organizational structure
    $departmentsData = [
        'Vedení firmy' => [],
        'Marketing' => ['parent' => 'Vedení firmy'],
        'Prodej' => ['parent' => 'Vedení firmy'],
        'Vývoj' => ['parent' => 'Vedení firmy'],
    ];

    $usersData = [
        // CEO
        ['username' => 'alice', 'email' => 'alice@firma.cz', 'full_name' => 'Alice Vzorová', 'password' => 'password', 'department' => 'Vedení firmy', 'is_manager_of' => 'Vedení firmy'],
        // Managers
        ['username' => 'bara', 'email' => 'bara@firma.cz', 'full_name' => 'Bára Marketingová', 'password' => 'password', 'department' => 'Marketing', 'is_manager_of' => 'Marketing'],
        ['username' => 'cyril', 'email' => 'cyril@firma.cz', 'full_name' => 'Cyril Prodejce', 'password' => 'password', 'department' => 'Prodej', 'is_manager_of' => 'Prodej'],
        ['username' => 'david', 'email' => 'david@firma.cz', 'full_name' => 'David Vývojář', 'password' => 'password', 'department' => 'Vývoj', 'is_manager_of' => 'Vývoj'],
        // Employees
        ['username' => 'eva', 'email' => 'eva@firma.cz', 'full_name' => 'Eva Kreativní', 'password' => 'password', 'department' => 'Marketing', 'is_manager_of' => null],
        ['username' => 'filip', 'email' => 'filip@firma.cz', 'full_name' => 'Filip Stratég', 'password' => 'password', 'department' => 'Marketing', 'is_manager_of' => null],
        ['username' => 'gita', 'email' => 'gita@firma.cz', 'full_name' => 'Gita Obchodníková', 'password' => 'password', 'department' => 'Prodej', 'is_manager_of' => null],
        ['username' => 'hynek', 'email' => 'hynek@firma.cz', 'full_name' => 'Hynek Důsledný', 'password' => 'password', 'department' => 'Prodej', 'is_manager_of' => null],
        ['username' => 'iva', 'email' => 'iva@firma.cz', 'full_name' => 'Iva Kódová', 'password' => 'password', 'department' => 'Vývoj', 'is_manager_of' => null],
        ['username' => 'jan', 'email' => 'jan@firma.cz', 'full_name' => 'Jan Stavitel', 'password' => 'password', 'department' => 'Vývoj', 'is_manager_of' => null],
        // Admin user
        ['username' => 'admin', 'email' => 'admin@firma.cz', 'full_name' => 'Admin', 'password' => 'admin', 'department' => null, 'is_manager_of' => null],
    ];

    $departmentIds = [];
    $userIds = [];

    // 3. Create Departments
    echo "[INFO] Creating departments...\n";
    $stmt = $pdo->prepare("INSERT INTO departments (name, parent_id) VALUES (?, ?)");
    
    // Insert parent department first
    $stmt->execute(['Vedení firmy', null]);
    $vedeniId = $pdo->lastInsertId();
    $departmentIds['Vedení firmy'] = $vedeniId;
    echo "  - Created department: Vedení firmy (ID: $vedeniId)\n";

    // Insert child departments
    foreach ($departmentsData as $name => $data) {
        if ($name === 'Vedení firmy') continue;
        $parentId = $departmentIds[$data['parent']];
        $stmt->execute([$name, $parentId]);
        $deptId = $pdo->lastInsertId();
        $departmentIds[$name] = $deptId;
        echo "  - Created department: $name (ID: $deptId)\n";
    }
    echo "[SUCCESS] Departments created.\n";

    // 4. Create Users
    echo "[INFO] Creating users...\n";
    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)");
    foreach ($usersData as $ud) {
        $hash = password_hash($ud['password'], PASSWORD_DEFAULT);
        $stmt->execute([$ud['username'], $ud['email'], $ud['full_name'], $hash]);
        $userId = $pdo->lastInsertId();
        $userIds[$ud['username']] = $userId;
        echo "  - Created user: {$ud['full_name']} (ID: $userId)\n";
    }
    echo "[SUCCESS] Users created.\n";

    // 5. Link Users to Departments
    echo "[INFO] Linking users to departments...\n";
    $stmt = $pdo->prepare("INSERT INTO user_departments (user_id, department_id) VALUES (?, ?)");
    foreach ($usersData as $ud) {
        $userId = $userIds[$ud['username']];
        // Only link user to department if department is specified
        if (!empty($ud['department'])) {
            $deptId = $departmentIds[$ud['department']];
            $stmt->execute([$userId, $deptId]);
            echo "  - Linked {$ud['full_name']} to {$ud['department']}\n";

            // If user is a manager (and not CEO), link them to the parent department as well
            if ($ud['is_manager_of'] && $ud['username'] !== 'alice') {
                $parentDeptName = $departmentsData[$ud['department']]['parent'];
                $parentDeptId = $departmentIds[$parentDeptName];
                $stmt->execute([$userId, $parentDeptId]);
                echo "  - Linked manager {$ud['full_name']} to parent department {$parentDeptName}\n";
            }
        } else {
            echo "  - User {$ud['full_name']} is not assigned to a department.\n";
        }
    }
    echo "[SUCCESS] Users linked.\n";
    
    // 6. Set Department Managers
    echo "[INFO] Setting department managers...\n";
    $stmt = $pdo->prepare("UPDATE departments SET manager_id = ? WHERE id = ?");
    foreach ($usersData as $ud) {
        if ($ud['is_manager_of']) {
            $userId = $userIds[$ud['username']];
            $deptId = $departmentIds[$ud['is_manager_of']];
            $stmt->execute([$userId, $deptId]);
            echo "  - Set {$ud['full_name']} as manager of {$ud['is_manager_of']}\n";
        }
    }
    echo "[SUCCESS] Department managers set.\n";


} catch (PDOException | Exception $e) {
    die("\n[ERROR] An error occurred: " . $e->getMessage() . "\n");
}

echo "\nSeeding finished successfully.\n";