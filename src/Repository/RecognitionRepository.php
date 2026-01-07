<?php

require_once __DIR__ . '/../Database.php';

class RecognitionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all recognitions, joining user names for giver and receiver.
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT 
                r.*,
                giver.full_name as giver_name,
                receiver.full_name as receiver_name
            FROM recognitions r
            LEFT JOIN users giver ON r.giver_id = giver.id
            LEFT JOIN users receiver ON r.receiver_id = receiver.id
            ORDER BY r.created_at DESC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Find a single recognition by its ID.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM recognitions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Save a recognition (create or update).
     * @param array $data Recognition data
     * @return bool
     */
    public function save(array $data): bool
    {
        $id = $data['id'] ?? null;

        if ($id) {
            // Update (Only message can be updated, giver/receiver are immutable)
            $sql = "UPDATE recognitions SET message = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['message'],
                $id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO recognitions (giver_id, receiver_id, message) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['giver_id'],
                $data['receiver_id'],
                $data['message']
            ]);
        }
    }

    /**
     * Delete a recognition by its ID.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM recognitions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Find all recognitions received by a specific user.
     * @param int $receiverId
     * @return array
     */
    public function findByReceiver(int $receiverId): array
    {
        $sql = "
            SELECT 
                r.*,
                giver.full_name as giver_name,
                receiver.full_name as receiver_name
            FROM recognitions r
            LEFT JOIN users giver ON r.giver_id = giver.id
            LEFT JOIN users receiver ON r.receiver_id = receiver.id
            WHERE r.receiver_id = :receiverId
            ORDER BY r.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':receiverId' => $receiverId]);
        return $stmt->fetchAll();
    }
}
