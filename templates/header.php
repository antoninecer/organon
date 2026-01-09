<?php
$pageTitle = $pageTitle ?? 'Organon';
$user = $auth->user();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        body > nav {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .container {
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <?php if ($auth->check()): ?>
    <nav>
        <ul>
            <li><strong>Organon</strong></li>
        </ul>
        <ul>
            <li><a href="index.php?page=dashboard">Dashboard</a></li>
            <li><a href="index.php?page=my_team">Můj tým</a></li>
            <li><a href="index.php?page=goals">Cíle</a></li>
            <li><a href="index.php?page=action_items">Úkoly</a></li>
            <li><a href="index.php?page=recognitions">Pochvaly</a></li>
            <li><a href="index.php?page=reviews">Hodnocení</a></li>
            <?php if ($auth->isAdmin()): ?>
                <li><a href="index.php?page=departments">Oddělení</a></li>
                <li><a href="index.php?page=users">Uživatelé</a></li>
            <?php endif; ?>
            <li>
                <details role="list" dir="rtl">
                    <summary aria-haspopup="listbox" role="link"><?= htmlspecialchars($user['full_name']) ?></summary>
                    <ul role="listbox">
                        <li><a href="index.php?action=logout">Odhlásit se</a></li>
                    </ul>
                </details>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <main class="container">
