<?php

declare(strict_types=1);

/**
 * Database access layer for CampusFix Lost & Found items.
 *
 * Member 3 ownership:
 * - create lost/found items
 * - browse approved items
 * - view item details
 * - view a student's own items
 * - admin pending-item list
 * - approve/reject/return items
 * - filter/search lost & found records
 */
class LostFoundRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new Lost or Found item.
     *
     * New submissions always begin with Pending status.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO lost_found_items (
                item_category_id,
                posted_by,
                location_id,
                item_type,
                item_name,
                description,
                status,
                item_date,
                image_path
            )
            VALUES (
                :item_category_id,
                :posted_by,
                :location_id,
                :item_type,
                :item_name,
                :description,
                'Pending',
                :item_date,
                :image_path
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':item_category_id' => $data['item_category_id'],
            ':posted_by'        => $data['posted_by'],
            ':location_id'      => $data['location_id'],
            ':item_type'        => $data['item_type'],
            ':item_name'        => $data['item_name'],
            ':description'      => $data['description'] ?? null,
            ':item_date'        => $data['item_date'],
            ':image_path'       => $data['image_path'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Return publicly visible approved Lost & Found items.
     *
     * Supported filters:
     * - type
     * - category_id
     * - location_id
     * - date
     * - search
     */
    public function getApproved(array $filters = []): array
    {
        $sql = "
            SELECT *
            FROM lost_found_items
            WHERE status = 'Approved'
        ";

        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND item_type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND item_category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['location_id'])) {
            $sql .= " AND location_id = :location_id";
            $params[':location_id'] = $filters['location_id'];
        }

        if (!empty($filters['date'])) {
            $sql .= " AND item_date = :item_date";
            $params[':item_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $sql .= "
                AND (
                    item_name LIKE :search
                    OR description LIKE :search
                )
            ";

            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find one Lost & Found item by primary key.
     */
    public function findById(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM lost_found_items
            WHERE item_id = :item_id
            LIMIT 1
        ");

        $stmt->execute([
            ':item_id' => $itemId,
        ]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        return $item ?: null;
    }

    /**
     * Return all items submitted by one user.
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM lost_found_items
            WHERE posted_by = :posted_by
            ORDER BY created_at DESC
        ");

        $stmt->execute([
            ':posted_by' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return items waiting for admin approval.
     */
    public function getPending(): array
    {
        $stmt = $this->pdo->query("
            SELECT *
            FROM lost_found_items
            WHERE status = 'Pending'
            ORDER BY created_at ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Change item status.
     *
     * Allowed statuses:
     * Pending, Approved, Rejected, Returned
     */
    public function updateStatus(int $itemId, string $status): bool
    {
        $allowedStatuses = [
            'Pending',
            'Approved',
            'Rejected',
            'Returned',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid Lost & Found status.');
        }

        $stmt = $this->pdo->prepare("
            UPDATE lost_found_items
            SET status = :status
            WHERE item_id = :item_id
        ");

        return $stmt->execute([
            ':status'  => $status,
            ':item_id' => $itemId,
        ]);
    }

    /**
     * Approve an item so it becomes publicly visible.
     */
    public function approve(int $itemId): bool
    {
        return $this->updateStatus($itemId, 'Approved');
    }

    /**
     * Reject an invalid Lost & Found submission.
     */
    public function reject(int $itemId): bool
    {
        return $this->updateStatus($itemId, 'Rejected');
    }

    /**
     * Mark an item as successfully returned.
     */
    public function markReturned(int $itemId): bool
    {
        return $this->updateStatus($itemId, 'Returned');
    }

    /**
     * Count Lost & Found records by status.
     */
    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM lost_found_items
            WHERE status = :status
        ");

        $stmt->execute([
            ':status' => $status,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count all Lost & Found items.
     */
    public function countAll(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM lost_found_items")
            ->fetchColumn();
    }
}