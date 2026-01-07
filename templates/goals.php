<?php
$pageTitle = 'Správa cílů';

/** @var GoalRepository $goalRepo */
/** @var UserRepository $userRepo */
/** @var DepartmentRepository $departmentRepo */ // Added for permission check
/** @var Auth $auth */

require_once __DIR__ . '/../src/Helpers/GoalPermissions.php';

// Data for the view
$goals = $goalRepo->findAll();
$allUsers = $userRepo->findAll(); // All users for permission checking

$editingGoal = null;
if (isset($_GET['edit_id'])) {
    $editingGoal = $goalRepo->find((int)$_GET['edit_id']);
}

$statusOptions = [
    'new' => 'Nový',
    'in_progress' => 'V řešení',
    'completed' => 'Hotovo'
];

// Filter users for the assignee dropdown based on permissions
$uploaderId = $auth->id();
$assignableUsers = [];
foreach ($allUsers as $user) {
    if (is_ancestor_manager($uploaderId, $user['id'], $userRepo, $departmentRepo)) {
        $assignableUsers[] = $user;
    }
}
// If editing a goal, ensure the current assignee is in the list even if no longer assignable
if ($editingGoal) {
    $currentAssigneeId = $editingGoal['assignee_id'];
    $isCurrentAssigneeInList = false;
    foreach ($assignableUsers as $au) {
        if ($au['id'] == $currentAssigneeId) {
            $isCurrentAssigneeInList = true;
            break;
        }
    }
    if (!$isCurrentAssigneeInList) {
        $assigneeUser = $userRepo->find($currentAssigneeId);
        if ($assigneeUser) {
            $assignableUsers[] = $assigneeUser; // Add current assignee
        }
    }
}
?>

<h1>Strategické cíle</h1>
<p class="description">Zde zadáváte strategické cíle, které manažeři přiřazují podřízeným. Jsou to dlouhodobější záměry, které podřízený aktualizuje s ohledem na celkový progres.</p>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="pico-color-red-500" role="alert">
        <strong>Chyba:</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<article>
    <form method="POST" action="index.php?page=goals">
        <h2><?= $editingGoal ? 'Upravit strategický cíl' : 'Vytvořit nový strategický cíl' ?></h2>

        <input type="hidden" name="action" value="save_goal">
        <?php if ($editingGoal): ?>
            <input type="hidden" name="id" value="<?= $editingGoal['id'] ?>">
        <?php endif; ?>

        <label for="title">
            Název strategického cíle
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($editingGoal['title'] ?? '') ?>" required>
        </label>

        <label for="description">
            Popis
            <textarea id="description" name="description"><?= htmlspecialchars($editingGoal['description'] ?? '') ?></textarea>
        </label>

        <div class="grid">
            <label for="assignee_id">
                Řešitel
                <select id="assignee_id" name="assignee_id" required>
                    <option value="">-- Vyberte řešitele --</option>
                    <?php foreach ($assignableUsers as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= (($editingGoal['assignee_id'] ?? null) == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="due_date">
                Termín do
                <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($editingGoal['due_date'] ?? '') ?>">
            </label>
        </div>
        
        <label for="status">
            Status
            <select id="status" name="status" required>
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= $value ?>" <?= (($editingGoal['status'] ?? 'new') == $value) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="grid">
            <button type="submit"><?= $editingGoal ? 'Uložit změny' : 'Vytvořit cíl' ?></button>
            <?php if ($editingGoal): ?>
                <a href="index.php?page=goals" role="button" class="secondary">Zrušit</a>
            <?php endif; ?>
        </div>
    </form>
</article>

<h2>Existující cíle</h2>
<figure>
    <table>
        <thead>
            <tr>
                <th>Cíl</th>
                <th>Řešitel</th>
                <th>Zadavatel</th>
                <th>Termín</th>
                <th>Status</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($goals)): ?>
                <tr>
                    <td colspan="6">Zatím nebyly zadány žádné cíle.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($goals as $goal): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($goal['title']) ?></strong><br><small><?= htmlspecialchars($goal['description']) ?></small></td>
                        <td><?= htmlspecialchars($goal['assignee_name']) ?></td>
                        <td><?= htmlspecialchars($goal['manager_name']) ?></td>
                        <td><?= $goal['due_date'] ? date('j. n. Y', strtotime($goal['due_date'])) : '—' ?></td>
                        <td>
                            <span class="pico-badge-dot" style="background-color: <?= match($goal['status']) { 'new' => '#007bff', 'in_progress' => '#ffc107', 'completed' => '#28a745' } ?>;"></span>
                            <?= $statusOptions[$goal['status']] ?? 'Neznámý' ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="index.php?page=goals&edit_id=<?= $goal['id'] ?>" role="button" class="contrast outline">Edit</a>
                                <form method="POST" action="index.php?page=goals" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_goal">
                                    <input type="hidden" name="id" value="<?= $goal['id'] ?>">
                                    <button type="submit" class="contrast" onclick="return confirm('Opravdu chcete smazat tento cíl?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>