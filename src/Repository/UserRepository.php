<?php

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all users.
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, full_name, username FROM users ORDER BY full_name");
        return $stmt->fetchAll();
    }

    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create(string $username, string $fullName, string $password): bool
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, full_name, password_hash) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$username, $fullName, $passwordHash]);
    }
    
    public function update(int $id, string $username, string $fullName, ?string $password): bool
    {
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, full_name = ?, password_hash = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$username, $fullName, $passwordHash, $id]);
        } else {
            $sql = "UPDATE users SET username = ?, full_name = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$username, $fullName, $id]);
        }
    }
    
    public function delete(int $id): bool
    {
        // Set manager_id to null in departments table for the user being deleted
        $this->pdo->prepare("UPDATE departments SET manager_id = NULL WHERE manager_id = ?")
                  ->execute([$id]);

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
