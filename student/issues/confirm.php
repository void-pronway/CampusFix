<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: report.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request token.');
}

$userId = (int) $_SESSION['user_id'];
$action = trim($_POST['action'] ?? '');

$repository = new IssueRepository($pdo);

if ($action === 'create') {
    $pendingIssue = $_SESSION['pending_issue'] ?? null;

    if (!is_array($pendingIssue)) {
        http_response_code(400);
        exit('No pending issue was found.');
    }

    $requiredFields = [
        'title',
        'description',
        'category_id',
        'location_id',
        'priority',
        'visibility',
    ];

    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $pendingIssue)) {
            http_response_code(400);
            exit('Pending issue data is incomplete.');
        }
    }

    try {
        $pdo->beginTransaction();

        $issueId = $repository->create([
            'category_id' => (int) $pendingIssue['category_id'],
            'reported_by' => $userId,
            'location_id' => (int) $pendingIssue['location_id'],
            'title' => trim((string) $pendingIssue['title']),
            'description' => trim((string) $pendingIssue['description']),
            'visibility' => (string) $pendingIssue['visibility'],
            'priority' => (string) $pendingIssue['priority'],
            'image_path' => null,
        ]);

        $repository->addStatusLog(
            $issueId,
            $userId,
            null,
            StatusService::PENDING
        );

        $pdo->commit();

        unset($_SESSION['pending_issue']);

        header('Location: detail.php?id=' . $issueId);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        exit('Unable to create the issue.');
    }
}

if ($action === 'confirm') {
    $issueId = (int) ($_POST['issue_id'] ?? 0);

    if ($issueId <= 0) {
        http_response_code(400);
        exit('Invalid issue.');
    }

    $issue = $repository->findById($issueId);

    if ($issue === null) {
        http_response_code(404);
        exit('Issue not found.');
    }

    if ((int) $issue['reported_by'] === $userId) {
        http_response_code(400);
        exit('You cannot confirm your own issue.');
    }

    try {
        if (!$repository->hasUserConfirmed($issueId, $userId)) {
            $repository->addConfirmation($issueId, $userId);
        }

        unset($_SESSION['pending_issue']);

        header('Location: detail.php?id=' . $issueId);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Unable to confirm the issue.');
    }
}

http_response_code(400);
exit('Invalid action.');
