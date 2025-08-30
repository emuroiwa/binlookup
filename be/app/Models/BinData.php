<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class BinData extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bin_lookup_id',
        'bin_number',
        'bank_name',
        'card_type',
        'card_brand',
        'country_code',
        'country_name',
        'website',
        'phone',
        'api_response',
    ];

    protected $casts = [
        'api_response' => 'array',
    ];

    public function binLookup(): BelongsTo
    {
        return $this->belongsTo(BinLookup::class);
    }

    public function binImport(): HasOneThrough
    {
        return $this->hasOneThrough(BinImport::class, BinLookup::class, 'id', 'id', 'bin_lookup_id', 'bin_import_id');
    }
}
