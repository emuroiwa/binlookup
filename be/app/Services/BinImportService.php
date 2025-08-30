<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Import\BinImportProcessingService;
use App\Models\BinImport;
use Illuminate\Http\UploadedFile;

class BinImportService
{
    public function __construct(
        private readonly BinImportProcessingService $processingService
    ) {}

    /**
     * Import BINs from CSV file and create lookup jobs
     */
    public function importFromCsv(UploadedFile $file): BinImport
    {
        return $this->processingService->processImport($file);
    }

    /**
     * Update import progress counters
     */
    public function updateProgress(BinImport $import): void
    {
        $this->processingService->updateProgress($import);
    }

    /**
     * Check if import is completed and update status accordingly
     */
    public function checkImportCompletion(BinImport $import): void
    {
        $this->updateProgress($import);
    }
}
