<?php

// Front Controller

// --- Initialization ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Repository/DepartmentRepository.php';
require_once __DIR__ . '/../src/Repository/UserRepository.php';
require_once __DIR__ . '/../src/Repository/GoalRepository.php';
require_once __DIR__ . '/../src/Repository/ActionItemRepository.php';
require_once __DIR__ . '/../src/Repository/RecognitionRepository.php';
require_once __DIR__ . '/../src/Repository/GoalReportRepository.php';
require_once __DIR__ . '/../src/Repository/OneOnOneNoteRepository.php';
require_once __DIR__ . '/../src/Repository/ReviewRepository.php';

require_once __DIR__ . '/../src/Helpers/GoalPermissions.php';

$auth = new Auth();
$departmentRepo = new DepartmentRepository();
$userRepo = new UserRepository();
$goalRepo = new GoalRepository();
$actionItemRepo = new ActionItemRepository();
$recognitionRepo = new RecognitionRepository();
$goalReportRepo = new GoalReportRepository();
$oneOnOneNoteRepo = new OneOnOneNoteRepository();
$reviewRepo = new ReviewRepository();

/**
 * Redirects user to dashboard with an "Unauthorized" error message.
 */
function _unauthorized() {
    $_SESSION['error_message'] = 'K této akci nemáte oprávnění.';
    header('Location: index.php?page=dashboard');
    exit;
}

// --- Action Handling ---
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// General Actions
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($auth->login($username, $password)) {
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = 'Neplatné uživatelské jméno nebo heslo.';
    }
}

if ($action === 'logout') {
    $auth->logout();
    header('Location: index.php');
    exit;
}

// Department Actions
if ($action === 'create_department' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->isAdmin()) { _unauthorized(); }
    $name = $_POST['name'] ?? '';
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    $departmentRepo->create($name, $parentId, $managerId);
    header('Location: index.php?page=departments');
    exit;
}

if ($action === 'update_department' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->isAdmin()) { _unauthorized(); }
    $id = (int)$_POST['id'];
    $name = $_POST['name'] ?? '';
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    $departmentRepo->update($id, $name, $parentId, $managerId);
    header('Location: index.php?page=departments');
    exit;
}

if ($action === 'delete_department' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->isAdmin()) { _unauthorized(); }
    $id = (int)$_POST['id'];
    $departmentRepo->delete($id);
    header('Location: index.php?page=departments');
    exit;
}

// User Actions
if ($action === 'save_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->isAdmin()) { _unauthorized(); }
    $userData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'username' => $_POST['username'],
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
        'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
    ];
    $userRepo->save($userData);
    header('Location: index.php?page=users');
    exit;
}

if ($action === 'delete_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->isAdmin()) { _unauthorized(); }
    $id = (int)$_POST['id'];
    if ($id !== $auth->id()) { // Extra check to prevent deleting yourself
        $userRepo->delete($id);
    }
    header('Location: index.php?page=users');
    exit;
}

// Goal Actions
if ($action === 'save_goal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaderId = $auth->id();
    $assigneeId = (int)$_POST['assignee_id'];

    if (!is_ancestor_manager($uploaderId, $assigneeId, $userRepo, $departmentRepo, $auth)) {
        $_SESSION['error_message'] = 'Nemáte oprávnění přidělit cíl tomuto uživateli.';
        header('Location: index.php?page=goals');
        exit;
    }

    $goalData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'assignee_id' => $assigneeId,
        'status' => $_POST['status'],
        'due_date' => $_POST['due_date'],
        'metric_type' => $_POST['metric_type'],
        'target_value' => !empty($_POST['target_value']) ? (float)$_POST['target_value'] : null,
        'weight' => (int)$_POST['weight'],
        'evaluation_rule' => $_POST['evaluation_rule'],
        'data_source' => $_POST['data_source'],
        'manager_id' => $uploaderId // The manager is the person creating the goal
    ];
    $goalRepo->save($goalData);
    header('Location: index.php?page=goals');
    exit;
}

if ($action === 'delete_goal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $goalRepo->delete($id);
    header('Location: index.php?page=goals');
    exit;
}

if ($action === 'update_goal_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $goalId = (int)$_POST['goal_id'];
    $status = $_POST['status'];
    $goal = $goalRepo->find($goalId);

    // Security check: Only the assignee can update the status
    if ($goal && $goal['assignee_id'] === $auth->id()) {
        $goalRepo->updateStatus($goalId, $status);
    } else {
        $_SESSION['error_message'] = 'Nemáte oprávnění upravit tento cíl.';
    }
    header('Location: index.php?page=goals');
    exit;
}

// Action Item Actions
if ($action === 'save_action_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $creatorId = $auth->id();
    $ownerId = (int)$_POST['owner_id'];

    if (!is_ancestor_manager($creatorId, $ownerId, $userRepo, $departmentRepo, $auth)) {
        $_SESSION['error_message'] = 'Nemáte oprávnění přidělit úkol tomuto uživateli.';
        header('Location: index.php?page=action_items');
        exit;
    }

    $actionItemData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'title' => $_POST['title'],
        'owner_id' => $ownerId,
        'creator_id' => $creatorId,
        'due_date' => $_POST['due_date'],
        'status' => $_POST['status'],
        'context' => $_POST['context']
    ];
    $actionItemRepo->save($actionItemData);
    header('Location: index.php?page=action_items');
    exit;
}

if ($action === 'delete_action_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    // Optional: Add security check to ensure only creator can delete
    $actionItemRepo->delete($id);
    header('Location: index.php?page=action_items');
    exit;
}

if ($action === 'update_action_item_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionItemId = (int)$_POST['action_item_id'];
    $status = $_POST['status'];
    $item = $actionItemRepo->find($actionItemId);

    // Security check: Only the owner can update the status
    if ($item && $item['owner_id'] === $auth->id()) {
        $actionItemRepo->updateStatus($actionItemId, $status);
    } else {
        $_SESSION['error_message'] = 'Nemáte oprávnění upravit tento úkol.';
    }
    header('Location: index.php?page=action_items');
    exit;
}

// Recognition Actions
if ($action === 'save_recognition' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $recognitionData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'receiver_id' => (int)$_POST['receiver_id'],
        'message' => $_POST['message'],
        'giver_id' => $auth->id() // The giver is the currently logged-in user
    ];
    $recognitionRepo->save($recognitionData);
    header('Location: index.php?page=recognitions');
    exit;
}

if ($action === 'delete_recognition' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $recognitionRepo->delete($id);
    header('Location: index.php?page=recognitions');
    exit;
}

// Goal Report Actions
if ($action === 'save_goal_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $goalId = (int)$_POST['goal_id'];
    $reportData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'goal_id' => $goalId,
        'report_date' => $_POST['report_date'],
        'value' => !empty($_POST['value']) ? (float)$_POST['value'] : null,
        'comment' => $_POST['comment'],
        'plan_next_week' => $_POST['plan_next_week'],
        'risk_level' => $_POST['risk_level'],
        'reported_by_id' => $auth->id() // Who submitted this report
    ];
    $goalReportRepo->save($reportData);
    header('Location: index.php?page=goal_report&goal_id=' . $goalId);
    exit;
}

if ($action === 'delete_goal_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $goalId = (int)$_POST['goal_id'];
    $id = (int)$_POST['id'];
    $goalReportRepo->delete($id);
    header('Location: index.php?page=goal_report&goal_id=' . $goalId);
    exit;
}

// One-on-One Note Actions
if ($action === 'save_one_on_one_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $subordinateId = (int)$_POST['subordinate_id'];
    $managerId = $auth->id(); // The manager is the currently logged-in user

    // Basic authorization check: a manager can only add notes for their direct or indirect subordinates.
    if (!is_ancestor_manager($managerId, $subordinateId, $userRepo, $departmentRepo, $auth) && $managerId !== $subordinateId) {
        $_SESSION['error_message'] = 'Nemáte oprávnění přidávat poznámky tomuto uživateli.';
        header('Location: index.php?page=subordinate_detail&id=' . $subordinateId);
        exit;
    }

    $noteData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'manager_id' => $managerId,
        'subordinate_id' => $subordinateId,
        'note' => $_POST['note'],
        'note_date' => $_POST['note_date']
    ];

    if ($noteData['id']) {
        $oneOnOneNoteRepo->update($noteData['id'], $noteData['note'], $noteData['note_date']);
    } else {
        $oneOnOneNoteRepo->create($noteData['manager_id'], $noteData['subordinate_id'], $noteData['note'], $noteData['note_date']);
    }
    header('Location: index.php?page=subordinate_detail&user_id=' . $subordinateId);
    exit;
}

if ($action === 'delete_one_on_one_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $subordinateId = (int)$_POST['subordinate_id'];
    $note = $oneOnOneNoteRepo->find($id);

    // Only the manager who created the note can delete it
    if ($note && $note['manager_id'] === $auth->id()) {
        $oneOnOneNoteRepo->delete($id);
    } else {
        $_SESSION['error_message'] = 'Nemáte oprávnění smazat tuto poznámku.';
    }
    header('Location: index.php?page=subordinate_detail&user_id=' . $subordinateId);
    exit;
}

// Review Actions
if ($action === 'start_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $managerId = $auth->id();
    $userId = (int)$_POST['user_id'];
    $reviewPeriod = $_POST['review_period'];

    // Security Check: Ensure the person starting the review is a manager of the user being reviewed
    if (!is_ancestor_manager($managerId, $userId, $userRepo, $departmentRepo, $auth)) {
        $_SESSION['error_message'] = 'Nemáte oprávnění zahájit hodnocení pro tohoto uživatele.';
        header('Location: index.php?page=reviews');
        exit;
    }

    $reviewId = $reviewRepo->save([
        'user_id' => $userId,
        'manager_id' => $managerId,
        'review_period' => $reviewPeriod
    ]);

    if ($reviewId) {
        header('Location: index.php?page=review_detail&id=' . $reviewId);
    } else {
        $_SESSION['error_message'] = 'Nepodařilo se zahájit hodnocení.';
        header('Location: index.php?page=reviews');
    }
    exit;
}

if ($action === 'save_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = (int)$_POST['review_id'];
    $review = $reviewRepo->find($reviewId);

    // Security Check: ensure the user is the manager for this review
    if (!$review || $review['manager_id'] !== $auth->id()) {
        _unauthorized();
    }
    
    // Determine if we are finalizing or just saving a draft
    $status = isset($_POST['finalize']) ? 'finalized' : 'draft';

    $data = [
        'id' => $reviewId,
        'strengths_summary' => $_POST['strengths_summary'],
        'weaknesses_summary' => $_POST['weaknesses_summary'],
        'development_plan' => $_POST['development_plan'],
        'final_rating' => (int)$_POST['final_rating'],
        'status' => $status,
        'goals' => $_POST['goals'] ?? [],
        'action_items' => $_POST['action_items'] ?? [],
        'recognitions' => $_POST['recognitions'] ?? []
    ];
    
    if ($reviewRepo->save($data)) {
        $_SESSION['success_message'] = 'Hodnocení bylo uloženo.';
    } else {
        $_SESSION['error_message'] = 'Chyba při ukládání hodnocení.';
    }
    header('Location: index.php?page=reviews');
    exit;
}


// --- Page Routing & Security ---
$page = $_GET['page'] ?? 'dashboard';

// If not logged in, force login page
if (!$auth->check()) {
    $page = 'login';
}
// If user is logged in but tries to access login page, redirect to dashboard
if ($auth->check() && $page === 'login') {
    header('Location: index.php?page=dashboard');
    exit;
}

// Load main layout header if user is authenticated
if ($auth->check()){
    include __DIR__ . '/../templates/header.php';
}

// Simple router
switch ($page) {
    case 'dashboard':
        include __DIR__ . '/../templates/dashboard.php';
        break;

    case 'my_team':
        include __DIR__ . '/../templates/my_team.php';
        break;

    case 'subordinate_detail':
        include __DIR__ . '/../templates/subordinate_detail.php';
        break;

    case 'goals':
        include __DIR__ . '/../templates/goals.php';
        break;

    case 'goal_report':
        include __DIR__ . '/../templates/goal_report.php';
        break;

    case 'action_items':
        include __DIR__ . '/../templates/action_items.php';
        break;

    case 'recognitions':
        include __DIR__ . '/../templates/recognitions.php';
        break;
    
    case 'reviews':
        include __DIR__ . '/../templates/reviews.php';
        break;

    case 'review_detail':
        include __DIR__ . '/../templates/review_detail.php';
        break;

    case 'departments':
        include __DIR__ . '/../templates/departments.php';
        break;

    case 'users':
        include __DIR__ . '/../templates/users.php';
        break;

    case 'login':
        // This case is for when login fails and we need to show the page again
        // or when user is not authenticated
        include __DIR__ . '/../templates/login.php';
        break;

    default:
        // Page not found
        http_response_code(404);
        echo "<h1>404 - Stránka nenalezena</h1>";
        break;
}

// Load main layout footer if user is authenticated
if ($auth->check()){
    include __DIR__ . '/../templates/footer.php';
}
