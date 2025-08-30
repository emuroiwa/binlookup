<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BinDataResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bin_number' => $this->bin_number,
            'bank_name' => $this->bank_name,
            'card_type' => $this->card_type,
            'card_brand' => $this->card_brand,
            'country_code' => $this->country_code,
            'country_name' => $this->country_name,
            'website' => $this->website,
            'phone' => $this->phone,
            'import_filename' => $this->whenLoaded('binLookup', function () {
                return $this->binLookup->binImport->filename;
            }),
            'import_date' => $this->whenLoaded('binLookup', function () {
                return $this->binLookup->binImport->created_at->toISOString();
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
