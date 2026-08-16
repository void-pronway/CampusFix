<?php

declare(strict_types=1);

class IssueRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO issues (
                category_id,
                reported_by,
                location_id,
                title,
                description,
                visibility,
                priority,
                image_path
            )
            VALUES (
                :category_id,
                :reported_by,
                :location_id,
                :title,
                :description,
                :visibility,
                :priority,
                :image_path
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':category_id' => $data['category_id'],
            ':reported_by' => $data['reported_by'],
            ':location_id' => $data['location_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':visibility' => $data['visibility'],
            ':priority' => $data['priority'],
            ':image_path' => $data['image_path'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $issueId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM issues WHERE id = :id LIMIT 1"
        );

        $stmt->execute([
            ':id' => $issueId,
        ]);

        $issue = $stmt->fetch(PDO::FETCH_ASSOC);

        return $issue !== false ? $issue : null;
    }

    public function findByReporter(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM issues
             WHERE reported_by = :reported_by
             ORDER BY created_at DESC"
        );

        $stmt->execute([
            ':reported_by' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(
    int $issueId,
    string $status,
    ?string $rejectionNote = null
): bool {
    $sql = "
        UPDATE issues
        SET
            status = :status,
            rejection_note = :rejection_note
        WHERE id = :id
    ";

    $stmt = $this->pdo->prepare($sql);

    return $stmt->execute([
        ':status' => $status,
        ':rejection_note' => $rejectionNote,
        ':id' => $issueId,
    ]);
}

public function findOpenDuplicateCandidates(
    int $categoryId,
    int $locationId
): array {
    $sql = "
        SELECT *
        FROM issues
        WHERE category_id = :category_id
          AND location_id = :location_id
          AND status NOT IN ('Resolved', 'Rejected')
        ORDER BY created_at DESC
    ";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([
        ':category_id' => $categoryId,
        ':location_id' => $locationId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function addConfirmation(int $issueId, int $userId): bool
{
    $stmt = $this->pdo->prepare(
        "INSERT INTO issue_confirmations (issue_id, user_id)
         VALUES (:issue_id, :user_id)"
    );

    return $stmt->execute([
        ':issue_id' => $issueId,
        ':user_id' => $userId,
    ]);
}

public function hasUserConfirmed(int $issueId, int $userId): bool
{
    $stmt = $this->pdo->prepare(
        "SELECT 1
         FROM issue_confirmations
         WHERE issue_id = :issue_id
           AND user_id = :user_id
         LIMIT 1"
    );

    $stmt->execute([
        ':issue_id' => $issueId,
        ':user_id' => $userId,
    ]);

    return $stmt->fetchColumn() !== false;
}

public function countConfirmations(int $issueId): int
{
    $stmt = $this->pdo->prepare(
        "SELECT COUNT(*)
         FROM issue_confirmations
         WHERE issue_id = :issue_id"
    );

    $stmt->execute([
        ':issue_id' => $issueId,
    ]);

    return (int) $stmt->fetchColumn();
}

public function addStatusLog(
    int $issueId,
    int $changedBy,
    ?string $oldStatus,
    string $newStatus,
    ?string $note = null
): bool {
    $stmt = $this->pdo->prepare(
        "INSERT INTO issue_status_logs (
            issue_id,
            changed_by,
            old_status,
            new_status,
            note
        )
        VALUES (
            :issue_id,
            :changed_by,
            :old_status,
            :new_status,
            :note
        )"
    );

    return $stmt->execute([
        ':issue_id' => $issueId,
        ':changed_by' => $changedBy,
        ':old_status' => $oldStatus,
        ':new_status' => $newStatus,
        ':note' => $note,
    ]);
}

public function getStatusHistory(int $issueId): array
{
    $stmt = $this->pdo->prepare(
        "SELECT *
         FROM issue_status_logs
         WHERE issue_id = :issue_id
         ORDER BY changed_at ASC, id ASC"
    );

    $stmt->execute([
        ':issue_id' => $issueId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function createAssignment(
    int $issueId,
    int $staffId,
    int $assignedBy,
    ?string $assignmentNote = null
): int {
    $stmt = $this->pdo->prepare(
        "INSERT INTO issue_assignments (
            issue_id,
            staff_id,
            assigned_by,
            assignment_note
        )
        VALUES (
            :issue_id,
            :staff_id,
            :assigned_by,
            :assignment_note
        )"
    );

    $stmt->execute([
        ':issue_id' => $issueId,
        ':staff_id' => $staffId,
        ':assigned_by' => $assignedBy,
        ':assignment_note' => $assignmentNote,
    ]);

    return (int) $this->pdo->lastInsertId();
}

public function findAssignmentsByStaff(int $staffId): array
{
    $stmt = $this->pdo->prepare(
        "SELECT
            ia.*,
            i.title,
            i.priority,
            i.status,
            i.location_id,
            i.category_id
         FROM issue_assignments ia
         INNER JOIN issues i
            ON i.id = ia.issue_id
         WHERE ia.staff_id = :staff_id
         ORDER BY ia.assigned_at DESC"
    );

    $stmt->execute([
        ':staff_id' => $staffId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function markAssignmentResolved(
    int $issueId,
    string $resolutionNote
): bool {
    $stmt = $this->pdo->prepare(
        "UPDATE issue_assignments
         SET
            resolution_note = :resolution_note,
            resolved_at = CURRENT_TIMESTAMP
         WHERE issue_id = :issue_id
           AND resolved_at IS NULL"
    );

    return $stmt->execute([
        ':resolution_note' => $resolutionNote,
        ':issue_id' => $issueId,
    ]);
}

public function addComment(
    int $issueId,
    int $userId,
    string $comment
): int {
    $stmt = $this->pdo->prepare(
        "INSERT INTO comments (
            issue_id,
            user_id,
            comment
        )
        VALUES (
            :issue_id,
            :user_id,
            :comment
        )"
    );

    $stmt->execute([
        ':issue_id' => $issueId,
        ':user_id' => $userId,
        ':comment' => $comment,
    ]);

    return (int) $this->pdo->lastInsertId();
}

public function getComments(int $issueId): array
{
    $stmt = $this->pdo->prepare(
        "SELECT *
         FROM comments
         WHERE issue_id = :issue_id
         ORDER BY created_at ASC, id ASC"
    );

    $stmt->execute([
        ':issue_id' => $issueId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}



