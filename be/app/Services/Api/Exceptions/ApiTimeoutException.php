<?php

declare(strict_types=1);

namespace App\Services\Api\Exceptions;

class ApiTimeoutException extends ApiException
{
    public function shouldRetry(): bool
    {
        return true;
    }
}