<?php

declare(strict_types=1);

require_once __DIR__ . '/LostFoundRepository.php';

/**
 * Business logic for CampusFix Lost & Found.
 *
 * This service validates input and controls allowed workflow
 * transitions before calling LostFoundRepository.
 */
class LostFoundService
{
    private LostFoundRepository $repository;

    public function __construct(LostFoundRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Submit a new lost/found item.
     *
     * The logged-in user's ID is supplied separately so a user
     * cannot choose another user's ID through form input.
     */
    public function submitItem(array $input, int $userId): int
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user.');
        }

        $categoryId = (int) ($input['item_category_id'] ?? 0);
        $locationId = (int) ($input['location_id'] ?? 0);

        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Item category is required.');
        }

        if ($locationId <= 0) {
            throw new InvalidArgumentException('Location is required.');
        }

        $itemType = trim((string) ($input['item_type'] ?? ''));

        if (!in_array($itemType, ['Lost', 'Found'], true)) {
            throw new InvalidArgumentException(
                'Item type must be Lost or Found.'
            );
        }

        $itemName = trim((string) ($input['item_name'] ?? ''));

        if ($itemName === '') {
            throw new InvalidArgumentException('Item name is required.');
        }

        if (strlen($itemName) > 150) {
            throw new InvalidArgumentException(
                'Item name cannot exceed 150 characters.'
            );
        }

        $description = trim((string) ($input['description'] ?? ''));

        $itemDate = trim((string) ($input['item_date'] ?? ''));

        if (!$this->isValidDate($itemDate)) {
            throw new InvalidArgumentException(
                'A valid item date is required.'
            );
        }

        if ($this->isFutureDate($itemDate)) {
            throw new InvalidArgumentException(
                'Item date cannot be in the future.'
            );
        }

        $imagePath = isset($input['image_path'])
            ? trim((string) $input['image_path'])
            : null;

        if ($imagePath === '') {
            $imagePath = null;
        }

        return $this->repository->create([
            'item_category_id' => $categoryId,
            'posted_by'        => $userId,
            'location_id'      => $locationId,
            'item_type'        => $itemType,
            'item_name'        => $itemName,
            'description'      => $description !== '' ? $description : null,
            'item_date'        => $itemDate,
            'image_path'       => $imagePath,
        ]);
    }

    /**
     * Browse approved Lost & Found items.
     */
    public function browseApproved(array $filters = []): array
    {
        $cleanFilters = [];

        if (!empty($filters['type'])) {
            $type = trim((string) $filters['type']);

            if (in_array($type, ['Lost', 'Found'], true)) {
                $cleanFilters['type'] = $type;
            }
        }

        if (!empty($filters['category_id'])) {
            $categoryId = (int) $filters['category_id'];

            if ($categoryId > 0) {
                $cleanFilters['category_id'] = $categoryId;
            }
        }

        if (!empty($filters['location_id'])) {
            $locationId = (int) $filters['location_id'];

            if ($locationId > 0) {
                $cleanFilters['location_id'] = $locationId;
            }
        }

        if (!empty($filters['date'])) {
            $date = trim((string) $filters['date']);

            if ($this->isValidDate($date)) {
                $cleanFilters['date'] = $date;
            }
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            if ($search !== '') {
                $cleanFilters['search'] = $search;
            }
        }

        return $this->repository->getApproved($cleanFilters);
    }

    /**
     * Get one item.
     */
    public function getItem(int $itemId): array
    {
        if ($itemId <= 0) {
            throw new InvalidArgumentException('Invalid item ID.');
        }

        $item = $this->repository->findById($itemId);

        if ($item === null) {
            throw new RuntimeException('Lost & Found item not found.');
        }

        return $item;
    }

    /**
     * Get items submitted by the logged-in student.
     */
    public function getUserItems(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user.');
        }

        return $this->repository->getByUser($userId);
    }

    /**
     * Get pending submissions for admin moderation.
     */
    public function getPendingItems(): array
    {
        return $this->repository->getPending();
    }

    /**
     * Approve a pending item.
     */
    public function approveItem(int $itemId): bool
    {
        $item = $this->getItem($itemId);

        if ($item['status'] !== 'Pending') {
            throw new RuntimeException(
                'Only pending items can be approved.'
            );
        }

        return $this->repository->approve($itemId);
    }

    /**
     * Reject a pending item.
     */
    public function rejectItem(int $itemId): bool
    {
        $item = $this->getItem($itemId);

        if ($item['status'] !== 'Pending') {
            throw new RuntimeException(
                'Only pending items can be rejected.'
            );
        }

        return $this->repository->reject($itemId);
    }

    /**
     * Mark an approved item as returned.
     */
    public function markItemReturned(int $itemId): bool
    {
        $item = $this->getItem($itemId);

        if ($item['status'] !== 'Approved') {
            throw new RuntimeException(
                'Only approved items can be marked as returned.'
            );
        }

        return $this->repository->markReturned($itemId);
    }

    /**
     * Basic Lost & Found counts for dashboard/backend use.
     */
    public function getStatistics(): array
    {
        return [
            'total'    => $this->repository->countAll(),
            'pending'  => $this->repository->countByStatus('Pending'),
            'approved' => $this->repository->countByStatus('Approved'),
            'rejected' => $this->repository->countByStatus('Rejected'),
            'returned' => $this->repository->countByStatus('Returned'),
        ];
    }

    /**
     * Validate YYYY-MM-DD date format.
     */
    private function isValidDate(string $date): bool
    {
        $parsedDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        return $parsedDate !== false
            && $parsedDate->format('Y-m-d') === $date;
    }

    /**
     * Check whether an item date is after today.
     */
    private function isFutureDate(string $date): bool
    {
        $itemDate = new DateTimeImmutable($date);
        $today = new DateTimeImmutable('today');

        return $itemDate > $today;
    }
}