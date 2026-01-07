<?php $pageTitle = 'Přihlášení'; ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--card-background-color);
        }
        main.container {
            width: 100%;
            max-width: 450px;
        }
        .error {
            color: var(--del-color);
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="container">
        <article>
            <h1 style="text-align: center;">Organon</h1>
            <?php if (isset($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST" action="index.php?action=login">
                <label for="username">
                    Uživatelské jméno
                    <input type="text" id="username" name="username" required>
                </label>

                <label for="password">
                    Heslo
                    <input type="password" id="password" name="password" required>
                </label>

                <button type="submit">Přihlásit se</button>
            </form>
        </article>
    </main>
</body>
</html>
