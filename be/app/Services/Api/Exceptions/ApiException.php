<?php

declare(strict_types=1);

namespace App\Services\Api\Exceptions;

abstract class ApiException extends \Exception
{
    abstract public function shouldRetry(): bool;
}