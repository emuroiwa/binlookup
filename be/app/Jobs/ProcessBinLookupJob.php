<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\Contracts\BinLookupRepositoryInterface;
use App\Services\BinImportService;
use App\Services\BinLookupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBinLookupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly string $binLookupId
    ) {
        $this->onQueue('bin-lookups');
    }

    public function handle(
        BinLookupService $lookupService,
        BinImportService $importService,
        BinLookupRepositoryInterface $binLookupRepository
    ): void {
        $lookup = $binLookupRepository->find($this->binLookupId);

        if (! $lookup) {
            Log::error('BinLookup not found', ['id' => $this->binLookupId]);

            return;
        }

        DB::transaction(function () use ($lookup, $lookupService, $importService) {
            try {
                $lookupService->fetchAndStore($lookup);
            } catch (\Exception $e) {
                Log::error('BIN lookup processing failed', [
                    'lookup_id' => $lookup->id,
                    'bin' => $lookup->bin_number,
                    'error' => $e->getMessage(),
                    'attempts' => $lookup->attempts,
                ]);

                // Re-queue for retry if attempts < 3 and error is retryable
                if ($lookup->attempts < 3 && $this->isRetryableError($e)) {
                    static::dispatch($this->binLookupId)
                        ->delay(now()->addSeconds($this->calculateBackoffDelay($lookup->attempts)));
                }
            }

            // Update parent import progress
            $importService->updateProgress($lookup->binImport);
        });
    }

    /**
     * Calculate exponential backoff delay
     */
    private function calculateBackoffDelay(int $attempts): int
    {
        return min(pow(2, $attempts) * 10, 300); // Max 5 minutes
    }

    /**
     * Determine if error is retryable
     */
    private function isRetryableError(\Exception $e): bool
    {
        $retryableMessages = [
            'Rate limit exceeded',
            'Connection error',
            'Server error',
            'timeout',
        ];

        foreach ($retryableMessages as $message) {
            if (str_contains(strtolower($e->getMessage()), strtolower($message))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     */
    public function backoff(): array
    {
        return [30, 120, 300]; // 30s, 2min, 5min
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        $binLookupRepository = app(BinLookupRepositoryInterface::class);
        $lookup = $binLookupRepository->find($this->binLookupId);

        if ($lookup) {
            DB::transaction(function () use ($lookup, $exception, $binLookupRepository) {
                $binLookupRepository->update($lookup, [
                    'status' => 'failed',
                    'error_message' => "Job failed after max retries: {$exception->getMessage()}",
                ]);

                // Update import progress
                app(BinImportService::class)->updateProgress($lookup->binImport);
            });
        }

        Log::error('ProcessBinLookupJob permanently failed', [
            'lookup_id' => $this->binLookupId,
            'error' => $exception->getMessage(),
        ]);
    }
}
