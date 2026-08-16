<?php

declare(strict_types=1);

/**
 * CampusFix
 * Member 3: Analytics Backend Service
 *
 * Supplies structured analytics data for:
 * - Admin analytics
 * - Dashboard charts
 * - Chart.js datasets
 *
 * Member 4 handles presentation/UI.
 */
class AnalyticsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Issue count grouped by status.
     */
    public function getIssueStatusBreakdown(): array
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

        return $this->formatLabelValueRows(
            $rows,
            'status',
            'total'
        );
    }

    /**
     * Issue count grouped by priority.
     */
    public function getIssuePriorityBreakdown(): array
    {
        $sql = "
            SELECT
                priority,
                COUNT(*) AS total
            FROM issues
            GROUP BY priority
            ORDER BY total DESC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'priority',
            'total'
        );
    }

    /**
     * Issue count grouped by category.
     */
    public function getIssueCategoryBreakdown(): array
    {
        $sql = "
            SELECT
                ic.category_name AS category,
                COUNT(i.issue_id) AS total
            FROM issue_categories AS ic
            LEFT JOIN issues AS i
                ON i.category_id = ic.category_id
            GROUP BY
                ic.category_id,
                ic.category_name
            ORDER BY total DESC, ic.category_name ASC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'category',
            'total'
        );
    }

    /**
     * Monthly issue creation trend.
     */
    public function getIssueMonthlyTrend(): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS month,
                COUNT(*) AS total
            FROM issues
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'month',
            'total'
        );
    }

    /**
     * Daily issue creation trend.
     */
    public function getIssueDailyTrend(): array
    {
        $sql = "
            SELECT
                DATE(created_at) AS activity_date,
                COUNT(*) AS total
            FROM issues
            GROUP BY DATE(created_at)
            ORDER BY activity_date ASC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'activity_date',
            'total'
        );
    }

    /**
     * Locations receiving the highest number of issue reports.
     */
    public function getTopIssueLocations(int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                location_id,
                COUNT(*) AS total_issues
            FROM issues
            GROUP BY location_id
            ORDER BY total_issues DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['location_id'] = (int) $row['location_id'];
            $row['total_issues'] = (int) $row['total_issues'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Users who have reported the most issues.
     */
    public function getTopIssueReporters(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                reported_by AS user_id,
                COUNT(*) AS total_reports
            FROM issues
            GROUP BY reported_by
            ORDER BY total_reports DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['user_id'] = (int) $row['user_id'];
            $row['total_reports'] = (int) $row['total_reports'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Lost & Found item count grouped by status.
     */
    public function getLostFoundStatusBreakdown(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) AS total
            FROM lost_found_items
            GROUP BY status
            ORDER BY total DESC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'status',
            'total'
        );
    }

    /**
     * Lost versus Found item count.
     */
    public function getLostFoundTypeBreakdown(): array
    {
        $sql = "
            SELECT
                item_type,
                COUNT(*) AS total
            FROM lost_found_items
            GROUP BY item_type
            ORDER BY total DESC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'item_type',
            'total'
        );
    }

    /**
     * Lost & Found count grouped by category.
     */
    public function getLostFoundCategoryBreakdown(): array
    {
        $sql = "
            SELECT
                lfc.item_category_id,
                COUNT(lfi.item_id) AS total
            FROM lost_found_categories AS lfc
            LEFT JOIN lost_found_items AS lfi
                ON lfi.item_category_id = lfc.item_category_id
            GROUP BY lfc.item_category_id
            ORDER BY total DESC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'category_id' => (int) $row['item_category_id'],
                'total' => (int) $row['total'],
            ];
        }

        return $result;
    }

    /**
     * Monthly Lost & Found activity.
     */
    public function getLostFoundMonthlyTrend(): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS month,
                COUNT(*) AS total
            FROM lost_found_items
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'month',
            'total'
        );
    }

    /**
     * Users who have posted the most Lost & Found items.
     */
    public function getTopLostFoundPosters(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                posted_by AS user_id,
                COUNT(*) AS total_posts
            FROM lost_found_items
            GROUP BY posted_by
            ORDER BY total_posts DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['user_id'] = (int) $row['user_id'];
            $row['total_posts'] = (int) $row['total_posts'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Claim count grouped by status.
     */
    public function getClaimStatusBreakdown(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) AS total
            FROM claims
            GROUP BY status
            ORDER BY total DESC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->formatLabelValueRows(
            $rows,
            'status',
            'total'
        );
    }

    /**
     * Lost & Found items receiving the most claims.
     */
    public function getMostClaimedItems(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        $sql = "
            SELECT
                lfi.item_id,
                lfi.item_name,
                COUNT(c.claim_id) AS total_claims
            FROM lost_found_items AS lfi
            LEFT JOIN claims AS c
                ON c.item_id = lfi.item_id
            GROUP BY
                lfi.item_id,
                lfi.item_name
            ORDER BY total_claims DESC, lfi.item_name ASC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['item_id'] = (int) $row['item_id'];
            $row['total_claims'] = (int) $row['total_claims'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Daily activity across Issues, Lost & Found and Claims.
     */
    public function getDailyActivity(): array
    {
        $sql = "
            SELECT
                activity_date,
                SUM(issue_count) AS issue_count,
                SUM(lostfound_count) AS lostfound_count,
                SUM(claim_count) AS claim_count
            FROM (
                SELECT
                    DATE(created_at) AS activity_date,
                    COUNT(*) AS issue_count,
                    0 AS lostfound_count,
                    0 AS claim_count
                FROM issues
                GROUP BY DATE(created_at)

                UNION ALL

                SELECT
                    DATE(created_at) AS activity_date,
                    0 AS issue_count,
                    COUNT(*) AS lostfound_count,
                    0 AS claim_count
                FROM lost_found_items
                GROUP BY DATE(created_at)

                UNION ALL

                SELECT
                    DATE(created_at) AS activity_date,
                    0 AS issue_count,
                    0 AS lostfound_count,
                    COUNT(*) AS claim_count
                FROM claims
                GROUP BY DATE(created_at)
            ) AS activity
            GROUP BY activity_date
            ORDER BY activity_date ASC
        ";

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['issue_count'] = (int) $row['issue_count'];
            $row['lostfound_count'] = (int) $row['lostfound_count'];
            $row['claim_count'] = (int) $row['claim_count'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Overall totals for analytics summary cards.
     */
    public function getOverallTotals(): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM issues)
                    AS total_issues,

                (SELECT COUNT(*) FROM lost_found_items)
                    AS total_lostfound_items,

                (SELECT COUNT(*) FROM claims)
                    AS total_claims,

                (
                    SELECT COUNT(*)
                    FROM lost_found_items
                    WHERE status = 'Pending'
                ) AS pending_lostfound_items,

                (
                    SELECT COUNT(*)
                    FROM claims
                    WHERE status = 'Pending'
                ) AS pending_claims
        ";

        $row = $this->pdo
            ->query($sql)
            ->fetch(PDO::FETCH_ASSOC);

        return [
            'total_issues' =>
                (int) ($row['total_issues'] ?? 0),

            'total_lostfound_items' =>
                (int) ($row['total_lostfound_items'] ?? 0),

            'total_claims' =>
                (int) ($row['total_claims'] ?? 0),

            'pending_lostfound_items' =>
                (int) ($row['pending_lostfound_items'] ?? 0),

            'pending_claims' =>
                (int) ($row['pending_claims'] ?? 0),
        ];
    }

    /**
     * Complete analytics payload.
     *
     * admin/analytics_data.php can return this as JSON.
     */
    public function getAnalyticsPackage(): array
    {
        return [
            'summary' =>
                $this->getOverallTotals(),

            'issues' => [
                'by_status' =>
                    $this->getIssueStatusBreakdown(),

                'by_priority' =>
                    $this->getIssuePriorityBreakdown(),

                'by_category' =>
                    $this->getIssueCategoryBreakdown(),

                'monthly_trend' =>
                    $this->getIssueMonthlyTrend(),

                'top_locations' =>
                    $this->getTopIssueLocations(),
            ],

            'lost_found' => [
                'by_status' =>
                    $this->getLostFoundStatusBreakdown(),

                'by_type' =>
                    $this->getLostFoundTypeBreakdown(),

                'by_category' =>
                    $this->getLostFoundCategoryBreakdown(),

                'monthly_trend' =>
                    $this->getLostFoundMonthlyTrend(),

                'most_claimed_items' =>
                    $this->getMostClaimedItems(),
            ],

            'claims' => [
                'by_status' =>
                    $this->getClaimStatusBreakdown(),
            ],

            'daily_activity' =>
                $this->getDailyActivity(),
        ];
    }

    /**
     * Convert database rows into a Chart.js-friendly structure.
     */
    private function formatLabelValueRows(
        array $rows,
        string $labelColumn,
        string $valueColumn
    ): array {
        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = (string) $row[$labelColumn];
            $values[] = (int) $row[$valueColumn];
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}