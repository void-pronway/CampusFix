<?php

declare(strict_types=1);

class IssueService
{
    public const PRIORITY_LOW = 'Low';
    public const PRIORITY_MEDIUM = 'Medium';
    public const PRIORITY_HIGH = 'High';
    public const PRIORITY_EMERGENCY = 'Emergency';

    public const VISIBILITY_PUBLIC = 'Public';
    public const VISIBILITY_PRIVATE = 'Private';

    private const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_EMERGENCY,
    ];

    private const VISIBILITIES = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_PRIVATE,
    ];

    public static function isValidPriority(string $priority): bool
    {
        return in_array($priority, self::PRIORITIES, true);
    }

    public static function isValidVisibility(string $visibility): bool
    {
        return in_array($visibility, self::VISIBILITIES, true);
    }

    public static function validateTitle(string $title): bool
    {
        $title = trim($title);

        return $title !== ''
            && strlen($title) <= 200;
    }

    public static function validateDescription(string $description): bool
    {
        return trim($description) !== '';
    }
}
