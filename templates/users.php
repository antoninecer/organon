<?php
$pageTitle = 'Správa uživatelů';

/** @var UserRepository $userRepo */
/** @var DepartmentRepository $departmentRepo */

// Fetch data for the view
$users = $userRepo->findAll();
$departments = $departmentRepo->findAll();

// Determine if we are editing or creating
$editingUser = null;
if (isset($_GET['edit_id'])) {
    $editingUser = $userRepo->find((int)$_GET['edit_id']);
}
?>

<h1>Správa uživatelů</h1>

<article>
    <form method="POST" action="index.php?page=users">
        <h2 id="form-title"><?= $editingUser ? 'Upravit uživatele' : 'Vytvořit nového uživatele' ?></h2>

        <?php if ($editingUser): ?>
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="id" value="<?= $editingUser['id'] ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="create_user">
        <?php endif; ?>

        <div class="grid">
            <label for="full_name">
                Celé jméno
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($editingUser['full_name'] ?? '') ?>" required>
            </label>
            <label for="username">
                Uživatelské jméno
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($editingUser['username'] ?? '') ?>" required>
            </label>
        </div>

        <label for="password">
            Heslo <?= $editingUser ? '(vyplňte pouze pro změnu)' : '' ?>
            <input type="password" id="password" name="password" <?= $editingUser ? '' : 'required' ?>>
        </label>
        
        <div class="grid">
            <button type="submit"><?= $editingUser ? 'Uložit změny' : 'Vytvořit uživatele' ?></button>
            <?php if ($editingUser): ?>
                <a href="index.php?page=users" role="button" class="secondary">Zrušit úpravy</a>
            <?php endif; ?>
        </div>
    </form>
</article>

<h2>Existující uživatelé</h2>
<figure>
    <table>
        <thead>
            <tr>
                <th>Celé jméno</th>
                <th>Uživatelské jméno</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="3">Zatím nebyli vytvořeni žádní uživatelé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td>
                            <div class="grid">
                                <a href="index.php?page=users&edit_id=<?= $user['id'] ?>" role="button" class="contrast outline">Upravit</a>
                                <?php if ($user['id'] !== $auth->id()): // Prevent deleting yourself ?>
                                <form method="POST" action="index.php?page=users" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="contrast" onclick="return confirm('Opravdu chcete smazat tohoto uživatele?')">Smazat</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>
