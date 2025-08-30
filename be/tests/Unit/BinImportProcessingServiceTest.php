<?php

use App\Services\Import\BinImportProcessingService;
use App\Services\Import\BinImportValidationService;
use App\Models\BinImport;
use App\Models\BinLookup;
use App\Enums\ImportStatus;
use App\Jobs\ProcessBinLookupJob;
use App\Repositories\Contracts\BinImportRepositoryInterface;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

describe("BinImportProcessingService", function () {
    beforeEach(function () {
        $this->binImportRepo = Mockery::mock(BinImportRepositoryInterface::class);
        $this->binLookupRepo = Mockery::mock(BinLookupRepositoryInterface::class);
        $this->validationService = Mockery::mock(BinImportValidationService::class);
        
        $this->service = new BinImportProcessingService(
            $this->binImportRepo,
            $this->binLookupRepo,
            $this->validationService
        );
    });

    describe("processImport", function () {
        it("validates file before processing", function () {
            $file = UploadedFile::fake()->createWithContent("test.csv", "bin\n123456\n789012");
            $binImport = BinImport::factory()->make([
                "id" => "test-uuid"
            ]);
            
            $this->validationService
                ->shouldReceive("validateFile")
                ->once()
                ->with($file)
                ->andReturn([]);

            $this->binImportRepo
                ->shouldReceive("create")
                ->once()
                ->andReturn($binImport);

            $this->binImportRepo
                ->shouldReceive("update")
                ->once()
                ->with($binImport, Mockery::subset(["total_bins" => 2]));

            $this->binLookupRepo
                ->shouldReceive("batchInsert")
                ->once();

            $this->binImportRepo
                ->shouldReceive("update")
                ->once()
                ->with($binImport, Mockery::subset(["status" => ImportStatus::PROCESSING]));

            $this->binLookupRepo
                ->shouldReceive("findByImportId")
                ->once()
                ->with("test-uuid", 100)
                ->andReturn(collect([])->lazy());

            Queue::fake();
            DB::shouldReceive("transaction")
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $result = $this->service->processImport($file);

            expect($result)->toBeInstanceOf(BinImport::class);
        });

        it("throws exception when validation fails", function () {
            $file = UploadedFile::fake()->create("invalid.txt");
            
            $this->validationService
                ->shouldReceive("validateFile")
                ->once()
                ->andReturn(["File must be CSV format"]);

            expect(fn() => $this->service->processImport($file))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe("updateProgress", function () {
        it("updates import with current statistics", function () {
            $import = BinImport::factory()->make([
                "id" => "test-uuid",
                "total_bins" => 10
            ]);

            $stats = (object) [
                "completed" => 8,
                "failed" => 1,
                "total" => 10
            ];

            $this->binLookupRepo
                ->shouldReceive("getStatsByImport")
                ->once()
                ->with($import)
                ->andReturn($stats);

            $this->binImportRepo
                ->shouldReceive("update")
                ->once()
                ->with($import, Mockery::subset([
                    "processed_bins" => 8,
                    "failed_bins" => 1
                ]));

            $this->service->updateProgress($import);
        });
    });
});
