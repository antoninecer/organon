<?php

require_once __DIR__ . '/../Database.php';

class ReviewRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find a single review by its ID.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Find all reviews for a specific user (where they were reviewed).
     * @param int $userId
     * @return array
     */
    public function findAllForUser(int $userId): array
    {
        $sql = "
            SELECT r.*, m.full_name as manager_name
            FROM reviews r
            JOIN users m ON r.manager_id = m.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Find all reviews conducted by a specific manager.
     * @param int $managerId
     * @return array
     */
    public function findAllByManager(int $managerId): array
    {
        $sql = "
            SELECT r.*, u.full_name as user_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.manager_id = ?
            ORDER BY r.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$managerId]);
        return $stmt->fetchAll();
    }

    /**
     * Save a review (create or update) and its associated items.
     * @param array $data
     * @return int|false The ID of the saved review or false on failure.
     */
    public function save(array $data)
    {
        $this->pdo->beginTransaction();
        
        try {
            $reviewId = $data['id'] ?? null;

            if ($reviewId) {
                // Update existing review
                $sql = "UPDATE reviews SET 
                            strengths_summary = ?, 
                            weaknesses_summary = ?, 
                            development_plan = ?, 
                            final_rating = ?,
                            status = ?,
                            finalized_at = ?
                        WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $data['strengths_summary'] ?? null,
                    $data['weaknesses_summary'] ?? null,
                    $data['development_plan'] ?? null,
                    $data['final_rating'] ?? null,
                    $data['status'] ?? 'draft',
                    ($data['status'] === 'finalized') ? date('Y-m-d H:i:s') : null,
                    $reviewId
                ]);
            } else {
                // Create new review draft
                $sql = "INSERT INTO reviews (user_id, manager_id, review_period, status) VALUES (?, ?, ?, 'draft')";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $data['user_id'],
                    $data['manager_id'],
                    $data['review_period']
                ]);
                $reviewId = $this->pdo->lastInsertId();
            }

            // Link items (goals, action_items, etc.)
            // First, clear existing links for this review
            $this->pdo->prepare("DELETE FROM review_items WHERE review_id = ?")->execute([$reviewId]);

            // Then, insert the new links
            $itemStmt = $this->pdo->prepare("INSERT INTO review_items (review_id, item_id, item_type) VALUES (?, ?, ?)");
            
            // Link Goals
            if (!empty($data['goals'])) {
                foreach ($data['goals'] as $goalId) {
                    $itemStmt->execute([$reviewId, (int)$goalId, 'goal']);
                }
            }
            // Link Action Items
            if (!empty($data['action_items'])) {
                foreach ($data['action_items'] as $itemId) {
                    $itemStmt->execute([$reviewId, (int)$itemId, 'action_item']);
                }
            }
            // Link Recognitions
            if (!empty($data['recognitions'])) {
                foreach ($data['recognitions'] as $recId) {
                    $itemStmt->execute([$reviewId, (int)$recId, 'recognition']);
                }
            }

            $this->pdo->commit();
            return $reviewId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in ReviewRepository::save: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find all items associated with a specific review.
     * @param int $reviewId
     * @return array
     */
    public function findReviewItems(int $reviewId): array
    {
        $stmt = $this->pdo->prepare("SELECT item_id, item_type FROM review_items WHERE review_id = ?");
        $stmt->execute([$reviewId]);
        
        $items = [
            'goal' => [],
            'action_item' => [],
            'recognition' => []
        ];
        
        foreach($stmt->fetchAll() as $row) {
            if (isset($items[$row['item_type']])) {
                $items[$row['item_type']][] = $row['item_id'];
            }
        }
        return $items;
    }

    /**
     * Find a review by user and period to prevent duplicates.
     * @param int $userId
     * @param string $reviewPeriod
     * @return mixed
     */
    public function findByUserAndPeriod(int $userId, string $reviewPeriod)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND review_period = ?");
        $stmt->execute([$userId, $reviewPeriod]);
        return $stmt->fetch();
    }
}
