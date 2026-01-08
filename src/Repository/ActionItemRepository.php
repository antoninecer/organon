<?php

require_once __DIR__ . '/../Database.php';

class ActionItemRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all action items, joining user names for the owner.
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT 
                ai.*,
                owner.full_name as owner_name
            FROM action_items ai
            LEFT JOIN users owner ON ai.owner_id = owner.id
            ORDER BY ai.due_date DESC, ai.created_at DESC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Find a single action item by its ID.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM action_items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Save an action item (create or update).
     * @param array $data Action item data
     * @return bool
     */
    public function save(array $data): bool
    {
        $id = $data['id'] ?? null;

        if ($id) {
            // Update - creator_id is not changed on update
            $sql = "UPDATE action_items SET title = ?, owner_id = ?, due_date = ?, status = ?, context = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['title'],
                $data['owner_id'],
                empty($data['due_date']) ? null : $data['due_date'],
                $data['status'],
                $data['context'],
                $id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO action_items (title, owner_id, creator_id, due_date, status, context) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['title'],
                $data['owner_id'],
                $data['creator_id'], // Set only on creation
                empty($data['due_date']) ? null : $data['due_date'],
                $data['status'],
                $data['context']
            ]);
        }
    }

    /**
     * Delete an action item by its ID.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM action_items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Find all action items owned by a specific user.
     * @param int $ownerId
     * @return array
     */
    public function findByOwner(int $ownerId): array
    {
        $sql = "
            SELECT 
                ai.*,
                owner.full_name as owner_name,
                creator.full_name as creator_name
            FROM action_items ai
            LEFT JOIN users owner ON ai.owner_id = owner.id
            LEFT JOIN users creator ON ai.creator_id = creator.id
            WHERE ai.owner_id = :ownerId
            ORDER BY ai.due_date DESC, ai.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ownerId' => $ownerId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Find all action items created by a specific user.
     * @param int $creatorId
     * @return array
     */
    public function findByCreator(int $creatorId): array
    {
        $sql = "
            SELECT
                ai.*,
                owner.full_name as owner_name,
                creator.full_name as creator_name
            FROM action_items ai
            LEFT JOIN users owner ON ai.owner_id = owner.id
            LEFT JOIN users creator ON ai.creator_id = creator.id
            WHERE ai.creator_id = :creatorId
            ORDER BY ai.due_date DESC, ai.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':creatorId' => $creatorId]);
        return $stmt->fetchAll();
    }

    /**
     * Update the status of a single action item.
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE action_items SET status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
}
