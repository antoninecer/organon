<?php

// Front Controller

// --- Initialization ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Repository/DepartmentRepository.php';
require_once __DIR__ . '/../src/Repository/UserRepository.php';

$auth = new Auth();
$departmentRepo = new DepartmentRepository();
$userRepo = new UserRepository();

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
if ($action === 'create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRepo->create($_POST['username'], $_POST['full_name'], $_POST['password']);
    header('Location: index.php?page=users');
    exit;
}

if ($action === 'update_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRepo->update((int)$_POST['id'], $_POST['username'], $_POST['full_name'], $_POST['password']);
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
