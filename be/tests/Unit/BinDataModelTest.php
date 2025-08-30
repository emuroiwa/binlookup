<?php

use App\Models\BinData;
use App\Models\BinLookup;

describe("BinData Model", function () {
    beforeEach(function () {
        $this->binData = BinData::factory()->make([
            "id" => "test-data-uuid",
            "bin_lookup_id" => "test-lookup-uuid",
            "bin_number" => "123456",
            "bank_name" => "Test Bank",
            "card_type" => "debit",
            "card_brand" => "visa",
            "country_code" => "US",
            "country_name" => "United States",
            "api_response" => ["key" => "value"]
        ]);
    });

    describe("relationships", function () {
        it("belongs to bin lookup", function () {
            $relationship = $this->binData->binLookup();
            
            expect($relationship)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
            expect($relationship->getRelated())->toBeInstanceOf(BinLookup::class);
        });

        it("has bin import through bin lookup", function () {
            $relationship = $this->binData->binImport();
            
            expect($relationship)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasOneThrough::class);
        });
    });

    describe("fillable attributes", function () {
        it("includes all expected fillable fields", function () {
            $expectedFillable = [
                "bin_lookup_id",
                "bin_number",
                "bank_name",
                "card_type",
                "card_brand",
                "country_code",
                "country_name",
                "website",
                "phone",
                "api_response"
            ];

            expect($this->binData->getFillable())->toBe($expectedFillable);
        });
    });

    describe("casts", function () {
        it("casts api_response to array", function () {
            $casts = $this->binData->getCasts();
            
            expect($casts["api_response"])->toBe("array");
        });
    });

    describe("uses traits", function () {
        it("uses HasFactory and HasUuids traits", function () {
            $traits = class_uses(BinData::class);
            
            expect($traits)->toContain("Illuminate\Database\Eloquent\Factories\HasFactory");
            expect($traits)->toContain("Illuminate\Database\Eloquent\Concerns\HasUuids");
        });
    });
});
