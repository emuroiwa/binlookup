<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\BinData;
use App\Models\BinLookup;

interface BinDataRepositoryInterface
{
    public function updateOrCreate(array $attributes, array $values): BinData;

    public function findByBinNumber(string $binNumber): ?BinData;

    public function findByLookupId(int $lookupId): ?BinData;

    public function createFromApiResponse(BinLookup $lookup, array $apiResponse): BinData;
}