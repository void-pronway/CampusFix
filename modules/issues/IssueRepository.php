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

}


