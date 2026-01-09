<?php
$pageTitle = 'Hodnocení';

/** @var ReviewRepository $reviewRepo */
/** @var UserRepository $userRepo */
/** @var Auth $auth */

$currentUserId = $auth->id();

// Data for the view
$myReviews = $reviewRepo->findAllForUser($currentUserId);
$teamReviews = $reviewRepo->findAllByManager($currentUserId);

$assignableUsers = $userRepo->findAllSubordinates($currentUserId);

?>

<h1>Hodnocení</h1>
<p class="description">Zde najdete svá minulá hodnocení a můžete spravovat hodnocení členů svého týmu.</p>

<!-- My Past Reviews -->
<h2>Moje Hodnocení</h2>
<article>
    <figure>
        <table>
            <thead>
                <tr>
                    <th>Období</th>
                    <th>Hodnotící Manažer</th>
                    <th>Stav</th>
                    <th>Datum Finalizace</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($myReviews)): ?>
                    <tr><td colspan="5">Zatím nemáte žádná hodnocení.</td></tr>
                <?php else: ?>
                    <?php foreach ($myReviews as $review): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($review['review_period']) ?></strong></td>
                            <td><?= htmlspecialchars($review['manager_name']) ?></td>
                            <td><?= htmlspecialchars($review['status']) ?></td>
                            <td><?= $review['finalized_at'] ? date('j. n. Y', strtotime($review['finalized_at'])) : '—' ?></td>
                            <td>
                                <a href="index.php?page=review_detail&id=<?= $review['id'] ?>" role="button" class="contrast">Zobrazit Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </figure>
</article>

<!-- Team Review Management -->
<?php if (!empty($assignableUsers) || $auth->isAdmin()): ?>
    <hr>
    <h2>Hodnocení Mého Týmu</h2>
    
    <article>
        <h3>Nové Hodnocení</h3>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="start_review">
            <div class="grid">
                <label for="user_id">
                    Hodnotit člena týmu
                    <select id="user_id" name="user_id" required>
                        <option value="">-- Vyberte podřízeného --</option>
                        <?php foreach ($assignableUsers as $subordinate): ?>
                            <option value="<?= $subordinate['id'] ?>"><?= htmlspecialchars($subordinate['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label for="review_period">
                    Hodnotící Období
                    <input type="text" id="review_period" name="review_period" placeholder="např. Q1 2026" required>
                </label>
            </div>
            <button type="submit">Zahájit hodnocení</button>
        </form>
    </article>

    <article>
        <h3>Probíhající a minulá hodnocení týmu</h3>
        <figure>
            <table>
                <thead>
                    <tr>
                        <th>Hodnocený</th>
                        <th>Období</th>
                        <th>Stav</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teamReviews)): ?>
                        <tr><td colspan="4">Zatím jste nevytvořili žádné hodnocení.</td></tr>
                    <?php else: ?>
                        <?php foreach ($teamReviews as $review): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($review['user_name']) ?></strong></td>
                                <td><?= htmlspecialchars($review['review_period']) ?></td>
                                <td><?= htmlspecialchars($review['status']) ?></td>
                                <td>
                                    <a href="index.php?page=review_detail&id=<?= $review['id'] ?>" role="button" class="contrast">
                                        <?= $review['status'] === 'draft' ? 'Pokračovat v úpravách' : 'Zobrazit Detail' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </figure>
    </article>
<?php endif; ?>
