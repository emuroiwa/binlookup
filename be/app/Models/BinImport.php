<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BinImport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'filename',
        'total_bins',
        'processed_bins',
        'failed_bins',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => ImportStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_bins' => 'integer',
        'processed_bins' => 'integer',
        'failed_bins' => 'integer',
    ];

    public function binLookups(): HasMany
    {
        return $this->hasMany(BinLookup::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_bins === 0) {
            return 0;
        }

        return (int) round(($this->processed_bins + $this->failed_bins) / $this->total_bins * 100);
    }

    public function getSuccessRateAttribute(): float
    {
        $totalCompleted = $this->processed_bins + $this->failed_bins;

        if ($totalCompleted === 0) {
            return 0.0;
        }

        return round($this->processed_bins / $totalCompleted * 100, 2);
    }
}
