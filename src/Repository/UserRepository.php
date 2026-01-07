<?php

require_once __DIR__ . '/../Database.php';

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all users and their associated departments.
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                u.id,
                u.full_name,
                u.username,
                u.email,
                GROUP_CONCAT(d.name, ', ') as department_names
            FROM
                users u
            LEFT JOIN
                user_departments ud ON u.id = ud.user_id
            LEFT JOIN
                departments d ON ud.department_id = d.id
            GROUP BY
                u.id, u.full_name, u.username, u.email
            ORDER BY
                u.full_name
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Find a single user by ID, including their department associations.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.*, 
                GROUP_CONCAT(ud.department_id) as department_ids
            FROM users u
            LEFT JOIN user_departments ud ON u.id = ud.user_id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        // Convert department_ids string to an array of integers
        if ($user && !empty($user['department_ids'])) {
            $user['department_ids'] = array_map('intval', explode(',', $user['department_ids']));
        } else if ($user) {
            $user['department_ids'] = [];
        }
        
        return $user;
    }
    
    /**
     * Save a user (create or update).
     * @param array $data User data including [id, username, full_name, email, password, department_id]
     * @return bool
     */
    public function save(array $data): bool
    {
        $this->pdo->beginTransaction();

        try {
            $userId = $data['id'] ?? null;

            // Step 1: Insert or Update the user in the 'users' table
            if ($userId) { // Update existing user
                if (!empty($data['password'])) {
                    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, password_hash = ? WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$data['username'], $data['full_name'], $data['email'], $passwordHash, $userId]);
                } else {
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ? WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$data['username'], $data['full_name'], $data['email'], $userId]);
                }
            } else { // Create new user
                $passwordHash = password_hash($data['password'] ?? 'password', PASSWORD_DEFAULT); // Default password if not set
                $sql = "INSERT INTO users (username, full_name, email, password_hash) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$data['username'], $data['full_name'], $data['email'], $passwordHash]);
                $userId = $this->pdo->lastInsertId();
            }

            // Step 2: Manage department assignments in 'user_departments'
            $newDeptId = $data['department_id'] ?? null;

            // First, unconditionally remove all existing assignments for the user.
            // This robustly handles the case where a user should become unassigned.
            $this->pdo->prepare("DELETE FROM user_departments WHERE user_id = ?")->execute([$userId]);

            // If a new, valid department ID was provided, insert the new assignment.
            if (!empty($newDeptId)) {
                $stmt = $this->pdo->prepare("INSERT INTO user_departments (user_id, department_id) VALUES (?, ?)");
                $stmt->execute([$userId, $newDeptId]);
            }
            
            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // In a real app, you'd log this error.
            error_log("Error in UserRepository::save: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a user by ID.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Foreign key constraints will handle the cleanup.
        // manager_id in 'departments' is set to NULL on delete.
        // rows in 'user_departments' are deleted on cascade.
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}