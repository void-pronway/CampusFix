<?php

declare(strict_types=1);

/**
 * CampusFix
 * Member 3: Dashboard Backend Data
 *
 * Provides statistics and recent-record data for:
 * - Admin dashboard
 * - Student dashboard
 * - Staff dashboard
 *
 * Member 4 is responsible for displaying these values in the UI.
 */
class DashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get the main statistics required by the admin dashboard.
     */
    public function getAdminDashboardStats(): array
    {
        $issueSql = "
            SELECT
                COUNT(*) AS total_issues,
                SUM(status = 'Pending') AS pending_issues,
                SUM(status = 'Assigned') AS assigned_issues,
                SUM(status = 'In Progress') AS in_progress_issues,
                SUM(status = 'Resolved') AS resolved_issues,
                SUM(status = 'Rejected') AS rejected_issues,
                SUM(
                    (status = 'Pending'
                        AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY))
                    OR
                    (status = 'Assigned'
                        AND updated_at < DATE_SUB(NOW(), INTERVAL 2 DAY))
                    OR
                    (status = 'In Progress'
                        AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
                ) AS escalated_issues
            FROM issues
        ";

        $issueStats = $this->pdo
            ->query($issueSql)
            ->fetch(PDO::FETCH_ASSOC);

        $userSql = "
            SELECT
                SUM(role = 'student') AS total_students,
                SUM(role = 'staff') AS total_staff
            FROM users
        ";

        $userStats = $this->pdo
            ->query($userSql)
            ->fetch(PDO::FETCH_ASSOC);

        return [
            'total_issues' => (int) ($issueStats['total_issues'] ?? 0),
            'pending_issues' => (int) ($issueStats['pending_issues'] ?? 0),
            'assigned_issues' => (int) ($issueStats['assigned_issues'] ?? 0),
            'in_progress_issues' => (int) ($issueStats['in_progress_issues'] ?? 0),
            'resolved_issues' => (int) ($issueStats['resolved_issues'] ?? 0),
            'rejected_issues' => (int) ($issueStats['rejected_issues'] ?? 0),
            'escalated_issues' => (int) ($issueStats['escalated_issues'] ?? 0),

            'pending_lostfound' => $this->getPendingLostFoundCount(),
            'pending_claims' => $this->getPendingClaimCount(),

            'total_students' => (int) ($userStats['total_students'] ?? 0),
            'total_staff' => (int) ($userStats['total_staff'] ?? 0),
        ];
    }

    /**
     * Get dashboard statistics for one student.
     */
    public function getStudentDashboardStats(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $issueSql = "
            SELECT
                COUNT(*) AS total_issues,
                SUM(status = 'Pending') AS pending_issues,
                SUM(status = 'Assigned') AS assigned_issues,
                SUM(status = 'In Progress') AS in_progress_issues,
                SUM(status = 'Resolved') AS resolved_issues,
                SUM(status = 'Rejected') AS rejected_issues
            FROM issues
            WHERE reported_by = :user_id
        ";

        $stmt = $this->pdo->prepare($issueSql);
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        $issueStats = $stmt->fetch(PDO::FETCH_ASSOC);

        $lostFoundSql = "
            SELECT COUNT(*)
            FROM lost_found_items
            WHERE posted_by = :user_id
        ";

        $stmt = $this->pdo->prepare($lostFoundSql);
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        $lostFoundPosts = (int) $stmt->fetchColumn();

        $claimsSql = "
            SELECT COUNT(*)
            FROM claims
            WHERE submitted_by = :user_id
        ";

        $stmt = $this->pdo->prepare($claimsSql);
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        $claimRequests = (int) $stmt->fetchColumn();

        return [
            'total_issues' => (int) ($issueStats['total_issues'] ?? 0),
            'pending_issues' => (int) ($issueStats['pending_issues'] ?? 0),
            'assigned_issues' => (int) ($issueStats['assigned_issues'] ?? 0),
            'in_progress_issues' => (int) ($issueStats['in_progress_issues'] ?? 0),
            'resolved_issues' => (int) ($issueStats['resolved_issues'] ?? 0),
            'rejected_issues' => (int) ($issueStats['rejected_issues'] ?? 0),
            'lostfound_posts' => $lostFoundPosts,
            'claim_requests' => $claimRequests,
        ];
    }

    /**
     * Get dashboard statistics for one staff member.
     */
    public function getStaffDashboardStats(int $staffId): array
    {
        if ($staffId <= 0) {
            throw new InvalidArgumentException('Invalid staff ID.');
        }

        $sql = "
            SELECT
                COUNT(DISTINCT i.issue_id) AS total_assigned,
                COUNT(
                    DISTINCT CASE
                        WHEN i.status = 'Assigned'
                        THEN i.issue_id
                    END
                ) AS assigned_issues,
                COUNT(
                    DISTINCT CASE
                        WHEN i.status = 'In Progress'
                        THEN i.issue_id
                    END
                ) AS in_progress_issues,
                COUNT(
                    DISTINCT CASE
                        WHEN i.status = 'Resolved'
                        THEN i.issue_id
                    END
                ) AS resolved_issues
            FROM issue_assignments AS ia
            INNER JOIN issues AS i
                ON i.issue_id = ia.issue_id
            WHERE ia.staff_id = :staff_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':staff_id' => $staffId,
        ]);

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_assigned' => (int) ($stats['total_assigned'] ?? 0),
            'assigned_issues' => (int) ($stats['assigned_issues'] ?? 0),
            'in_progress_issues' => (int) ($stats['in_progress_issues'] ?? 0),
            'resolved_issues' => (int) ($stats['resolved_issues'] ?? 0),
        ];
    }

    /**
     * Return issue totals grouped by status.
     *
     * Useful for dashboard cards and Chart.js.
     */
    public function getIssueStatusCounts(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) AS total
            FROM issues
            GROUP BY status
            ORDER BY total DESC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        $counts = [
            'Pending' => 0,
            'Assigned' => 0,
            'In Progress' => 0,
            'Resolved' => 0,
            'Rejected' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) $row['status'];

            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Return issue totals grouped by issue category.
     */
    public function getIssueCategoryCounts(): array
    {
        $sql = "
            SELECT
                ic.category_id,
                ic.category_name,
                COUNT(i.issue_id) AS total_issues
            FROM issue_categories AS ic
            LEFT JOIN issues AS i
                ON i.category_id = ic.category_id
            GROUP BY
                ic.category_id,
                ic.category_name
            ORDER BY total_issues DESC, ic.category_name ASC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['category_id'] = (int) $row['category_id'];
            $row['total_issues'] = (int) $row['total_issues'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Get newest issues for dashboard recent-activity sections.
     */
    public function getRecentIssues(int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                issue_id,
                title,
                priority,
                status,
                created_at
            FROM issues
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get newest issues submitted by one student.
     */
    public function getRecentStudentIssues(
        int $userId,
        int $limit = 5
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                issue_id,
                title,
                priority,
                status,
                created_at
            FROM issues
            WHERE reported_by = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(
            ':user_id',
            $userId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get newest issues assigned to one staff member.
     */
    public function getRecentStaffIssues(
        int $staffId,
        int $limit = 5
    ): array {
        if ($staffId <= 0) {
            throw new InvalidArgumentException('Invalid staff ID.');
        }

        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT DISTINCT
                i.issue_id,
                i.title,
                i.priority,
                i.status,
                i.created_at
            FROM issue_assignments AS ia
            INNER JOIN issues AS i
                ON i.issue_id = ia.issue_id
            WHERE ia.staff_id = :staff_id
            ORDER BY i.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(
            ':staff_id',
            $staffId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count Lost & Found posts waiting for admin approval.
     */
    public function getPendingLostFoundCount(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM lost_found_items
            WHERE status = 'Pending'
        ";

        return (int) $this->pdo
            ->query($sql)
            ->fetchColumn();
    }

    /**
     * Count claims waiting for admin review.
     */
    public function getPendingClaimCount(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM claims
            WHERE status = 'Pending'
        ";

        return (int) $this->pdo
            ->query($sql)
            ->fetchColumn();
    }

    /**
     * Get recent approved Lost & Found posts.
     */
    public function getRecentLostFoundItems(int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                item_id,
                item_type,
                item_name,
                status,
                item_date,
                created_at
            FROM lost_found_items
            WHERE status = 'Approved'
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent pending claims for the admin dashboard.
     */
    public function getRecentPendingClaims(int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                c.claim_id,
                c.item_id,
                lfi.item_name,
                c.submitted_by,
                c.claim_msg,
                c.status,
                c.created_at
            FROM claims AS c
            INNER JOIN lost_found_items AS lfi
                ON lfi.item_id = c.item_id
            WHERE c.status = 'Pending'
            ORDER BY c.created_at ASC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}