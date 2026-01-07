<?php
$pageTitle = 'Správa úkolů';

/** @var ActionItemRepository $actionItemRepo */
/** @var UserRepository $userRepo */
/** @var DepartmentRepository $departmentRepo */ // Added for permission check
/** @var Auth $auth */

require_once __DIR__ . '/../src/Helpers/GoalPermissions.php';

// Data for the view
$actionItems = $actionItemRepo->findAll();
$allUsers = $userRepo->findAll(); // All users for permission checking

$editingActionItem = null;
if (isset($_GET['edit_id'])) {
    $editingActionItem = $actionItemRepo->find((int)$_GET['edit_id']);
}

// Determine pre-selected owner ID (from GET parameter or editing action item)
$preselectedOwnerId = null;
if (isset($_GET['owner_id'])) {
    $preselectedOwnerId = (int)$_GET['owner_id'];
} elseif ($editingActionItem) {
    $preselectedOwnerId = $editingActionItem['owner_id'];
}

$statusOptions = [
    'new' => 'Nový',
    'in_progress' => 'V řešení',
    'completed' => 'Hotovo'
];

// Filter users for the owner dropdown based on permissions
$uploaderId = $auth->id();
$assignableUsers = [];
foreach ($allUsers as $user) {
    if (is_ancestor_manager($uploaderId, $user['id'], $userRepo, $departmentRepo)) {
        $assignableUsers[] = $user;
    }
}
// If editing an action item or creating for a preselected owner, ensure that user is in the assignable list for display
if ($preselectedOwnerId && !in_array($preselectedOwnerId, array_column($assignableUsers, 'id'))) {
    $userToPreselect = $userRepo->find($preselectedOwnerId);
    if ($userToPreselect) {
        $assignableUsers[] = $userToPreselect; // Add for display purposes
    }
}
?>

<h1>Taktické úkoly a dohody</h1>
<p class="description">Zde spravujete konkrétní akční položky a dohody, které obvykle vyplývají z 1:1 schůzek nebo jiných diskusí. Každý úkol má jasného majitele a termín.</p>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="pico-color-red-500" role="alert">
        <strong>Chyba:</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<article>
    <form method="POST" action="index.php?page=action_items">
        <h2><?= $editingActionItem ? 'Upravit taktický úkol' : 'Vytvořit nový taktický úkol' ?></h2>

        <input type="hidden" name="action" value="save_action_item">
        <?php if ($editingActionItem): ?>
            <input type="hidden" name="id" value="<?= $editingActionItem['id'] ?>">
        <?php endif; ?>

        <label for="title">
            Téma taktického úkolu
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($editingActionItem['title'] ?? '') ?>" required>
        </label>

        <label for="context">
            Kontext (např. z 1:1 schůzky)
            <textarea id="context" name="context"><?= htmlspecialchars($editingActionItem['context'] ?? '') ?></textarea>
        </label>

        <div class="grid">
            <label for="owner_id">
                Majitel úkolu
                <select id="owner_id" name="owner_id" required>
                    <option value="">-- Vyberte majitele --</option>
                    <?php foreach ($assignableUsers as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($preselectedOwnerId == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="due_date">
                Termín do
                <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($editingActionItem['due_date'] ?? '') ?>">
            </label>
        </div>
        
        <label for="status">
            Status
            <select id="status" name="status" required>
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= $value ?>" <?= (($editingActionItem['status'] ?? 'new') == $value) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="grid">
            <button type="submit"><?= $editingActionItem ? 'Uložit změny' : 'Vytvořit úkol' ?></button>
            <?php if ($editingActionItem): ?>
                <a href="index.php?page=action_items" role="button" class="secondary">Zrušit</a>
            <?php endif; ?>
        </div>
    </form>
</article>

<h2>Existující úkoly</h2>
<figure>
    <table>
        <thead>
            <tr>
                <th>Téma</th>
                <th>Majitel</th>
                <th>Termín</th>
                <th>Status</th>
                <th>Kontext</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($actionItems)): ?>
                <tr>
                    <td colspan="6">Zatím nebyly zadány žádné úkoly.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($actionItems as $item): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                        <td><?= htmlspecialchars($item['owner_name']) ?></td>
                        <td><?= $item['due_date'] ? date('j. n. Y', strtotime($item['due_date'])) : '—' ?></td>
                        <td>
                            <span class="pico-badge-dot" style="background-color: <?= match($item['status']) { 'new' => '#007bff', 'in_progress' => '#ffc107', 'completed' => '#28a745' } ?>;"></span>
                            <?= $statusOptions[$item['status']] ?? 'Neznámý' ?>
                        </td>
                        <td><?= htmlspecialchars($item['context'] ?? '—') ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="index.php?page=action_items&edit_id=<?= $item['id'] ?>" role="button" class="contrast outline">Edit</a>
                                <form method="POST" action="index.php?page=action_items" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_action_item">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="contrast" onclick="return confirm('Opravdu chcete smazat tento úkol?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>