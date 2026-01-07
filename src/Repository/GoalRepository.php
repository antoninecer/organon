<?php

require_once __DIR__ . '/../Database.php';

class GoalRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all goals, joining user names for assignee and manager.
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT 
                g.*,
                assignee.full_name as assignee_name,
                manager.full_name as manager_name
            FROM goals g
            LEFT JOIN users assignee ON g.assignee_id = assignee.id
            LEFT JOIN users manager ON g.manager_id = manager.id
            ORDER BY g.due_date DESC, g.created_at DESC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Find a single goal by its ID.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM goals WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Save a goal (create or update).
     * @param array $data Goal data
     * @return bool
     */
    public function save(array $data): bool
    {
        $id = $data['id'] ?? null;

        if ($id) {
            // Update
            $sql = "UPDATE goals SET title = ?, description = ?, assignee_id = ?, status = ?, due_date = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['title'],
                $data['description'],
                $data['assignee_id'],
                $data['status'],
                empty($data['due_date']) ? null : $data['due_date'],
                $id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO goals (title, description, assignee_id, manager_id, status, due_date) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['title'],
                $data['description'],
                $data['assignee_id'],
                $data['manager_id'], // Set only on creation
                $data['status'],
                empty($data['due_date']) ? null : $data['due_date']
            ]);
        }
    }

    /**
     * Delete a goal by its ID.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM goals WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Find all goals assigned to a specific user.
     * @param int $assigneeId
     * @return array
     */
    public function findByAssignee(int $assigneeId): array
    {
        $sql = "
            SELECT 
                g.*,
                assignee.full_name as assignee_name,
                manager.full_name as manager_name
            FROM goals g
            LEFT JOIN users assignee ON g.assignee_id = assignee.id
            LEFT JOIN users manager ON g.manager_id = manager.id
            WHERE g.assignee_id = :assigneeId
            ORDER BY g.due_date DESC, g.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':assigneeId' => $assigneeId]);
        return $stmt->fetchAll();
    }
}
