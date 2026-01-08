<?php
$pageTitle = 'Taktické úkoly';

/** @var ActionItemRepository $actionItemRepo */
/** @var UserRepository $userRepo */
/** @var DepartmentRepository $departmentRepo */
/** @var Auth $auth */

require_once __DIR__ . '/../src/Helpers/GoalPermissions.php';

$currentUserId = $auth->id();

// --- Data for the view ---

// 1. Tasks assigned to the current user
$myActionItems = $actionItemRepo->findByOwner($currentUserId);

// 2. Tasks created by the current user for their team
$teamActionItems = $actionItemRepo->findByCreator($currentUserId);

// 3. Data for the 'Create/Edit Task' form
$editingActionItem = null;
if (isset($_GET['edit_id'])) {
    $editingActionItem = $actionItemRepo->find((int)$_GET['edit_id']);
}

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
$allUsers = $userRepo->findAll();
$assignableUsers = [];
foreach ($allUsers as $user) {
    if (is_ancestor_manager($currentUserId, $user['id'], $userRepo, $departmentRepo, $auth)) {
        $assignableUsers[] = $user;
    }
}
if ($preselectedOwnerId && !in_array($preselectedOwnerId, array_column($assignableUsers, 'id'))) {
    $userToPreselect = $userRepo->find($preselectedOwnerId);
    if ($userToPreselect) {
        $assignableUsers[] = $userToPreselect;
    }
}
?>

<h1>Taktické úkoly a dohody</h1>
<p class="description">Zde vidíte úkoly přiřazené vám a úkoly, které jste zadali svému týmu.</p>

<?php if (isset($_SESSION['error_message'])): ?>
    <article class="pico-color-red-500" role="alert" style="padding: 1rem; margin-bottom: 1rem;">
        <strong>Chyba:</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
    </article>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>


<!-- My Tasks -->
<h2>Moje Úkoly</h2>
<article>
    <figure>
        <table>
            <thead>
                <tr>
                    <th>Téma</th>
                    <th>Zadavatel</th>
                    <th>Termín</th>
                    <th>Status</th>
                    <th>Kontext</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($myActionItems)): ?>
                    <tr><td colspan="5">Zatím vám nebyly přiřazeny žádné úkoly.</td></tr>
                <?php else: ?>
                    <?php foreach ($myActionItems as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                            <td><?= htmlspecialchars($item['creator_name']) ?></td>
                            <td><?= $item['due_date'] ? date('j. n. Y', strtotime($item['due_date'])) : '—' ?></td>
                            <td>
                                <form method="POST" action="index.php?page=action_items" style="margin: 0;">
                                    <input type="hidden" name="action" value="update_action_item_status">
                                    <input type="hidden" name="action_item_id" value="<?= $item['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= ($item['status'] == $value) ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><?= htmlspecialchars($item['context'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </figure>
</article>


<!-- Team Tasks & Management Form -->
<?php if (!empty($assignableUsers) || $auth->isAdmin()): // Show this section only to managers/admins ?>
    <hr>
    <h2>Úkoly Mého Týmu</h2>
    <p>Zde můžete zadávat nové úkoly a spravovat úkoly členů vašeho týmu.</p>

    <article>
        <form method="POST" action="index.php?page=action_items">
            <h3><?= $editingActionItem ? 'Upravit taktický úkol' : 'Vytvořit nový taktický úkol' ?></h3>

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

    <figure>
        <table>
            <thead>
                <tr>
                    <th>Téma</th>
                    <th>Majitel</th>
                    <th>Termín</th>
                    <th>Status</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teamActionItems)): ?>
                    <tr><td colspan="5">Zatím jste nezadali žádné úkoly svému týmu.</td></tr>
                <?php else: ?>
                    <?php foreach ($teamActionItems as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                            <td><?= htmlspecialchars($item['owner_name']) ?></td>
                            <td><?= $item['due_date'] ? date('j. n. Y', strtotime($item['due_date'])) : '—' ?></td>
                            <td>
                                <span class="pico-badge-dot" style="background-color: <?= match($item['status']) { 'new' => '#007bff', 'in_progress' => '#ffc107', 'completed' => '#28a745' } ?>;"></span>
                                <?= $statusOptions[$item['status']] ?? 'Neznámý' ?>
                            </td>
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
<?php endif; ?>