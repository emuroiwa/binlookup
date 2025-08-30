<?php

declare(strict_types=1);

namespace App\Services\Api\Exceptions;

class ApiUnavailableException extends ApiException
{
    public function shouldRetry(): bool
    {
        return false;
    }
}