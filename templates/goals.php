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

// Determine pre-selected assignee ID (from GET parameter or editing goal)
$preselectedAssigneeId = null;
if (isset($_GET['assignee_id'])) {
    $preselectedAssigneeId = (int)$_GET['assignee_id'];
} elseif ($editingGoal) {
    $preselectedAssigneeId = $editingGoal['assignee_id'];
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
// If editing a goal or creating for a preselected assignee, ensure that user is in the assignable list for display
if ($preselectedAssigneeId && !in_array($preselectedAssigneeId, array_column($assignableUsers, 'id'))) {
    $userToPreselect = $userRepo->find($preselectedAssigneeId);
    if ($userToPreselect) {
        $assignableUsers[] = $userToPreselect; // Add for display purposes
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
            <label for="metric_type">
                Typ metriky
                <select id="metric_type" name="metric_type" required>
                    <option value="number" <?= (($editingGoal['metric_type'] ?? 'number') == 'number') ? 'selected' : '' ?>>Číslo</option>
                    <option value="percentage" <?= (($editingGoal['metric_type'] ?? '') == 'percentage') ? 'selected' : '' ?>>Procenta</option>
                    <option value="boolean" <?= (($editingGoal['metric_type'] ?? '') == 'boolean') ? 'selected' : '' ?>>Ano/Ne</option>
                    <option value="scale" <?= (($editingGoal['metric_type'] ?? '') == 'scale') ? 'selected' : '' ?>>Škála</option>
                </select>
                <small>Definuje, jak se cíl měří (např. absolutní číslo, procenta, ano/ne, na škále).</small>
            </label>
            <label for="target_value">
                Cílová hodnota
                <input type="number" step="any" id="target_value" name="target_value" value="<?= htmlspecialchars($editingGoal['target_value'] ?? '') ?>">
                <small>Hodnota, které má být dosaženo (např. 100 pro číslo, 80.5 pro procenta). Může být prázdné pro metodu "Škála".</small>
            </label>
        </div>

        <div class="grid">
            <label for="weight">
                Váha (1-5)
                <input type="number" min="1" max="5" id="weight" name="weight" value="<?= htmlspecialchars($editingGoal['weight'] ?? 1) ?>">
                <small>Důležitost cíle pro celkové hodnocení (1-nejmenší, 5-největší).</small>
            </label>
            <label for="evaluation_rule">
                Pravidlo hodnocení
                <select id="evaluation_rule" name="evaluation_rule" required>
                    <option value=">=" <?= (($editingGoal['evaluation_rule'] ?? '>=') == '>=') ? 'selected' : '' ?>>Větší nebo rovno (>=)</option>
                    <option value="<=" <?= (($editingGoal['evaluation_rule'] ?? '') == '<=') ? 'selected' : '' ?>>Menší nebo rovno (<=)</option>
                    <option value="exact" <?= (($editingGoal['evaluation_rule'] ?? '') == 'exact') ? 'selected' : '' ?>>Přesná hodnota</option>
                </select>
                <small>Logika pro vyhodnocení plnění cíle vůči cílové hodnotě.</small>
            </label>
            <label for="data_source">
                Zdroj dat
                <select id="data_source" name="data_source" required>
                    <option value="manual" <?= (($editingGoal['data_source'] ?? 'manual') == 'manual') ? 'selected' : '' ?>>Manuálně</option>
                    <option value="api" <?= (($editingGoal['data_source'] ?? '') == 'api') ? 'selected' : '' ?>>API</option>
                    <option value="system" <?= (($editingGoal['data_source'] ?? '') == 'system') ? 'selected' : '' ?>>Systém</option>
                </select>
                <small>Odkud se získává aktuální hodnota pro reporting (manuální zadání, automaticky z API, z interního systému).</small>
            </label>
        </div>

        <div class="grid">
            <label for="assignee_id">
                Řešitel
                <select id="assignee_id" name="assignee_id" required>
                    <option value="">-- Vyberte řešitele --</option>
                    <?php foreach ($assignableUsers as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($preselectedAssigneeId == $user['id']) ? 'selected' : '' ?>>
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
                <th>Reporty</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($goals)): ?>
                <tr>
                    <td colspan="7">Zatím nebyly zadány žádné cíle.</td>
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
                        <td><a href="index.php?page=goal_report&goal_id=<?= $goal['id'] ?>" role="button" class="contrast outline">Reporty</a></td>
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