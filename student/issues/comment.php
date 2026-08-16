<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/CommentService.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';

require_role(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_issues.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request token.');
}

$userId = (int) $_SESSION['user_id'];
$issueId = (int) ($_POST['issue_id'] ?? 0);
$comment = (string) ($_POST['comment'] ?? '');

if ($issueId <= 0) {
    http_response_code(400);
    exit('Invalid issue.');
}

if (!CommentService::validateComment($comment)) {
    http_response_code(400);
    exit('Please enter a comment.');
}

$repository = new IssueRepository($pdo);
$issue = $repository->findById($issueId);

if ($issue === null) {
    http_response_code(404);
    exit('Issue not found.');
}

$isOwner = (int) $issue['reported_by'] === $userId;
$isPublic = (string) $issue['visibility'] === 'Public';

if (!$isOwner && !$isPublic) {
    http_response_code(403);
    exit('Access denied.');
}

$comment = CommentService::normalizeComment($comment);

try {
    $repository->addComment(
        $issueId,
        $userId,
        $comment
    );

    header('Location: detail.php?id=' . $issueId);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    exit('Unable to add the comment.');
}
