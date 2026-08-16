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
}
