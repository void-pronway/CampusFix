<?php

declare(strict_types=1);

/**
 * Database access layer for Lost & Found claims.
 *
 * Handles:
 * - submitting claims
 * - viewing claims
 * - admin claim review
 * - approving/rejecting claims
 * - returning an item after an approved claim
 */
class ClaimRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Submit a new claim.
     */
    public function create(
        int $itemId,
        int $submittedBy,
        string $claimMessage
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO claims (
                item_id,
                submitted_by,
                claim_msg,
                status
            )
            VALUES (
                :item_id,
                :submitted_by,
                :claim_msg,
                'Pending'
            )
        ");

        $stmt->execute([
            ':item_id'      => $itemId,
            ':submitted_by' => $submittedBy,
            ':claim_msg'    => $claimMessage,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Find one claim by ID.
     */
    public function findById(int $claimId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                lfi.item_name,
                lfi.item_type,
                lfi.status AS item_status,
                lfi.posted_by,
                lfi.image_path
            FROM claims c
            INNER JOIN lost_found_items lfi
                ON lfi.item_id = c.item_id
            WHERE c.claim_id = :claim_id
            LIMIT 1
        ");

        $stmt->execute([
            ':claim_id' => $claimId,
        ]);

        $claim = $stmt->fetch(PDO::FETCH_ASSOC);

        return $claim ?: null;
    }

    /**
     * Return claims submitted by one user.
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                lfi.item_name,
                lfi.item_type,
                lfi.status AS item_status,
                lfi.image_path
            FROM claims c
            INNER JOIN lost_found_items lfi
                ON lfi.item_id = c.item_id
            WHERE c.submitted_by = :user_id
            ORDER BY c.created_at DESC
        ");

        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all claims submitted for one item.
     */
    public function getByItem(int $itemId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM claims
            WHERE item_id = :item_id
            ORDER BY created_at DESC
        ");

        $stmt->execute([
            ':item_id' => $itemId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return pending claims for admin review.
     */
    public function getPending(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                c.*,
                lfi.item_name,
                lfi.item_type,
                lfi.status AS item_status,
                lfi.image_path
            FROM claims c
            INNER JOIN lost_found_items lfi
                ON lfi.item_id = c.item_id
            WHERE c.status = 'Pending'
            ORDER BY c.created_at ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether the user already has a pending claim
     * for the same Lost & Found item.
     */
    public function hasPendingClaim(
        int $itemId,
        int $userId
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM claims
            WHERE item_id = :item_id
              AND submitted_by = :user_id
              AND status = 'Pending'
        ");

        $stmt->execute([
            ':item_id' => $itemId,
            ':user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Reject one pending claim.
     */
    public function reject(
        int $claimId,
        int $reviewedBy,
        ?string $adminNote = null
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE claims
            SET
                status = 'Rejected',
                reviewed_by = :reviewed_by,
                admin_note = :admin_note
            WHERE claim_id = :claim_id
              AND status = 'Pending'
        ");

        $stmt->execute([
            ':reviewed_by' => $reviewedBy,
            ':admin_note'  => $adminNote,
            ':claim_id'    => $claimId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Approve a claim using a database transaction.
     *
     * The transaction:
     * 1. Locks the selected claim and item.
     * 2. Approves the selected claim.
     * 3. Rejects other pending claims for that item.
     * 4. Marks the Lost & Found item as Returned.
     *
     * Either every step succeeds or every step is rolled back.
     */
    public function approve(
        int $claimId,
        int $reviewedBy,
        ?string $adminNote = null
    ): bool {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                SELECT
                    c.claim_id,
                    c.item_id,
                    c.status AS claim_status,
                    lfi.status AS item_status
                FROM claims c
                INNER JOIN lost_found_items lfi
                    ON lfi.item_id = c.item_id
                WHERE c.claim_id = :claim_id
                FOR UPDATE
            ");

            $stmt->execute([
                ':claim_id' => $claimId,
            ]);

            $claim = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$claim) {
                throw new RuntimeException('Claim not found.');
            }

            if ($claim['claim_status'] !== 'Pending') {
                throw new RuntimeException(
                    'Only pending claims can be approved.'
                );
            }

            if ($claim['item_status'] !== 'Approved') {
                throw new RuntimeException(
                    'The item must be approved before a claim can be approved.'
                );
            }

            $approveStmt = $this->pdo->prepare("
                UPDATE claims
                SET
                    status = 'Approved',
                    reviewed_by = :reviewed_by,
                    admin_note = :admin_note
                WHERE claim_id = :claim_id
            ");

            $approveStmt->execute([
                ':reviewed_by' => $reviewedBy,
                ':admin_note'  => $adminNote,
                ':claim_id'    => $claimId,
            ]);

            $rejectOthersStmt = $this->pdo->prepare("
                UPDATE claims
                SET
                    status = 'Rejected',
                    reviewed_by = :reviewed_by
                WHERE item_id = :item_id
                  AND claim_id <> :claim_id
                  AND status = 'Pending'
            ");

            $rejectOthersStmt->execute([
                ':reviewed_by' => $reviewedBy,
                ':item_id'     => $claim['item_id'],
                ':claim_id'    => $claimId,
            ]);

            $itemStmt = $this->pdo->prepare("
                UPDATE lost_found_items
                SET status = 'Returned'
                WHERE item_id = :item_id
            ");

            $itemStmt->execute([
                ':item_id' => $claim['item_id'],
            ]);

            $this->pdo->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Count claims by status.
     */
    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM claims
            WHERE status = :status
        ");

        $stmt->execute([
            ':status' => $status,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count all claims.
     */
    public function countAll(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM claims")
            ->fetchColumn();
    }
}