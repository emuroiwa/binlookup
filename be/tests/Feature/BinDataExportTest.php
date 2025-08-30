<?php

use App\Models\BinData;
use App\Models\BinLookup;
use App\Models\BinImport;
use Illuminate\Support\Facades\Storage;

describe("BIN Data Export Feature", function () {
    beforeEach(function () {
        Storage::fake("local");
    });

    describe("GET /api/bin-data/export", function () {
        it("exports BIN data to Excel format", function () {
            $import = BinImport::factory()->create();
            $lookup = BinLookup::factory()->create(["bin_import_id" => $import->id]);
            
            BinData::factory()->create([
                "bin_lookup_id" => $lookup->id,
                "bin_number" => "123456",
                "bank_name" => "Test Bank",
                "card_type" => "debit",
                "card_brand" => "visa",
                "country_name" => "United States"
            ]);

            $response = $this->get("/api/bin-data/export");

            $response->assertStatus(200)
                ->assertHeader("content-type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            
            // Check that filename starts with expected pattern
            $contentDisposition = $response->headers->get("content-disposition");
            expect($contentDisposition)->toStartWith("attachment; filename=bin-data-export");
        });

        it("exports filtered data when filters are applied", function () {
            $import = BinImport::factory()->create();
            $lookup1 = BinLookup::factory()->create(["bin_import_id" => $import->id]);
            $lookup2 = BinLookup::factory()->create(["bin_import_id" => $import->id]);

            BinData::factory()->create([
                "bin_lookup_id" => $lookup1->id,
                "bank_name" => "Target Bank",
                "card_brand" => "visa"
            ]);
            
            BinData::factory()->create([
                "bin_lookup_id" => $lookup2->id,
                "bank_name" => "Other Bank", 
                "card_brand" => "mastercard"
            ]);

            $response = $this->get("/api/bin-data/export?brand=visa");

            $response->assertStatus(200)
                ->assertHeader("content-type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        });

        it("exports empty file when no data matches filters", function () {
            $response = $this->get("/api/bin-data/export?bin=nonexistent");

            $response->assertStatus(200)
                ->assertHeader("content-type", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        });
    });
});
