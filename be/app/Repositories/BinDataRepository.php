<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BinData;
use App\Models\BinLookup;
use App\Repositories\Contracts\BinDataRepositoryInterface;

class BinDataRepository implements BinDataRepositoryInterface
{
    public function updateOrCreate(array $attributes, array $values): BinData
    {
        return BinData::updateOrCreate($attributes, $values);
    }

    public function findByBinNumber(string $binNumber): ?BinData
    {
        return BinData::where('bin_number', $binNumber)->first();
    }

    public function findByLookupId(int $lookupId): ?BinData
    {
        return BinData::where('bin_lookup_id', $lookupId)->first();
    }

    public function createFromApiResponse(BinLookup $lookup, array $apiResponse): BinData
    {
        return $this->updateOrCreate(
            ['bin_lookup_id' => $lookup->id],
            [
                'bin_number' => $lookup->bin_number,
                'bank_name' => $apiResponse['bank']['name'] ?? null,
                'card_type' => $apiResponse['type'] ?? null,
                'card_brand' => $apiResponse['brand'] ?? $apiResponse['scheme'] ?? null,
                'country_code' => $apiResponse['country']['alpha2'] ?? null,
                'country_name' => $apiResponse['country']['name'] ?? null,
                'website' => $apiResponse['bank']['url'] ?? null,
                'phone' => $apiResponse['bank']['phone'] ?? null,
                'api_response' => $apiResponse,
            ]
        );
    }
}