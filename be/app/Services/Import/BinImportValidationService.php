<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;

class BinImportValidationService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_EXTENSIONS = ['csv', 'txt'];
    private const REQUIRED_COLUMNS = ['bin'];

    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        if (!$this->hasValidExtension($file)) {
            $errors[] = 'File must be CSV or TXT format';
        }

        if (!$this->hasValidSize($file)) {
            $errors[] = 'File size cannot exceed 10MB';
        }

        if ($this->isEmpty($file)) {
            $errors[] = 'File cannot be empty';
        }

        if (empty($errors) && !$this->hasValidStructure($file)) {
            $errors[] = 'CSV must contain a "bin" column';
        }

        return $errors;
    }

    public function isValidFile(UploadedFile $file): bool
    {
        return empty($this->validateFile($file));
    }

    private function hasValidExtension(UploadedFile $file): bool
    {
        return in_array($file->getClientOriginalExtension(), self::ALLOWED_EXTENSIONS);
    }

    private function hasValidSize(UploadedFile $file): bool
    {
        return $file->getSize() <= self::MAX_FILE_SIZE;
    }

    private function isEmpty(UploadedFile $file): bool
    {
        return $file->getSize() === 0;
    }

    private function hasValidStructure(UploadedFile $file): bool
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return false;
        }

        $header = fgetcsv($handle);
        fclose($handle);

        if (!$header) {
            return false;
        }

        $normalizedHeader = array_map('strtolower', array_map('trim', $header));
        
        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!in_array($required, $normalizedHeader)) {
                return false;
            }
        }

        return true;
    }
}