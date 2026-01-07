<?php
$pageTitle = 'Správa oddělení';

/** @var DepartmentRepository $departmentRepo */
/** @var UserRepository $userRepo */

// Fetch data for the view
$departments = $departmentRepo->findAll();
$users = $userRepo->findAll();

// Determine if we are editing or creating
$editingDepartment = null;
if (isset($_GET['edit_id'])) {
    $editingDepartment = $departmentRepo->find((int)$_GET['edit_id']);
}
?>

<h1>Správa oddělení (Organigram)</h1>

<article>
    <form method="POST" action="index.php?page=departments">
        <h2 id="form-title"><?= $editingDepartment ? 'Upravit oddělení' : 'Vytvořit nové oddělení' ?></h2>

        <?php if ($editingDepartment): ?>
            <input type="hidden" name="action" value="update_department">
            <input type="hidden" name="id" value="<?= $editingDepartment['id'] ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="create_department">
        <?php endif; ?>

        <label for="name">
            Název oddělení
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($editingDepartment['name'] ?? '') ?>" required>
        </label>

        <div class="grid">
            <label for="parent_id">
                Nadřazené oddělení
                <select id="parent_id" name="parent_id">
                    <option value="">Žádné</option>
                    <?php foreach ($departments as $dep): ?>
                        <?php // Prevent a department from being its own parent in the dropdown ?>
                        <?php if ($editingDepartment && $editingDepartment['id'] === $dep['id']) continue; ?>
                        <option value="<?= $dep['id'] ?>" <?= (($editingDepartment['parent_id'] ?? null) == $dep['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dep['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label for="manager_id">
                Manažer
                <select id="manager_id" name="manager_id">
                    <option value="">Žádný</option>
                    <?php foreach ($users as $user): ?>
                         <option value="<?= $user['id'] ?>" <?= (($editingDepartment['manager_id'] ?? null) == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="grid">
            <button type="submit"><?= $editingDepartment ? 'Uložit změny' : 'Vytvořit oddělení' ?></button>
            <?php if ($editingDepartment): ?>
                <a href="index.php?page=departments" role="button" class="secondary">Zrušit úpravy</a>
            <?php endif; ?>
        </div>
    </form>
</article>

<h2>Existující oddělení</h2>
<figure>
    <table>
        <thead>
            <tr>
                <th>Název</th>
                <th>Nadřazené oddělení</th>
                <th>Manažer</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="4">Zatím nebyla vytvořena žádná oddělení.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($departments as $department): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($department['name']) ?></strong></td>
                        <td><?= htmlspecialchars($department['parent_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($department['manager_name'] ?? '—') ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="index.php?page=departments&edit_id=<?= $department['id'] ?>" role="button" class="contrast outline" style="margin-bottom: 0;">Edit</a>
                                <form method="POST" action="index.php?page=departments" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_department">
                                    <input type="hidden" name="id" value="<?= $department['id'] ?>">
                                    <button type="submit" class="contrast" onclick="return confirm('Opravdu chcete smazat toto oddělení?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>
