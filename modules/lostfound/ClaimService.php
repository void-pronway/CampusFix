<?php

declare(strict_types=1);

require_once __DIR__ . '/ClaimRepository.php';
require_once __DIR__ . '/LostFoundRepository.php';

/**
 * Business logic for CampusFix Lost & Found claims.
 */
class ClaimService
{
    private ClaimRepository $claimRepository;
    private LostFoundRepository $itemRepository;

    public function __construct(
        ClaimRepository $claimRepository,
        LostFoundRepository $itemRepository
    ) {
        $this->claimRepository = $claimRepository;
        $this->itemRepository = $itemRepository;
    }

    /**
     * Submit a claim for an approved Lost & Found item.
     */
    public function submitClaim(
        int $itemId,
        int $userId,
        string $claimMessage
    ): int {
        if ($itemId <= 0) {
            throw new InvalidArgumentException('Invalid item ID.');
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user.');
        }

        $claimMessage = trim($claimMessage);

        if ($claimMessage === '') {
            throw new InvalidArgumentException(
                'Claim message is required.'
            );
        }

        if (strlen($claimMessage) > 2000) {
            throw new InvalidArgumentException(
                'Claim message cannot exceed 2000 characters.'
            );
        }

        $item = $this->itemRepository->findById($itemId);

        if ($item === null) {
            throw new RuntimeException(
                'Lost & Found item not found.'
            );
        }

        if ($item['status'] !== 'Approved') {
            throw new RuntimeException(
                'Claims can only be submitted for approved items.'
            );
        }

        if ((int) $item['posted_by'] === $userId) {
            throw new RuntimeException(
                'You cannot claim an item that you posted.'
            );
        }

        if (
            $this->claimRepository->hasPendingClaim(
                $itemId,
                $userId
            )
        ) {
            throw new RuntimeException(
                'You already have a pending claim for this item.'
            );
        }

        return $this->claimRepository->create(
            $itemId,
            $userId,
            $claimMessage
        );
    }

    /**
     * Get a single claim.
     */
    public function getClaim(int $claimId): array
    {
        if ($claimId <= 0) {
            throw new InvalidArgumentException(
                'Invalid claim ID.'
            );
        }

        $claim = $this->claimRepository->findById($claimId);

        if ($claim === null) {
            throw new RuntimeException('Claim not found.');
        }

        return $claim;
    }

    /**
     * Return claims submitted by a student.
     */
    public function getUserClaims(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user.');
        }

        return $this->claimRepository->getByUser($userId);
    }

    /**
     * Return all claims for one Lost & Found item.
     */
    public function getItemClaims(int $itemId): array
    {
        if ($itemId <= 0) {
            throw new InvalidArgumentException(
                'Invalid item ID.'
            );
        }

        return $this->claimRepository->getByItem($itemId);
    }

    /**
     * Return claims waiting for admin review.
     */
    public function getPendingClaims(): array
    {
        return $this->claimRepository->getPending();
    }

    /**
     * Approve a claim.
     *
     * ClaimRepository performs the approval transaction:
     * - approve selected claim
     * - reject competing pending claims
     * - mark item Returned
     */
    public function approveClaim(
        int $claimId,
        int $adminId,
        ?string $adminNote = null
    ): bool {
        if ($adminId <= 0) {
            throw new InvalidArgumentException(
                'Invalid admin user.'
            );
        }

        $claim = $this->getClaim($claimId);

        if ($claim['status'] !== 'Pending') {
            throw new RuntimeException(
                'Only pending claims can be approved.'
            );
        }

        if ($claim['item_status'] !== 'Approved') {
            throw new RuntimeException(
                'The item is no longer available for claiming.'
            );
        }

        $adminNote = $this->cleanAdminNote($adminNote);

        return $this->claimRepository->approve(
            $claimId,
            $adminId,
            $adminNote
        );
    }

    /**
     * Reject a pending claim.
     */
    public function rejectClaim(
        int $claimId,
        int $adminId,
        ?string $adminNote = null
    ): bool {
        if ($adminId <= 0) {
            throw new InvalidArgumentException(
                'Invalid admin user.'
            );
        }

        $claim = $this->getClaim($claimId);

        if ($claim['status'] !== 'Pending') {
            throw new RuntimeException(
                'Only pending claims can be rejected.'
            );
        }

        $adminNote = $this->cleanAdminNote($adminNote);

        $rejected = $this->claimRepository->reject(
            $claimId,
            $adminId,
            $adminNote
        );

        if (!$rejected) {
            throw new RuntimeException(
                'The claim could not be rejected.'
            );
        }

        return true;
    }

    /**
     * Claim statistics for dashboard/backend use.
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->claimRepository->countAll(),
            'pending' =>
                $this->claimRepository->countByStatus('Pending'),
            'approved' =>
                $this->claimRepository->countByStatus('Approved'),
            'rejected' =>
                $this->claimRepository->countByStatus('Rejected'),
        ];
    }

    /**
     * Clean optional admin review note.
     */
    private function cleanAdminNote(?string $adminNote): ?string
    {
        if ($adminNote === null) {
            return null;
        }

        $adminNote = trim($adminNote);

        if ($adminNote === '') {
            return null;
        }

        if (strlen($adminNote) > 2000) {
            throw new InvalidArgumentException(
                'Admin note cannot exceed 2000 characters.'
            );
        }

        return $adminNote;
    }
}