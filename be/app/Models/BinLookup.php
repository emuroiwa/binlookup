<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LookupStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BinLookup extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bin_import_id',
        'bin_number',
        'status',
        'attempts',
        'last_attempted_at',
        'error_message',
    ];

    protected $casts = [
        'status' => LookupStatus::class,
        'last_attempted_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function binImport(): BelongsTo
    {
        return $this->belongsTo(BinImport::class);
    }

    public function binData(): HasOne
    {
        return $this->hasOne(BinData::class);
    }
}
