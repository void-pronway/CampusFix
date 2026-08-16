<?php

declare(strict_types=1);

class DuplicateService
{
    public static function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = strtolower($value);

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    public static function isPotentialDuplicate(
        string $existingTitle,
        string $newTitle,
        int $existingCategoryId,
        int $newCategoryId,
        string $existingLocation,
        string $newLocation
    ): bool {
        if ($existingCategoryId !== $newCategoryId) {
            return false;
        }

        if (
            self::normalizeText($existingLocation)
            !== self::normalizeText($newLocation)
        ) {
            return false;
        }

        $existing = self::normalizeText($existingTitle);
        $new = self::normalizeText($newTitle);

        if ($existing === '' || $new === '') {
            return false;
        }

        return str_contains($existing, $new)
            || str_contains($new, $existing);
    }
}
