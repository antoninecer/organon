<?php

require_once __DIR__ . '/Database.php';

class Auth
{
    private ?array $user = null;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($this->check()) {
            $this->user = $_SESSION['user'];
        }
    }

    /**
     * Attempt to log the user in.
     * @param string $username
     * @param string $password
     * @return bool True on success, false on failure.
     */
    public function login(string $username, string $password): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
            ];
            $this->user = $_SESSION['user'];
            return true;
        }

        return false;
    }

    /**
     * Log the user out.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        $this->user = null;
    }

    /**
     * Check if a user is logged in.
     * @return bool
     */
    public function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Get the currently authenticated user's data.
     * @return array|null
     */
    public function user(): ?array
    {
        return $this->user;
    }

    /**
     * Get the currently authenticated user's ID.
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->user['id'] ?? null;
    }
}
