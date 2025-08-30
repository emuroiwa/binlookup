<?php

declare(strict_types=1);

namespace App\Services\Api\Exceptions;

class ApiRateLimitException extends ApiException
{
    public function shouldRetry(): bool
    {
        return true;
    }

    public function getRetryAfter(): int
    {
        return 60; // seconds
    }
}