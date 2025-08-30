<?php

use App\Models\BinLookup;
use App\Models\BinImport;
use App\Models\BinData;
use App\Enums\LookupStatus;

describe("BinLookup Model", function () {
    beforeEach(function () {
        $this->binLookup = BinLookup::factory()->make([
            "id" => "test-lookup-uuid",
            "bin_import_id" => "test-import-uuid",
            "bin_number" => "123456",
            "status" => LookupStatus::PENDING,
            "attempts" => 0
        ]);
    });

    describe("relationships", function () {
        it("belongs to bin import", function () {
            $relationship = $this->binLookup->binImport();
            
            expect($relationship)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
            expect($relationship->getRelated())->toBeInstanceOf(BinImport::class);
        });

        it("has one bin data", function () {
            $relationship = $this->binLookup->binData();
            
            expect($relationship)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasOne::class);
            expect($relationship->getRelated())->toBeInstanceOf(BinData::class);
        });
    });

    describe("fillable attributes", function () {
        it("includes all expected fillable fields", function () {
            $expectedFillable = [
                "bin_import_id",
                "bin_number",
                "status",
                "attempts",
                "last_attempted_at",
                "error_message"
            ];

            expect($this->binLookup->getFillable())->toBe($expectedFillable);
        });
    });

    describe("casts", function () {
        it("casts attributes correctly", function () {
            $casts = $this->binLookup->getCasts();
            
            expect($casts["status"])->toBe(LookupStatus::class);
            expect($casts["last_attempted_at"])->toBe("datetime");
            expect($casts["attempts"])->toBe("integer");
        });
    });

    describe("uses traits", function () {
        it("uses HasFactory and HasUuids traits", function () {
            $traits = class_uses(BinLookup::class);
            
            expect($traits)->toContain("Illuminate\Database\Eloquent\Factories\HasFactory");
            expect($traits)->toContain("Illuminate\Database\Eloquent\Concerns\HasUuids");
        });
    });
});
