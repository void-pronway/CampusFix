<?php

declare(strict_types=1);

class StatusService
{
    public const PENDING = 'Pending';
    public const ASSIGNED = 'Assigned';
    public const IN_PROGRESS = 'In Progress';
    public const RESOLVED = 'Resolved';
    public const REJECTED = 'Rejected';

    private const TRANSITIONS = [
        self::PENDING => [
            self::ASSIGNED,
            self::REJECTED,
        ],
        self::ASSIGNED => [
            self::IN_PROGRESS,
        ],
        self::IN_PROGRESS => [
            self::RESOLVED,
        ],
        self::RESOLVED => [],
        self::REJECTED => [],
    ];

    public static function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::TRANSITIONS);
    }

    public static function canTransition(
        string $currentStatus,
        string $newStatus
    ): bool {
        if (!self::isValidStatus($currentStatus)) {
            return false;
        }

        return in_array(
            $newStatus,
            self::TRANSITIONS[$currentStatus],
            true
        );
    }

    public static function getAllowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public static function isTerminal(string $status): bool
    {
        return in_array(
            $status,
            [self::RESOLVED, self::REJECTED],
            true
        );
    }
    public static function isOverdue(
    string $status,
    DateTimeInterface $lastUpdated,
    ?DateTimeInterface $now = null
): bool {
    $now ??= new DateTimeImmutable();

    $daysElapsed = (int) $lastUpdated->diff($now)->format('%a');

    return match ($status) {
        self::PENDING => $daysElapsed > 3,
        self::ASSIGNED => $daysElapsed > 2,
        self::IN_PROGRESS => $daysElapsed > 7,
        default => false,
    };
}

public static function getEscalationReason(
    string $status,
    DateTimeInterface $lastUpdated,
    ?DateTimeInterface $now = null
): ?string {
    if (!self::isOverdue($status, $lastUpdated, $now)) {
        return null;
    }

    return match ($status) {
        self::PENDING => 'Issue has remained pending for more than 3 days.',
        self::ASSIGNED => 'Assigned issue has not started for more than 2 days.',
        self::IN_PROGRESS => 'Issue has remained in progress for more than 7 days.',
        default => null,
    };
}
}
