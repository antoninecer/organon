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
require_once __DIR__ . '/../src/Helpers/GoalPermissions.php';

$auth = new Auth();
$departmentRepo = new DepartmentRepository();
$userRepo = new UserRepository();
$goalRepo = new GoalRepository();
$actionItemRepo = new ActionItemRepository();
$recognitionRepo = new RecognitionRepository();

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
    $name = $_POST['name'] ?? '';
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    $departmentRepo->create($name, $parentId, $managerId);
    header('Location: index.php?page=departments');
    exit;
}

if ($action === 'update_department' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = $_POST['name'] ?? '';
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    $departmentRepo->update($id, $name, $parentId, $managerId);
    header('Location: index.php?page=departments');
    exit;
}

if ($action === 'delete_department' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $departmentRepo->delete($id);
    header('Location: index.php?page=departments');
    exit;
}

// User Actions
if ($action === 'save_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'username' => $_POST['username'],
        'full_name' => $_POST['full_name'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
    ];
    $userRepo->save($userData);
    header('Location: index.php?page=users');
    exit;
}

if ($action === 'delete_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (!is_ancestor_manager($uploaderId, $assigneeId, $userRepo, $departmentRepo)) {
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

// Action Item Actions
if ($action === 'save_action_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaderId = $auth->id();
    $ownerId = (int)$_POST['owner_id'];

    if (!is_ancestor_manager($uploaderId, $ownerId, $userRepo, $departmentRepo)) {
        $_SESSION['error_message'] = 'Nemáte oprávnění přidělit úkol tomuto uživateli.';
        header('Location: index.php?page=action_items');
        exit;
    }

    $actionItemData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'title' => $_POST['title'],
        'owner_id' => $ownerId,
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
    $actionItemRepo->delete($id);
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

    case 'action_items':
        include __DIR__ . '/../templates/action_items.php';
        break;

    case 'recognitions':
        include __DIR__ . '/../templates/recognitions.php';
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
