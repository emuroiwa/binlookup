<?php

use App\Services\BinLookupService;
use App\Services\Api\BinApiService;
use App\Services\Api\Exceptions\ApiRateLimitException;
use App\Services\Api\Exceptions\ApiTimeoutException;
use App\Services\Api\Exceptions\ApiUnavailableException;
use App\Models\BinLookup;
use App\Enums\LookupStatus;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use App\Repositories\Contracts\BinDataRepositoryInterface;
use Illuminate\Support\Facades\Log;

describe("BinLookupService", function () {
    beforeEach(function () {
        $this->binLookupRepo = Mockery::mock(BinLookupRepositoryInterface::class);
        $this->binDataRepo = Mockery::mock(BinDataRepositoryInterface::class);
        $this->apiService = Mockery::mock(BinApiService::class);
        
        $this->service = new BinLookupService(
            $this->binLookupRepo,
            $this->binDataRepo,
            $this->apiService
        );

        Log::shouldReceive("debug")->byDefault();
        Log::shouldReceive("warning")->byDefault();
        Log::shouldReceive("error")->byDefault();
    });

    describe("fetchAndStore", function () {
        it("successfully processes BIN lookup", function () {
            $lookup = BinLookup::factory()->make([
                "id" => "test-uuid",
                "bin_number" => "123456",
                "attempts" => 0,
                "status" => LookupStatus::PENDING
            ]);

            $apiResponse = [
                "bank_name" => "Test Bank",
                "card_type" => "debit",
                "card_brand" => "visa"
            ];

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset([
                    "status" => LookupStatus::PROCESSING,
                    "attempts" => 1
                ]));

            $this->apiService
                ->shouldReceive("lookupBin")
                ->once()
                ->with("123456")
                ->andReturn($apiResponse);

            $this->binDataRepo
                ->shouldReceive("createFromApiResponse")
                ->once()
                ->with($lookup, $apiResponse);

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset([
                    "status" => LookupStatus::COMPLETED,
                    "error_message" => null
                ]));

            $this->service->fetchAndStore($lookup);
        });

        it("handles API rate limit exception with retry", function () {
            $lookup = BinLookup::factory()->make([
                "bin_number" => "123456",
                "attempts" => 1
            ]);

            $exception = new ApiRateLimitException("Rate limit exceeded");

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset(["status" => LookupStatus::PROCESSING]));

            $this->apiService
                ->shouldReceive("lookupBin")
                ->once()
                ->andThrow($exception);

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset([
                    "status" => LookupStatus::PENDING,
                    "error_message" => "Rate limit exceeded"
                ]));

            $this->service->fetchAndStore($lookup);
        });

        it("handles API timeout exception without retry after max attempts", function () {
            $lookup = BinLookup::factory()->make([
                "bin_number" => "123456",
                "attempts" => 3
            ]);

            $exception = new ApiTimeoutException("Request timeout");

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset(["status" => LookupStatus::PROCESSING]));

            $this->apiService
                ->shouldReceive("lookupBin")
                ->once()
                ->andThrow($exception);

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset([
                    "status" => LookupStatus::FAILED,
                    "error_message" => "Request timeout"
                ]));

            $this->service->fetchAndStore($lookup);
        });

        it("handles generic exceptions as failed", function () {
            $lookup = BinLookup::factory()->make([
                "bin_number" => "123456",
                "attempts" => 1
            ]);

            $exception = new Exception("Unexpected error");

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset(["status" => LookupStatus::PROCESSING]));

            $this->apiService
                ->shouldReceive("lookupBin")
                ->once()
                ->andThrow($exception);

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset([
                    "status" => LookupStatus::FAILED,
                    "error_message" => "Unexpected error"
                ]));

            $this->service->fetchAndStore($lookup);
        });

        it("increments attempt count on each call", function () {
            $lookup = BinLookup::factory()->make([
                "attempts" => 2
            ]);

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($lookup, Mockery::subset([
                    "attempts" => 3
                ]));

            $this->apiService
                ->shouldReceive("lookupBin")
                ->andThrow(new Exception("Test error"));

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once();

            $this->service->fetchAndStore($lookup);
        });
    });
});
