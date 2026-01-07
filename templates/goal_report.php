<?php
$pageTitle = 'Reporty cíle';

/** @var GoalRepository $goalRepo */
/** @var GoalReportRepository $goalReportRepo */
/** @var UserRepository $userRepo */
/** @var Auth $auth */

$goalId = (int)$_GET['goal_id'] ?? 0;
$goal = $goalRepo->find($goalId);

if (!$goal) {
    echo "<h1>Cíl nenalezen.</h1>";
    exit;
}

$reports = $goalReportRepo->findAllByGoal($goalId);

$editingReport = null;
if (isset($_GET['edit_id'])) {
    $editingReport = $goalReportRepo->find((int)$_GET['edit_id']);
}

$riskLevelOptions = [
    'low' => 'Nízké',
    'medium' => 'Střední',
    'high' => 'Vysoké'
];
?>

<h1>Reporty pro cíl: "<?= htmlspecialchars($goal['title']) ?>"</h1>

<p><a href="index.php?page=goals" role="button" class="secondary">Zpět na cíle</a></p>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="pico-color-red-500" role="alert">
        <strong>Chyba:</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<article>
    <form method="POST" action="index.php?page=goal_report&goal_id=<?= $goalId ?>">
        <h2><?= $editingReport ? 'Upravit report' : 'Přidat nový report' ?></h2>

        <input type="hidden" name="action" value="save_goal_report">
        <input type="hidden" name="goal_id" value="<?= $goalId ?>">
        <?php if ($editingReport): ?>
            <input type="hidden" name="id" value="<?= $editingReport['id'] ?>">
        <?php endif; ?>

        <label for="report_date">
            Datum reportu
            <input type="date" id="report_date" name="report_date" value="<?= htmlspecialchars($editingReport['report_date'] ?? date('Y-m-d')) ?>" required>
        </label>
        
        <label for="value">
            Nahlášená hodnota
            <input type="number" step="any" id="value" name="value" value="<?= htmlspecialchars($editingReport['value'] ?? '') ?>">
            <small>Aktuální hodnota k datu reportu (dle typu metriky).</small>
        </label>

        <label for="comment">
            Komentář ("Proč?")
            <textarea id="comment" name="comment" required><?= htmlspecialchars($editingReport['comment'] ?? '') ?></textarea>
            <small>Vysvětlení aktuálního stavu a případných objektivních důvodů.</small>
        </label>

        <label for="plan_next_week">
            Plán do příště ("Co udělám příští týden?")
            <textarea id="plan_next_week" name="plan_next_week"><?= htmlspecialchars($editingReport['plan_next_week'] ?? '') ?></textarea>
        </label>

        <label for="risk_level">
            Úroveň rizika
            <select id="risk_level" name="risk_level" required>
                <?php foreach ($riskLevelOptions as $value => $label): ?>
                    <option value="<?= $value ?>" <?= (($editingReport['risk_level'] ?? 'low') == $value) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="grid">
            <button type="submit"><?= $editingReport ? 'Uložit změny' : 'Přidat report' ?></button>
            <?php if ($editingReport): ?>
                <a href="index.php?page=goal_report&goal_id=<?= $goalId ?>" role="button" class="secondary">Zrušit</a>
            <?php endif; ?>
        </div>
    </form>
</article>

<h2>Historie reportů</h2>
<figure>
    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Hodnota</th>
                <th>Komentář</th>
                <th>Plán do příště</th>
                <th>Riziko</th>
                <th>Nahlásil</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="7">Zatím nebyly zadány žádné reporty k tomuto cíli.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?= date('j. n. Y', strtotime($report['report_date'])) ?></td>
                        <td><?= htmlspecialchars($report['value'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($report['comment']) ?></td>
                        <td><?= htmlspecialchars($report['plan_next_week'] ?? '—') ?></td>
                        <td>
                            <span class="pico-badge-dot" style="background-color: <?= match($report['risk_level']) { 'low' => '#28a745', 'medium' => '#ffc107', 'high' => '#dc3545' } ?>;"></span>
                            <?= $riskLevelOptions[$report['risk_level']] ?? 'Neznámé' ?>
                        </td>
                        <td><?= htmlspecialchars($report['reported_by_name']) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php if ($report['reported_by_id'] == $auth->id()): // Only the reporter can edit/delete ?>
                                    <a href="index.php?page=goal_report&goal_id=<?= $goalId ?>&edit_id=<?= $report['id'] ?>" role="button" class="contrast outline">Edit</a>
                                    <form method="POST" action="index.php?page=goal_report&goal_id=<?= $goalId ?>" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_goal_report">
                                        <input type="hidden" name="id" value="<?= $report['id'] ?>">
                                        <button type="submit" class="contrast" onclick="return confirm('Opravdu chcete smazat tento report?')">Smazat</button>
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
