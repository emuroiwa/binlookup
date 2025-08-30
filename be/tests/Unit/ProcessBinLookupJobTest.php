<?php

use App\Jobs\ProcessBinLookupJob;
use App\Services\BinLookupService;
use App\Services\BinImportService;
use App\Models\BinLookup;
use App\Models\BinImport;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe("ProcessBinLookupJob", function () {
    beforeEach(function () {
        $this->lookupService = Mockery::mock(BinLookupService::class);
        $this->importService = Mockery::mock(BinImportService::class);
        $this->binLookupRepo = Mockery::mock(BinLookupRepositoryInterface::class);
        
        Log::shouldReceive("error")->byDefault();
        Log::shouldReceive("info")->byDefault();
        
        DB::shouldReceive("transaction")
            ->byDefault()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });
    });

    describe("handle", function () {
        it("processes bin lookup successfully", function () {
            $binLookup = BinLookup::factory()->make([
                "id" => "test-lookup-id",
                "bin_number" => "123456"
            ]);
            $binImport = BinImport::factory()->make();
            $binLookup->binImport = $binImport;

            $this->binLookupRepo
                ->shouldReceive("find")
                ->once()
                ->with("test-lookup-id")
                ->andReturn($binLookup);

            $this->lookupService
                ->shouldReceive("fetchAndStore")
                ->once()
                ->with($binLookup);

            $this->importService
                ->shouldReceive("updateProgress")
                ->once()
                ->with($binImport);

            $job = new ProcessBinLookupJob("test-lookup-id");
            $job->handle($this->lookupService, $this->importService, $this->binLookupRepo);
        });

        it("logs error when lookup not found", function () {
            $this->binLookupRepo
                ->shouldReceive("find")
                ->once()
                ->with("nonexistent-id")
                ->andReturn(null);

            Log::shouldReceive("error")
                ->once()
                ->with("BinLookup not found", ["id" => "nonexistent-id"]);

            $job = new ProcessBinLookupJob("nonexistent-id");
            $job->handle($this->lookupService, $this->importService, $this->binLookupRepo);
        });

        it("handles retryable errors with re-dispatch", function () {
            $binLookup = BinLookup::factory()->make([
                "id" => "test-lookup-id",
                "bin_number" => "123456",
                "attempts" => 1
            ]);
            $binImport = BinImport::factory()->make();
            $binLookup->binImport = $binImport;

            $this->binLookupRepo
                ->shouldReceive("find")
                ->once()
                ->andReturn($binLookup);

            $retryableException = new Exception("Rate limit exceeded");

            $this->lookupService
                ->shouldReceive("fetchAndStore")
                ->once()
                ->andThrow($retryableException);

            $this->importService
                ->shouldReceive("updateProgress")
                ->once();

            Queue::fake();

            $job = new ProcessBinLookupJob("test-lookup-id");
            $job->handle($this->lookupService, $this->importService, $this->binLookupRepo);

            Queue::assertPushed(ProcessBinLookupJob::class);
        });

        it("does not retry non-retryable errors", function () {
            $binLookup = BinLookup::factory()->make([
                "id" => "test-lookup-id",
                "attempts" => 1
            ]);
            $binImport = BinImport::factory()->make();
            $binLookup->binImport = $binImport;

            $this->binLookupRepo
                ->shouldReceive("find")
                ->once()
                ->andReturn($binLookup);

            $nonRetryableException = new Exception("Invalid BIN format");

            $this->lookupService
                ->shouldReceive("fetchAndStore")
                ->once()
                ->andThrow($nonRetryableException);

            $this->importService
                ->shouldReceive("updateProgress")
                ->once();

            Queue::fake();

            $job = new ProcessBinLookupJob("test-lookup-id");
            $job->handle($this->lookupService, $this->importService, $this->binLookupRepo);

            Queue::assertNotPushed(ProcessBinLookupJob::class);
        });

        it("does not retry after max attempts", function () {
            $binLookup = BinLookup::factory()->make([
                "id" => "test-lookup-id",
                "attempts" => 3
            ]);
            $binImport = BinImport::factory()->make();
            $binLookup->binImport = $binImport;

            $this->binLookupRepo
                ->shouldReceive("find")
                ->once()
                ->andReturn($binLookup);

            $retryableException = new Exception("Rate limit exceeded");

            $this->lookupService
                ->shouldReceive("fetchAndStore")
                ->once()
                ->andThrow($retryableException);

            $this->importService
                ->shouldReceive("updateProgress")
                ->once();

            Queue::fake();

            $job = new ProcessBinLookupJob("test-lookup-id");
            $job->handle($this->lookupService, $this->importService, $this->binLookupRepo);

            Queue::assertNotPushed(ProcessBinLookupJob::class);
        });
    });

    describe("backoff calculation", function () {
        it("calculates exponential backoff delay correctly", function () {
            $job = new ProcessBinLookupJob("test-id");
            $reflection = new ReflectionClass($job);
            $method = $reflection->getMethod("calculateBackoffDelay");
            $method->setAccessible(true);

            expect($method->invoke($job, 0))->toBe(10);
            expect($method->invoke($job, 1))->toBe(20);
            expect($method->invoke($job, 2))->toBe(40);
            expect($method->invoke($job, 10))->toBe(300); // Max 5 minutes
        });
    });

    describe("retryable error detection", function () {
        it("identifies retryable errors correctly", function () {
            $job = new ProcessBinLookupJob("test-id");
            $reflection = new ReflectionClass($job);
            $method = $reflection->getMethod("isRetryableError");
            $method->setAccessible(true);

            $retryableErrors = [
                new Exception("Rate limit exceeded"),
                new Exception("Connection error occurred"),
                new Exception("Server error 500"),
                new Exception("Request timeout")
            ];

            foreach ($retryableErrors as $error) {
                expect($method->invoke($job, $error))->toBeTrue();
            }

            $nonRetryableError = new Exception("Invalid BIN format");
            expect($method->invoke($job, $nonRetryableError))->toBeFalse();
        });
    });

    describe("failed", function () {
        it("marks lookup as failed when job fails permanently", function () {
            $binLookup = BinLookup::factory()->make([
                "id" => "test-lookup-id"
            ]);
            $binImport = BinImport::factory()->make();
            $binLookup->binImport = $binImport;

            $this->binLookupRepo
                ->shouldReceive("find")
                ->once()
                ->with("test-lookup-id")
                ->andReturn($binLookup);

            $this->binLookupRepo
                ->shouldReceive("update")
                ->once()
                ->with($binLookup, Mockery::subset([
                    "status" => "failed"
                ]));

            $this->importService
                ->shouldReceive("updateProgress")
                ->once()
                ->with($binImport);

            app()->instance(BinLookupRepositoryInterface::class, $this->binLookupRepo);
            app()->instance(BinImportService::class, $this->importService);

            $job = new ProcessBinLookupJob("test-lookup-id");
            $exception = new Exception("Job failed permanently");

            $job->failed($exception);
        });
    });

    describe("queue configuration", function () {
        it("has correct timeout and max exceptions", function () {
            $job = new ProcessBinLookupJob("test-id");

            expect($job->timeout)->toBe(120);
            expect($job->maxExceptions)->toBe(3);
        });

        it("has correct backoff intervals", function () {
            $job = new ProcessBinLookupJob("test-id");
            
            expect($job->backoff())->toBe([30, 120, 300]);
        });

        it("has correct retry until time", function () {
            $job = new ProcessBinLookupJob("test-id");
            $retryUntil = $job->retryUntil();
            
            expect($retryUntil)->toBeInstanceOf(DateTime::class);
        });
    });
});
