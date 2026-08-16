<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../modules/dashboard/AnalyticsService.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not available.'
        );
    }

    $analyticsService = new AnalyticsService($pdo);

    $analytics = $analyticsService->getAnalyticsPackage();

    echo json_encode(
        $analytics,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode(
        [
            'error' => true,
            'message' => 'Unable to load analytics data.',
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}