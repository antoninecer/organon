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

<hr>

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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</article>

<hr>

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

<hr>

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