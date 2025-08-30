<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\ImportStatus;
use App\Jobs\ProcessBinLookupJob;
use App\Models\BinImport;
use App\Repositories\Contracts\BinImportRepositoryInterface;
use App\Repositories\Contracts\BinLookupRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinImportProcessingService
{
    public function __construct(
        private readonly BinImportRepositoryInterface $binImportRepository,
        private readonly BinLookupRepositoryInterface $binLookupRepository,
        private readonly BinImportValidationService $validationService
    ) {}

    public function processImport(UploadedFile $file): BinImport
    {
        $validationErrors = $this->validationService->validateFile($file);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException(implode(', ', $validationErrors));
        }

        return DB::transaction(function () use ($file) {
            $binImport = $this->createImportRecord($file);
            $this->processCsvData($file, $binImport);
            $this->startProcessing($binImport);
            
            return $binImport;
        });
    }

    public function updateProgress(BinImport $import): void
    {
        $stats = $this->binLookupRepository->getStatsByImport($import);

        $this->binImportRepository->update($import, [
            'processed_bins' => $stats->completed,
            'failed_bins' => $stats->failed,
        ]);

        if ($this->isImportComplete($stats)) {
            $this->completeImport($import);
        }
    }

    private function createImportRecord(UploadedFile $file): BinImport
    {
        return $this->binImportRepository->create([
            'filename' => $file->getClientOriginalName(),
            'status' => ImportStatus::PENDING,
            'file_size' => $file->getSize(),
            'created_at' => now(),
        ]);
    }

    private function processCsvData(UploadedFile $file, BinImport $import): void
    {
        $rows = $this->parseCsvFile($file);
        $binNumbers = $this->extractAndValidateBins($rows);

        $this->binImportRepository->update($import, ['total_bins' => count($binNumbers)]);
        $this->createLookupRecords($import, $binNumbers);
    }

    private function parseCsvFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($header)) {
                $rows[] = array_combine($header, $data);
            }
        }

        fclose($handle);
        return $rows;
    }

    private function extractAndValidateBins(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => $this->extractBinFromRow($row))
            ->filter(fn ($bin) => $bin !== null && $this->isValidBin($bin))
            ->map(fn ($bin) => trim($bin))
            ->unique()
            ->values()
            ->toArray();
    }

    private function extractBinFromRow(array $row): ?string
    {
        $possibleColumns = ['bin', 'BIN', 'bin_number', 'bin_code'];
        
        foreach ($possibleColumns as $column) {
            if (isset($row[$column]) && !empty(trim($row[$column]))) {
                return trim($row[$column]);
            }
        }
        
        return null;
    }

    private function isValidBin(?string $bin): bool
    {
        return $bin !== null &&
               strlen($bin) >= 6 &&
               strlen($bin) <= 8 &&
               ctype_digit($bin);
    }

    private function createLookupRecords(BinImport $import, array $binNumbers): void
    {
        $lookupRecords = collect($binNumbers)->map(fn ($bin) => [
            'bin_import_id' => $import->id,
            'bin_number' => $bin,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        $this->binLookupRepository->batchInsert($lookupRecords);
    }

    private function startProcessing(BinImport $import): void
    {
        $this->binImportRepository->update($import, [
            'status' => ImportStatus::PROCESSING,
            'started_at' => now(),
        ]);

        foreach ($this->binLookupRepository->findByImportId($import->id, 100) as $lookup) {
            ProcessBinLookupJob::dispatch($lookup->id);
        }

        Log::info('BIN import processing started', [
            'import_id' => $import->id,
            'total_bins' => $import->total_bins,
            'filename' => $import->filename,
        ]);
    }

    private function isImportComplete(object $stats): bool
    {
        return ($stats->completed + $stats->failed) >= $stats->total;
    }

    private function completeImport(BinImport $import): void
    {
        $this->binImportRepository->update($import, [
            'status' => ImportStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        Log::info('BIN import completed', [
            'import_id' => $import->id,
            'success_rate' => $import->success_rate,
            'total_processed' => $import->processed_bins + $import->failed_bins,
        ]);
    }
}