<?php

use App\Models\BinImport;
use App\Models\BinLookup;
use App\Enums\ImportStatus;

describe("BinImport Model", function () {
    beforeEach(function () {
        $this->binImport = BinImport::factory()->make([
            "id" => "test-import-uuid",
            "filename" => "test.csv",
            "total_bins" => 100,
            "processed_bins" => 80,
            "failed_bins" => 10,
            "status" => ImportStatus::PROCESSING
        ]);
    });

    describe("relationships", function () {
        it("has many bin lookups", function () {
            $relationship = $this->binImport->binLookups();
            
            expect($relationship)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
            expect($relationship->getRelated())->toBeInstanceOf(BinLookup::class);
        });
    });

    describe("fillable attributes", function () {
        it("includes all expected fillable fields", function () {
            $expectedFillable = [
                "filename",
                "total_bins",
                "processed_bins", 
                "failed_bins",
                "status",
                "started_at",
                "completed_at"
            ];

            expect($this->binImport->getFillable())->toBe($expectedFillable);
        });
    });

    describe("casts", function () {
        it("casts status to ImportStatus enum", function () {
            $casts = $this->binImport->getCasts();
            
            expect($casts["status"])->toBe(ImportStatus::class);
            expect($casts["started_at"])->toBe("datetime");
            expect($casts["completed_at"])->toBe("datetime");
        });
    });

    describe("accessors", function () {
        it("calculates progress percentage correctly", function () {
            expect($this->binImport->progress_percentage)->toBe(90);
        });

        it("returns zero progress for no total bins", function () {
            $import = BinImport::factory()->make([
                "total_bins" => 0,
                "processed_bins" => 0,
                "failed_bins" => 0
            ]);

            expect($import->progress_percentage)->toBe(0);
        });

        it("calculates success rate correctly", function () {
            // 80 processed out of 90 total completed (80 + 10 failed)
            expect($this->binImport->success_rate)->toBe(88.89);
        });

        it("returns zero success rate for no completed bins", function () {
            $import = BinImport::factory()->make([
                "processed_bins" => 0,
                "failed_bins" => 0
            ]);

            expect($import->success_rate)->toBe(0.0);
        });
    });

    describe("uses traits", function () {
        it("uses HasFactory trait", function () {
            $traits = class_uses(BinImport::class);
            
            expect($traits)->toContain("Illuminate\Database\Eloquent\Factories\HasFactory");
        });

        it("uses HasUuids trait", function () {
            $traits = class_uses(BinImport::class);
            
            expect($traits)->toContain("Illuminate\Database\Eloquent\Concerns\HasUuids");
        });
    });
});
