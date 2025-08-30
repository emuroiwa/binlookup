<?php

use App\Services\Import\BinImportValidationService;
use Illuminate\Http\UploadedFile;

describe("BinImportValidationService", function () {
    beforeEach(function () {
        $this->service = new BinImportValidationService();
    });

    describe("validateFile", function () {
        it("accepts valid CSV files", function () {
            $file = UploadedFile::fake()->createWithContent(
                "valid.csv", 
                "bin\n123456\n789012"
            );

            $errors = $this->service->validateFile($file);

            expect($errors)->toBeEmpty();
        });

        it("rejects files with invalid extensions", function () {
            $file = UploadedFile::fake()->create("test.pdf", 100, "application/pdf");

            $errors = $this->service->validateFile($file);

            expect($errors)->toContain("File must be CSV or TXT format");
        });

        it("rejects files exceeding size limit", function () {
            $file = UploadedFile::fake()->create("large.csv", 11 * 1024 * 1024);

            $errors = $this->service->validateFile($file);

            expect($errors)->toContain("File size cannot exceed 10MB");
        });

        it("rejects empty files", function () {
            $file = UploadedFile::fake()->create("empty.csv", 0);

            $errors = $this->service->validateFile($file);

            expect($errors)->toContain("File cannot be empty");
        });

        it("rejects CSV without bin column", function () {
            $file = UploadedFile::fake()->createWithContent(
                "no-bin.csv",
                "name,value\nTest,123"
            );

            $errors = $this->service->validateFile($file);

            expect($errors)->toContain("CSV must contain a \"bin\" column");
        });

        it("accepts CSV with BIN column in uppercase", function () {
            $file = UploadedFile::fake()->createWithContent(
                "uppercase.csv",
                "BIN,name\n123456,Test"
            );

            $errors = $this->service->validateFile($file);

            expect($errors)->toBeEmpty();
        });
    });

    describe("isValidFile", function () {
        it("returns true for valid files", function () {
            $file = UploadedFile::fake()->createWithContent(
                "valid.csv",
                "bin\n123456"
            );

            $result = $this->service->isValidFile($file);

            expect($result)->toBeTrue();
        });

        it("returns false for invalid files", function () {
            $file = UploadedFile::fake()->create("invalid.pdf");

            $result = $this->service->isValidFile($file);

            expect($result)->toBeFalse();
        });
    });
});
