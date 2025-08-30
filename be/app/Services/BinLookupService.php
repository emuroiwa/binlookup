<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LookupStatus;
use App\Models\BinLookup;
use App\Repositories\Contracts\BinDataRepositoryInterface;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use App\Services\Api\BinApiService;
use App\Services\Api\Exceptions\ApiException;
use Illuminate\Support\Facades\Log;

class BinLookupService
{
    public function __construct(
        private readonly BinLookupRepositoryInterface $binLookupRepository,
        private readonly BinDataRepositoryInterface $binDataRepository,
        private readonly BinApiService $apiService
    ) {}

    /**
     * Fetch BIN data from external API and store normalized result
     */
    public function fetchAndStore(BinLookup $lookup): void
    {
        $this->binLookupRepository->update($lookup, [
            'status' => LookupStatus::PROCESSING,
            'last_attempted_at' => now(),
            'attempts' => $lookup->attempts + 1,
        ]);

        try {
            $apiResponse = $this->apiService->lookupBin($lookup->bin_number);
            $this->binDataRepository->createFromApiResponse($lookup, $apiResponse);

            $this->binLookupRepository->update($lookup, [
                'status' => LookupStatus::COMPLETED,
                'error_message' => null,
            ]);

            Log::debug('BIN lookup completed', [
                'bin' => $lookup->bin_number,
                'lookup_id' => $lookup->id,
            ]);

        } catch (ApiException $e) {
            $this->handleApiException($lookup, $e);
        } catch (\Exception $e) {
            $this->handleGenericException($lookup, $e);
        }
    }

    private function handleApiException(BinLookup $lookup, ApiException $e): void
    {
        $shouldRetry = $e->shouldRetry() && $lookup->attempts < 3;
        
        $this->binLookupRepository->update($lookup, [
            'status' => $shouldRetry ? LookupStatus::PENDING : LookupStatus::FAILED,
            'error_message' => $e->getMessage(),
        ]);

        Log::warning('BIN API lookup failed', [
            'bin' => $lookup->bin_number,
            'lookup_id' => $lookup->id,
            'error' => $e->getMessage(),
            'exception_type' => get_class($e),
            'attempts' => $lookup->attempts,
            'will_retry' => $shouldRetry,
        ]);
    }

    private function handleGenericException(BinLookup $lookup, \Exception $e): void
    {
        $this->binLookupRepository->update($lookup, [
            'status' => LookupStatus::FAILED,
            'error_message' => $e->getMessage(),
        ]);

        Log::error('BIN lookup failed with unexpected error', [
            'bin' => $lookup->bin_number,
            'lookup_id' => $lookup->id,
            'error' => $e->getMessage(),
            'exception_type' => get_class($e),
            'attempts' => $lookup->attempts,
        ]);
    }
}
