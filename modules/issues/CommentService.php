<?php

declare(strict_types=1);

class CommentService
{
    public static function validateComment(string $comment): bool
    {
        return trim($comment) !== '';
    }

    public static function normalizeComment(string $comment): string
    {
        return trim($comment);
    }
}
