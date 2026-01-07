<?php

class DepartmentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all departments and join manager and parent info.
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT 
                d.id, 
                d.name, 
                d.parent_id,
                d.manager_id,
                u.full_name as manager_name,
                p.name as parent_name
            FROM departments d
            LEFT JOIN users u ON d.manager_id = u.id
            LEFT JOIN departments p ON d.parent_id = p.id
            ORDER BY d.name
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Find a single department by its ID.
     * @param int $id
     * @return array|false
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new department.
     * @param string $name
     * @param int|null $parentId
     * @param int|null $managerId
     * @return bool
     */
    public function create(string $name, ?int $parentId, ?int $managerId): bool
    {
        $sql = "INSERT INTO departments (name, parent_id, manager_id) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $parentId, $managerId]);
    }

    /**
     * Update an existing department.
     * @param int $id
     * @param string $name
     * @param int|null $parentId
     * @param int|null $managerId
     * @return bool
     */
    public function update(int $id, string $name, ?int $parentId, ?int $managerId): bool
    {
        // You can't be your own parent
        if ($id === $parentId) {
            return false;
        }
        
        $sql = "UPDATE departments SET name = ?, parent_id = ?, manager_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $parentId, $managerId, $id]);
    }

    /**
     * Delete a department.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // First, set any children's parent_id to null to avoid foreign key constraints
        $this->pdo->prepare("UPDATE departments SET parent_id = NULL WHERE parent_id = ?")
                  ->execute([$id]);

        $stmt = $this->pdo->prepare("DELETE FROM departments WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
