<?php

use App\Models\BinData;
use App\Models\BinLookup;
use App\Models\BinImport;

describe("BIN Data API Endpoints", function () {
    describe("GET /api/bin-data", function () {
        it("returns paginated list of BIN data", function () {
            $import = BinImport::factory()->create();
            $lookups = BinLookup::factory()
                ->count(10)
                ->create(["bin_import_id" => $import->id]);
            
            foreach ($lookups as $lookup) {
                BinData::factory()->create(["bin_lookup_id" => $lookup->id]);
            }

            $response = $this->getJson("/api/bin-data");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    "data" => [
                        "*" => [
                            "id",
                            "bin_number",
                            "bank_name",
                            "card_type",
                            "card_brand",
                            "country_code",
                            "country_name",
                            "created_at"
                        ]
                    ],
                    "links",
                    "meta"
                ]);

            expect($response->json("data"))->toHaveCount(10);
        });

        it("filters by BIN number", function () {
            $import = BinImport::factory()->create();
            $lookup1 = BinLookup::factory()->create(["bin_import_id" => $import->id]);
            $lookup2 = BinLookup::factory()->create(["bin_import_id" => $import->id]);

            BinData::factory()->create([
                "bin_lookup_id" => $lookup1->id,
                "bin_number" => "123456"
            ]);
            
            BinData::factory()->create([
                "bin_lookup_id" => $lookup2->id,
                "bin_number" => "789012"
            ]);

            $response = $this->getJson("/api/bin-data?bin=123456");

            $response->assertStatus(200);
            expect($response->json("data"))->toHaveCount(1);
            expect($response->json("data.0.bin_number"))->toBe("123456");
        });

        it("supports pagination", function () {
            $import = BinImport::factory()->create();
            $lookups = BinLookup::factory()
                ->count(20)
                ->create(["bin_import_id" => $import->id]);
            
            foreach ($lookups as $lookup) {
                BinData::factory()->create(["bin_lookup_id" => $lookup->id]);
            }

            $response = $this->getJson("/api/bin-data?per_page=5");

            $response->assertStatus(200);
            expect($response->json("data"))->toHaveCount(5);
            expect($response->json("meta.per_page"))->toBe(5);
        });
    });

    describe("GET /api/bin-data/filter-options", function () {
        it("returns available filter options", function () {
            $response = $this->getJson("/api/bin-data/filter-options");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    "brands", 
                    "types",
                    "countries"
                ]);
        });
    });
});
