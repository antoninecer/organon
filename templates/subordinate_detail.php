<?php
$pageTitle = 'Detail podřízeného';

/** @var UserRepository $userRepo */
/** @var GoalRepository $goalRepo */
/** @var ActionItemRepository $actionItemRepo */
/** @var RecognitionRepository $recognitionRepo */
/** @var Auth $auth */ // Needed for status options

$subordinateId = (int)$_GET['user_id'] ?? 0;
$subordinateUser = $userRepo->find($subordinateId);

if (!$subordinateUser) {
    echo "<h1>Uživatel nenalezen.</h1>";
    exit;
}

$subordinateGoals = $goalRepo->findByAssignee($subordinateId);
$subordinateActionItems = $actionItemRepo->findByOwner($subordinateId);
$subordinateRecognitions = $recognitionRepo->findByReceiver($subordinateId);

$goalStatusOptions = [
    'new' => 'Nový',
    'in_progress' => 'V řešení',
    'completed' => 'Hotovo'
];

$actionItemStatusOptions = [
    'new' => 'Nový',
    'in_progress' => 'V řešení',
    'completed' => 'Hotovo'
];
?>

<h1>Detail podřízeného: <?= htmlspecialchars($subordinateUser['full_name']) ?></h1>

<div class="grid">
    <a href="index.php?page=goals&assignee_id=<?= $subordinateId ?>" role="button">Přidat cíl</a>
    <a href="index.php?page=action_items&owner_id=<?= $subordinateId ?>" role="button" class="secondary">Přidat úkol</a>
    <a href="index.php?page=recognitions&receiver_id=<?= $subordinateId ?>" role="button" class="contrast outline">Udělit pochvalu</a>
</div>

<p>
    **Email:** <?= htmlspecialchars($subordinateUser['email'] ?? 'N/A') ?><br>
    **Uživatelské jméno:** <?= htmlspecialchars($subordinateUser['username'] ?? 'N/A') ?>
</p>

<h2>Cíle</h2>
<article>
    <?php if (empty($subordinateGoals)): ?>
        <p>Tomuto uživateli nejsou přiřazeny žádné cíle.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Cíl</th>
                    <th>Zadavatel</th>
                    <th>Termín</th>
                    <th>Status</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subordinateGoals as $goal): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($goal['title']) ?></strong><br><small><?= htmlspecialchars($goal['description']) ?></small></td>
                        <td><?= htmlspecialchars($goal['manager_name']) ?></td>
                        <td><?= $goal['due_date'] ? date('j. n. Y', strtotime($goal['due_date'])) : '—' ?></td>
                        <td>
                            <span class="pico-badge-dot" style="background-color: <?= match($goal['status']) { 'new' => '#007bff', 'in_progress' => '#ffc107', 'completed' => '#28a745' } ?>;"></span>
                            <?= $goalStatusOptions[$goal['status']] ?? 'Neznámý' ?>
                        </td>
                        <td><a href="index.php?page=goal_report&goal_id=<?= $goal['id'] ?>">Reporty</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</article>

<h2>Úkoly</h2>
<article>
    <?php if (empty($subordinateActionItems)): ?>
        <p>Tomuto uživateli nejsou přiřazeny žádné úkoly.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Téma</th>
                    <th>Termín</th>
                    <th>Status</th>
                    <th>Kontext</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subordinateActionItems as $item): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                        <td><?= $item['due_date'] ? date('j. n. Y', strtotime($item['due_date'])) : '—' ?></td>
                        <td>
                            <span class="pico-badge-dot" style="background-color: <?= match($item['status']) { 'new' => '#007bff', 'in_progress' => '#ffc107', 'completed' => '#28a745' } ?>;"></span>
                            <?= $actionItemStatusOptions[$item['status']] ?? 'Neznámý' ?>
                        </td>
                        <td><?= htmlspecialchars($item['context'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</article>

<h2>Pochvaly</h2>
<article>
    <?php if (empty($subordinateRecognitions)): ?>
        <p>Tento uživatel zatím neobdržel žádné pochvaly.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($subordinateRecognitions as $recognition): ?>
                <li>
                    <?= htmlspecialchars($recognition['message']) ?> od
                    <strong><?= htmlspecialchars($recognition['giver_name']) ?></strong>
                    (<?= date('j. n. Y', strtotime($recognition['created_at'])) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>

<h2>Poznámky 1:1</h2>
<article>
    <?php
    $oneOnOneNotes = $oneOnOneNoteRepo->findAllForUser($subordinateId);
    ?>

    <?php if (empty($oneOnOneNotes)): ?>
        <p>Pro tohoto uživatele zatím nejsou žádné poznámky 1:1.</p>
    <?php else: ?>
        <?php foreach ($oneOnOneNotes as $note): ?>
            <blockquote style="margin-bottom: 1rem; border-left-color: #607d8b;">
                <p><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                <footer>
                    <small>
                        Poznámka z <strong><?= date('j. n. Y', strtotime($note['note_date'])) ?></strong>
                        od manažera: <strong><?= htmlspecialchars($note['manager_name']) ?></strong>
                        <?php if ($note['manager_id'] === $auth->id()): ?>
                            <br>
                            <form action="index.php" method="post" style="display: inline-block; margin-top: 10px;">
                                <input type="hidden" name="action" value="delete_one_on_one_note">
                                <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                <input type="hidden" name="subordinate_id" value="<?= $subordinateId ?>">
                                <button type="submit" onclick="return confirm('Opravdu chcete smazat tuto poznámku?')" class="contrast outline">Smazat</button>
                            </form>
                            <button type="button" class="contrast outline" onclick="editOneOnOneNote(<?= $note['id'] ?>, '<?= $note['note_date'] ?>', '<?= addslashes(htmlspecialchars($note['note'])) ?>')">Upravit</button>
                        <?php endif; ?>
                    </small>
                </footer>
            </blockquote>
        <?php endforeach; ?>
    <?php endif; ?>
</article>

<h2 id="add_edit_note_form_title">Přidat novou poznámku 1:1</h2>
<form action="index.php" method="post">
    <input type="hidden" name="action" value="save_one_on_one_note">
    <input type="hidden" name="subordinate_id" value="<?= $subordinateId ?>">
    <input type="hidden" name="id" id="note_id" value="">

    <label for="note_date">Datum poznámky</label>
    <input type="date" id="note_date" name="note_date" value="<?= date('Y-m-d') ?>" required>

    <label for="note_content">Poznámka</label>
    <textarea id="note_content" name="note" rows="5" required></textarea>

    <button type="submit">Uložit poznámku</button>
    <button type="button" id="cancel_edit_note" class="secondary" style="display:none;" onclick="cancelEditOneOnOneNote()">Zrušit úpravu</button>
</form>

<script>
    function editOneOnOneNote(id, date, content) {
        document.getElementById('note_id').value = id;
        document.getElementById('note_date').value = date;
        document.getElementById('note_content').value = content;
        document.getElementById('add_edit_note_form_title').innerText = 'Upravit poznámku 1:1';
        document.getElementById('cancel_edit_note').style.display = 'inline';
        window.scrollTo({ top: document.getElementById('add_edit_note_form_title').offsetTop, behavior: 'smooth' });
    }

    function cancelEditOneOnOneNote() {
        document.getElementById('note_id').value = '';
        document.getElementById('note_date').value = '<?= date('Y-m-d') ?>';
        document.getElementById('note_content').value = '';
        document.getElementById('add_edit_note_form_title').innerText = 'Přidat novou poznámku 1:1';
        document.getElementById('cancel_edit_note').style.display = 'none';
    }
</script>