<?php
$pageTitle = 'Správa pochval';

/** @var RecognitionRepository $recognitionRepo */
/** @var UserRepository $userRepo */
/** @var Auth $auth */

// Data for the view
$recognitions = $recognitionRepo->findAll();
$users = $userRepo->findAll(); // All users for receiver dropdown

$editingRecognition = null;
if (isset($_GET['edit_id'])) {
    $editingRecognition = $recognitionRepo->find((int)$_GET['edit_id']);
}

// Determine pre-selected receiver ID (from GET parameter or editing recognition)
$preselectedReceiverId = null;
if (isset($_GET['receiver_id'])) {
    $preselectedReceiverId = (int)$_GET['receiver_id'];
} elseif ($editingRecognition) {
    $preselectedReceiverId = $editingRecognition['receiver_id'];
}
?>

<h1>Správa pochval</h1>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="pico-color-red-500" role="alert">
        <strong>Chyba:</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<article>
    <form method="POST" action="index.php?page=recognitions">
        <h2><?= $editingRecognition ? 'Upravit pochvalu' : 'Vytvořit novou pochvalu' ?></h2>

        <input type="hidden" name="action" value="save_recognition">
        <?php if ($editingRecognition): ?>
            <input type="hidden" name="id" value="<?= $editingRecognition['id'] ?>">
        <?php endif; ?>

        <div class="grid">
            <label for="receiver_id">
                Pro koho je pochvala
                <select id="receiver_id" name="receiver_id" required>
                    <option value="">-- Vyberte příjemce --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($preselectedReceiverId == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        
        <label for="message">
            Zpráva pochvaly
            <textarea id="message" name="message" required><?= htmlspecialchars($editingRecognition['message'] ?? '') ?></textarea>
        </label>

        <div class="grid">
            <button type="submit"><?= $editingRecognition ? 'Uložit změny' : 'Vytvořit pochvalu' ?></button>
            <?php if ($editingRecognition): ?>
                <a href="index.php?page=recognitions" role="button" class="secondary">Zrušit</a>
            <?php endif; ?>
        </div>
    </form>
</article>

<h2>Existující pochvaly</h2>
<figure>
    <table>
        <thead>
            <tr>
                <th>Zpráva</th>
                <th>Od</th>
                <th>Pro</th>
                <th>Datum</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recognitions)): ?>
                <tr>
                    <td colspan="5">Zatím nebyly zadány žádné pochvaly.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recognitions as $recognition): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($recognition['message']) ?></strong></td>
                        <td><?= htmlspecialchars($recognition['giver_name']) ?></td>
                        <td><?= htmlspecialchars($recognition['receiver_name']) ?></td>
                        <td><?= date('j. n. Y', strtotime($recognition['created_at'])) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php if ($recognition['giver_id'] == $auth->id()): // Only giver can edit/delete ?>
                                    <a href="index.php?page=recognitions&edit_id=<?= $recognition['id'] ?>" role="button" class="contrast outline">Edit</a>
                                    <form method="POST" action="index.php?page=recognitions" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_recognition">
                                        <input type="hidden" name="id" value="<?= $recognition['id'] ?>">
                                        <button type="submit" class="contrast" onclick="return confirm('Opravdu chcete smazat tuto pochvalu?')">Delete</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>