<?php

declare(strict_types=1);

require_once __DIR__ . '/StatusService.php';

class AssignmentService
{
    public static function canAssign(string $currentStatus): bool
    {
        return StatusService::canTransition(
            $currentStatus,
            StatusService::ASSIGNED
        );
    }

    public static function validateAssignment(
        int $issueId,
        int $staffId,
        int $assignedBy
    ): bool {
        return $issueId > 0
            && $staffId > 0
            && $assignedBy > 0;
    }

    public static function normalizeNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }
}
