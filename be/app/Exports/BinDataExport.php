<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\BinData;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BinDataExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['binLookup.binImport']);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'BIN Number',
            'Bank Name',
            'Card Type',
            'Card Brand',
            'Country Code',
            'Country Name',
            'Website',
            'Phone',
            'Import File',
            'Import Date',
            'Created At',
        ];
    }

    /**
     * @param  BinData  $binData
     * @return array<int, mixed>
     */
    public function map($binData): array
    {
        return [
            $binData->bin_number,
            $binData->bank_name,
            $binData->card_type,
            $binData->card_brand,
            $binData->country_code,
            $binData->country_name,
            $binData->website,
            $binData->phone,
            $binData->binLookup->binImport->filename,
            $binData->binLookup->binImport->created_at->format('Y-m-d H:i:s'),
            $binData->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
