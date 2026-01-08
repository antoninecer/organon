<?php

require_once __DIR__ . '/../Database.php';

class OneOnOneNoteRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all 1:1 notes for a specific subordinate.
     * @param int $subordinateId
     * @return array
     */
    public function findAllForUser(int $subordinateId): array
    {
        $sql = "
            SELECT
                onon.*,
                m.full_name AS manager_name
            FROM one_on_one_notes onon
            JOIN users m ON onon.manager_id = m.id
            WHERE onon.subordinate_id = ?
            ORDER BY onon.note_date DESC, onon.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$subordinateId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single 1:1 note by ID.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM one_on_one_notes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new 1:1 note.
     * @param int $managerId
     * @param int $subordinateId
     * @param string $note
     * @param string $noteDate
     * @return bool
     */
    public function create(int $managerId, int $subordinateId, string $note, string $noteDate): bool
    {
        $sql = "INSERT INTO one_on_one_notes (manager_id, subordinate_id, note, note_date) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$managerId, $subordinateId, $note, $noteDate]);
    }

    /**
     * Update an existing 1:1 note.
     * @param int $id
     * @param string $note
     * @param string $noteDate
     * @return bool
     */
    public function update(int $id, string $note, string $noteDate): bool
    {
        $sql = "UPDATE one_on_one_notes SET note = ?, note_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$note, $noteDate, $id]);
    }

    /**
     * Delete a 1:1 note by ID.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM one_on_one_notes WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
