<?php
$pageTitle = 'Můj tým';

/** @var UserRepository $userRepo */
/** @var Auth $auth */

$currentUserId = $auth->id();
$subordinates = $userRepo->findAllSubordinates($currentUserId);
?>

<h1>Můj tým</h1>

<article>
    <?php if (empty($subordinates)): ?>
        <p>Nemáte žádné přímé ani nepřímé podřízené v organizační struktuře.</p>
    <?php else: ?>
        <h2>Vaši podřízení</h2>
        <ul>
            <?php foreach ($subordinates as $subordinate): ?>
                <li>
                    <strong><?= htmlspecialchars($subordinate['full_name']) ?></strong>
                    (<?= htmlspecialchars($subordinate['email']) ?>) -
                    <a href="index.php?page=subordinate_detail&user_id=<?= $subordinate['id'] ?>" role="button" class="contrast outline">Detail</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>