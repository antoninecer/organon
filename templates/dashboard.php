<?php
$pageTitle = 'Dashboard';

/** @var DepartmentRepository $departmentRepo */
/** @var RecognitionRepository $recognitionRepo */
/** @var PDO $pdo */
$pdo = Database::getInstance();

// --- Data Fetching (same as before) ---

$departments = [];
foreach ($departmentRepo->findAll() as $dept) {
    $departments[$dept['id']] = $dept;
}

$users = [];
$stmt_users = $pdo->query("SELECT id, full_name FROM users");
while($user = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
    $users[$user['id']] = $user;
}

$usersByDepartment = [];
$assignedUserIds = [];
$stmt_links = $pdo->query("SELECT user_id, department_id FROM user_departments");
while ($link = $stmt_links->fetch(PDO::FETCH_ASSOC)) {
    $usersByDepartment[$link['department_id']][] = $link['user_id'];
    $assignedUserIds[$link['user_id']] = true;
}

$unassignedUsers = [];
foreach ($users as $userId => $user) {
    if (!isset($assignedUserIds[$userId])) {
        $unassignedUsers[] = $user;
    }
}

// Fetch recent recognitions (e.g., 5 most recent)
$recentRecognitions = $recognitionRepo->findAll();
if (count($recentRecognitions) > 5) {
    $recentRecognitions = array_slice($recentRecognitions, 0, 5);
}

// --- HTML & CSS Rendering ---

/**
 * Helper function to render a single department card.
 */
function render_department_card($dept, $users, $usersByDepartment) {
    $managerName = $dept['manager_name'] ?? 'N/A';
    $memberIds = $usersByDepartment[$dept['id']] ?? [];
    ?>
    <div class="dept-card">
        <div class="dept-header">
            <?= htmlspecialchars($dept['name']) ?>
        </div>
        <div class="dept-body">
            <div class="dept-manager">
                <strong><?= htmlspecialchars($managerName) ?></strong>
            </div>
            <div class="dept-employees">
                <ul>
                    <?php
                    foreach ($memberIds as $userId) {
                        // Only list users who are not the manager of this specific department
                        if ($userId != $dept['manager_id']) {
                            echo '<li>' . htmlspecialchars($users[$userId]['full_name']) . '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

// Find root departments (those without a parent)
$rootDepts = [];
$childDepts = [];
foreach ($departments as $dept) {
    if (empty($dept['parent_id'])) {
        $rootDepts[] = $dept;
    } else {
        $childDepts[$dept['parent_id']][] = $dept;
    }
}

?>

<style>
    .org-container {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    .org-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }
    .dept-card {
        border: 1px solid #dfe1e5;
        border-radius: 6px;
        flex: 1;
        min-width: 280px;
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden; /* Ensures border-radius is respected by children */
    }
    .dept-header {
        background-color: #334e68; /* Dark blue-grey */
        color: white;
        padding: 0.75rem 1rem;
        font-weight: bold;
        border-bottom: 1px solid #dfe1e5;
    }
    .dept-body {
        padding: 1rem;
    }
    .dept-manager {
        background-color: #f0f4f8; /* Light blue-grey */
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .dept-employees ul {
        margin: 0;
        padding-left: 1.25rem;
    }
    .dept-employees li {
        margin-bottom: 0.25rem;
    }
</style>

<h1>Organizační struktura</h1>

<div class="org-container">
    <?php
    // Render root departments (e.g., "Vedení firmy")
    foreach ($rootDepts as $root) {
        render_department_card($root, $users, $usersByDepartment);
        
        // Render the grid of child departments for this root
        $directSubDepts = $childDepts[$root['id']] ?? [];
        if (!empty($directSubDepts)) {
            echo '<div class="org-grid">';
            foreach ($directSubDepts as $subDept) {
                render_department_card($subDept, $users, $usersByDepartment);
            }
            echo '</div>';
        }
    }
    ?>
</div>

<hr>

<h2>Nezařazení uživatelé</h2>
<article>
    <?php if (empty($unassignedUsers)): ?>
        <p>Všichni uživatelé jsou zařazeni do oddělení.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($unassignedUsers as $user): ?>
                <li><?= htmlspecialchars($user['full_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>

<hr>

<h2>Poslední pochvaly</h2>
<article>
    <?php if (empty($recentRecognitions)): ?>
        <p>Zatím nebyly uděleny žádné pochvaly.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($recentRecognitions as $recognition): ?>
                <li>
                    <?= htmlspecialchars($recognition['message']) ?> od
                    <strong><?= htmlspecialchars($recognition['giver_name']) ?></strong> pro
                    <strong><?= htmlspecialchars($recognition['receiver_name']) ?></strong>
                    (<?= date('j. n. Y', strtotime($recognition['created_at'])) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
