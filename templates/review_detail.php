<?php
$pageTitle = 'Detail Hodnocení';

/** @var ReviewRepository $reviewRepo */
/** @var UserRepository $userRepo */
/** @var GoalRepository $goalRepo */
/** @var ActionItemRepository $actionItemRepo */
/** @var RecognitionRepository $recognitionRepo */
/** @var Auth $auth */

$reviewId = (int)$_GET['id'] ?? 0;
$review = $reviewRepo->find($reviewId);

if (!$review) {
    echo "<h1>Hodnocení nebylo nalezeno.</h1>";
    exit;
}

// Security Check: Only the manager, the user being reviewed, or an admin can see this page.
$isManager = $review['manager_id'] === $auth->id();
$isReviewedUser = $review['user_id'] === $auth->id();
if (!$auth->isAdmin() && !$isManager && !$isReviewedUser) {
    _unauthorized();
}

// A review is editable only by the manager and only if it's a draft.
$isEditable = $isManager && $review['status'] === 'draft';

$reviewedUser = $userRepo->find($review['user_id']);

// Fetch all items for the user
$goals = $goalRepo->findByAssignee($review['user_id']);
$actionItems = $actionItemRepo->findByOwner($review['user_id']);
$recognitions = $recognitionRepo->findByReceiver($review['user_id']);

// Fetch items that are already linked to this review
$linkedItems = $reviewRepo->findReviewItems($reviewId);
$linkedGoalIds = $linkedItems['goal'];
$linkedActionItemIds = $linkedItems['action_item'];
$linkedRecognitionIds = $linkedItems['recognition'];

?>

<header>
    <h1>Hodnocení pro: <?= htmlspecialchars($reviewedUser['full_name']) ?></h1>
    <h2>Období: <?= htmlspecialchars($review['review_period']) ?></h2>
    <p><strong>Stav:</strong> <?= htmlspecialchars($review['status']) ?></p>
</header>

<form action="index.php" method="POST">
    <input type="hidden" name="action" value="save_review">
    <input type="hidden" name="review_id" value="<?= $reviewId ?>">

    <article>
        <h3>Vyberte podklady pro hodnocení</h3>
        <p>Zvolte položky, které jsou pro toto hodnotící období relevantní.</p>
        
        <h4>Strategické Cíle</h4>
        <?php foreach($goals as $goal): ?>
            <label>
                <input type="checkbox" name="goals[]" value="<?= $goal['id'] ?>" 
                    <?= in_array($goal['id'], $linkedGoalIds) ? 'checked' : '' ?>
                    <?= !$isEditable ? 'disabled' : '' ?>>
                <strong><?= htmlspecialchars($goal['title']) ?></strong> (Stav: <?= htmlspecialchars($goal['status']) ?>)
                <br><small><?= htmlspecialchars($goal['description']) ?></small>
            </label>
        <?php endforeach; ?>
        <?php if(empty($goals)): ?><p>Žádné cíle k zobrazení.</p><?php endif; ?>

        <hr>

        <h4>Taktické Úkoly</h4>
        <?php foreach($actionItems as $item): ?>
            <label>
                <input type="checkbox" name="action_items[]" value="<?= $item['id'] ?>"
                    <?= in_array($item['id'], $linkedActionItemIds) ? 'checked' : '' ?>
                    <?= !$isEditable ? 'disabled' : '' ?>>
                <strong><?= htmlspecialchars($item['title']) ?></strong> (Stav: <?= htmlspecialchars($item['status']) ?>)
            </label>
        <?php endforeach; ?>
        <?php if(empty($actionItems)): ?><p>Žádné úkoly k zobrazení.</p><?php endif; ?>

        <hr>

        <h4>Pochvaly</h4>
        <?php foreach($recognitions as $rec): ?>
             <label>
                <input type="checkbox" name="recognitions[]" value="<?= $rec['id'] ?>"
                    <?= in_array($rec['id'], $linkedRecognitionIds) ? 'checked' : '' ?>
                    <?= !$isEditable ? 'disabled' : '' ?>>
                <?= htmlspecialchars($rec['message']) ?> (od: <?= htmlspecialchars($rec['giver_name']) ?>)
            </label>
        <?php endforeach; ?>
        <?php if(empty($recognitions)): ?><p>Žádné pochvaly k zobrazení.</p><?php endif; ?>
    </article>

    <article>
        <h3>Slovní hodnocení</h3>
        <label for="strengths_summary">Silné stránky</label>
        <textarea id="strengths_summary" name="strengths_summary" <?= !$isEditable ? 'readonly' : '' ?>><?= htmlspecialchars($review['strengths_summary'] ?? '') ?></textarea>
        <small>Zde popište, v čem se zaměstnanci dařilo. Buďte konkrétní a uveďte příklady (např. "Excelentní vedení projektu X", "Proaktivní přístup k řešení problému Y").</small>
        
        <label for="weaknesses_summary">Slabé stránky a oblasti pro rozvoj</label>
        <textarea id="weaknesses_summary" name="weaknesses_summary" <?= !$isEditable ? 'readonly' : '' ?>><?= htmlspecialchars($review['weaknesses_summary'] ?? '') ?></textarea>
        <small>Konstruktivně pojmenujte oblasti, kde je prostor pro zlepšení. Zaměřte se na chování a výsledky, ne na osobnost.</small>
        
        <label for="development_plan">Plán rozvoje na další období</label>
        <textarea id="development_plan" name="development_plan" <?= !$isEditable ? 'readonly' : '' ?>><?= htmlspecialchars($review['development_plan'] ?? '') ?></textarea>
        <small>Navrhněte konkrétní kroky, kurzy nebo aktivity, které zaměstnanci pomohou v růstu (např. "Absolvovat kurz pokročilého SQL", "Vést jednu z týmových porad").</small>

        <label for="final_rating">Celkové hodnocení (1-5)</label>
        <input type="number" min="1" max="5" id="final_rating" name="final_rating" value="<?= htmlspecialchars($review['final_rating'] ?? '') ?>" <?= !$isEditable ? 'readonly' : '' ?>>
        <small>1 = Výrazně pod očekáváním, 2 = Pod očekáváním, 3 = Splňuje očekávání, 4 = Nad očekáváním, 5 = Excelentní.</small>
    </article>
    
    <?php if ($isEditable): ?>
    <footer>
        <div class="grid">
            <button type="submit" name="save_draft">Uložit jako koncept</button>
            <button type="submit" name="finalize" class="contrast" onclick="return confirm('Opravdu chcete hodnocení finalizovat? Po tomto kroku již nebude možné provádět úpravy.')">Finalizovat a uzavřít</button>
        </div>
    </footer>
    <?php endif; ?>
</form>
