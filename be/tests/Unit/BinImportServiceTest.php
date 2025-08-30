<?php

use App\Services\BinImportService;
use App\Services\Import\BinImportProcessingService;
use App\Models\BinImport;
use App\Enums\ImportStatus;
use Illuminate\Http\UploadedFile;

describe("BinImportService", function () {
    beforeEach(function () {
        $this->processingService = Mockery::mock(BinImportProcessingService::class);
        $this->service = new BinImportService($this->processingService);
    });

    describe("importFromCsv", function () {
        it("delegates CSV processing to processing service", function () {
            $file = UploadedFile::fake()->create("bins.csv", 100, "text/csv");
            $binImport = BinImport::factory()->make([
                "id" => "test-uuid",
                "filename" => "bins.csv",
                "status" => ImportStatus::PROCESSING
            ]);

            $this->processingService
                ->shouldReceive("processImport")
                ->once()
                ->with($file)
                ->andReturn($binImport);

            $result = $this->service->importFromCsv($file);

            expect($result)->toBe($binImport);
        });
    });

    describe("updateProgress", function () {
        it("delegates progress update to processing service", function () {
            $binImport = BinImport::factory()->make();

            $this->processingService
                ->shouldReceive("updateProgress")
                ->once()
                ->with($binImport);

            $this->service->updateProgress($binImport);
        });
    });
});
