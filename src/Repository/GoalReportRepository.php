<?php

require_once __DIR__ . '/../Database.php';

class GoalReportRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find all reports for a specific goal.
     * @param int $goalId
     * @return array
     */
    public function findAllByGoal(int $goalId): array
    {
        $sql = "
            SELECT 
                gr.*,
                u.full_name as reported_by_name
            FROM goal_reports gr
            LEFT JOIN users u ON gr.reported_by_id = u.id
            WHERE gr.goal_id = :goalId
            ORDER BY gr.report_date DESC, gr.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':goalId' => $goalId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single goal report by its ID.
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM goal_reports WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Save a goal report (create or update).
     * @param array $data Report data
     * @return bool
     */
    public function save(array $data): bool
    {
        $id = $data['id'] ?? null;

        if ($id) {
            // Update
            $sql = "UPDATE goal_reports SET report_date = ?, value = ?, comment = ?, plan_next_week = ?, risk_level = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['report_date'],
                $data['value'] ?? null,
                $data['comment'],
                $data['plan_next_week'],
                $data['risk_level'] ?? 'low',
                $id
            ]);
        } else {
            // Create
            $sql = "INSERT INTO goal_reports (goal_id, report_date, value, comment, plan_next_week, risk_level, reported_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['goal_id'],
                $data['report_date'],
                $data['value'] ?? null,
                $data['comment'],
                $data['plan_next_week'],
                $data['risk_level'] ?? 'low',
                $data['reported_by_id']
            ]);
        }
    }

    /**
     * Delete a goal report by its ID.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM goal_reports WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
