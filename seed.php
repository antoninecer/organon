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
        ['username' => 'alice', 'email' => 'alice@firma.cz', 'full_name' => 'Alice Vzorová', 'password' => 'password', 'department' => 'Vedení firmy', 'is_manager_of' => 'Vedení firmy', 'is_admin' => 0],
        // Managers
        ['username' => 'bara', 'email' => 'bara@firma.cz', 'full_name' => 'Bára Marketingová', 'password' => 'password', 'department' => 'Marketing', 'is_manager_of' => 'Marketing', 'is_admin' => 0],
        ['username' => 'cyril', 'email' => 'cyril@firma.cz', 'full_name' => 'Cyril Prodejce', 'password' => 'password', 'department' => 'Prodej', 'is_manager_of' => 'Prodej', 'is_admin' => 0],
        ['username' => 'david', 'email' => 'david@firma.cz', 'full_name' => 'David Vývojář', 'password' => 'password', 'department' => 'Vývoj', 'is_manager_of' => 'Vývoj', 'is_admin' => 0],
        // Employees
        ['username' => 'eva', 'email' => 'eva@firma.cz', 'full_name' => 'Eva Kreativní', 'password' => 'password', 'department' => 'Marketing', 'is_manager_of' => null, 'is_admin' => 0],
        ['username' => 'filip', 'email' => 'filip@firma.cz', 'full_name' => 'Filip Stratég', 'password' => 'password', 'department' => 'Marketing', 'is_manager_of' => null, 'is_admin' => 0],
        ['username' => 'gita', 'email' => 'gita@firma.cz', 'full_name' => 'Gita Obchodníková', 'password' => 'password', 'department' => 'Prodej', 'is_manager_of' => null, 'is_admin' => 0],
        ['username' => 'hynek', 'email' => 'hynek@firma.cz', 'full_name' => 'Hynek Důsledný', 'password' => 'password', 'department' => 'Prodej', 'is_manager_of' => null, 'is_admin' => 0],
        ['username' => 'iva', 'email' => 'iva@firma.cz', 'full_name' => 'Iva Kódová', 'password' => 'password', 'department' => 'Vývoj', 'is_manager_of' => null, 'is_admin' => 0],
        ['username' => 'jan', 'email' => 'jan@firma.cz', 'full_name' => 'Jan Stavitel', 'password' => 'password', 'department' => 'Vývoj', 'is_manager_of' => null, 'is_admin' => 0],
        // Admin user
        ['username' => 'admin', 'email' => 'admin@firma.cz', 'full_name' => 'Admin', 'password' => 'admin', 'department' => null, 'is_manager_of' => null, 'is_admin' => 1],
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
    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password_hash, is_admin) VALUES (?, ?, ?, ?, ?)");
    foreach ($usersData as $ud) {
        $hash = password_hash($ud['password'], PASSWORD_DEFAULT);
        $stmt->execute([$ud['username'], $ud['email'], $ud['full_name'], $hash, $ud['is_admin'] ?? 0]);
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

    // 7. Seed Sample Data for Managers (specifically for Bara's subordinates)
    echo "[INFO] Seeding sample goals, action items, recognitions, and 1:1 notes...\n";

    $baraId = $userIds['bara'];
    $evaId = $userIds['eva'];
    $filipId = $userIds['filip'];

    // Sample Goals for Eva
    $goalStmt = $pdo->prepare("INSERT INTO goals (title, description, assignee_id, manager_id, status, due_date, metric_type, target_value, weight, evaluation_rule, data_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $goalStmt->execute([
        'Zvýšit návštěvnost webu',
        'Zvýšit organickou návštěvnost webu o 20% do konce Q1.',
        $evaId, $baraId, 'in_progress', '2026-03-31', 'percentage', 20.0, 3, '>=', 'Google Analytics'
    ]);
    $goalStmt->execute([
        'Spustit novou kampaň A',
        'Úspěšně spustit novou marketingovou kampaň "Produkt X" do 15. února.',
        $evaId, $baraId, 'new', '2026-02-15', 'boolean', 1.0, 2, 'exact', 'manual'
    ]);

    // Sample Action Items for Filip
    $actionItemStmt = $pdo->prepare("INSERT INTO action_items (title, owner_id, creator_id, due_date, status, context) VALUES (?, ?, ?, ?, ?, ?)");
    $actionItemStmt->execute([
        'Analyzovat konkurenci',
        $filipId, $baraId, '2026-01-20', 'in_progress', 'Provést hloubkovou analýzu marketingových strategií tří hlavních konkurentů.'
    ]);
    $actionItemStmt->execute([
        'Připravit report Q4',
        $filipId, $baraId, '2026-01-25', 'new', 'Shromáždit data a připravit prezentaci pro výsledky Q4 2025.'
    ]);

    // Sample Recognitions (Bara to Eva)
    $recognitionStmt = $pdo->prepare("INSERT INTO recognitions (giver_id, receiver_id, message) VALUES (?, ?, ?)");
    $recognitionStmt->execute([$baraId, $evaId, 'Skvělá práce na analýze trhu, Evo! Velmi cenné poznatky.']);
    $recognitionStmt->execute([$baraId, $filipId, 'Filip, děkuji za rychlou reakci na krizovou komunikaci, zachránil jsi nám situaci.']);

    // Sample 1:1 Notes (Bara about Eva)
    $oneOnOneNoteStmt = $pdo->prepare("INSERT INTO one_on_one_notes (manager_id, subordinate_id, note_date, note) VALUES (?, ?, ?, ?)");
    $oneOnOneNoteStmt->execute([$baraId, $evaId, '2026-01-05', 'Během 1:1 schůzky Eva projevila zájem o rozšíření znalostí v SEO. Dohodnuto zařazení do online kurzu.']);
    $oneOnOneNoteStmt->execute([$baraId, $filipId, '2026-01-07', 'Filip si stěžoval na vysokou zátěž. Přerozdělení úkolů v týdnu 3. Rozvoj v oblasti řízení projektů.']);

    echo "[SUCCESS] Sample data seeded.\n";


} catch (PDOException | Exception $e) {
    die("\n[ERROR] An error occurred: " . $e->getMessage() . "\n");
}

echo "\nSeeding finished successfully.\n";