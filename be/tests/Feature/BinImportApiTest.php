<?php

use App\Models\BinImport;
use App\Models\BinLookup;
use App\Enums\ImportStatus;
use App\Enums\LookupStatus;

describe("BIN Import API Endpoints", function () {
    describe("GET /api/bin-imports", function () {
        it("returns paginated list of imports", function () {
            BinImport::factory()
                ->count(5)
                ->create();

            $response = $this->getJson("/api/bin-imports");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    "data" => [
                        "*" => [
                            "id",
                            "filename",
                            "total_bins",
                            "processed_bins",
                            "failed_bins",
                            "status",
                            "progress_percentage",
                            "success_rate",
                            "created_at"
                        ]
                    ],
                    "links",
                    "meta"
                ]);

            expect($response->json("data"))->toHaveCount(5);
        });

        it("filters imports by status", function () {
            BinImport::factory()->create(["status" => ImportStatus::COMPLETED]);
            BinImport::factory()->create(["status" => ImportStatus::PENDING]);
            BinImport::factory()->create(["status" => ImportStatus::PROCESSING]);

            $response = $this->getJson("/api/bin-imports?status=completed");

            $response->assertStatus(200);
            expect($response->json("data"))->toHaveCount(1);
            expect($response->json("data.0.status.value"))->toBe("completed");
        });

        it("searches imports by filename", function () {
            BinImport::factory()->create(["filename" => "test-data.csv"]);
            BinImport::factory()->create(["filename" => "production-bins.csv"]);

            $response = $this->getJson("/api/bin-imports?search=test");

            $response->assertStatus(200);
            expect($response->json("data"))->toHaveCount(1);
            expect($response->json("data.0.filename"))->toBe("test-data.csv");
        });

        it("supports pagination", function () {
            BinImport::factory()->count(20)->create();

            $response = $this->getJson("/api/bin-imports?per_page=5");

            $response->assertStatus(200);
            expect($response->json("data"))->toHaveCount(5);
            expect($response->json("meta.per_page"))->toBe(5);
            expect($response->json("meta.total"))->toBe(20);
        });
    });

    describe("GET /api/bin-imports/{import}", function () {
        it("returns detailed import information", function () {
            $import = BinImport::factory()->create([
                "filename" => "test.csv",
                "total_bins" => 10,
                "processed_bins" => 8,
                "failed_bins" => 1
            ]);

            BinLookup::factory()
                ->count(8)
                ->create([
                    "bin_import_id" => $import->id,
                    "status" => LookupStatus::COMPLETED
                ]);

            BinLookup::factory()
                ->count(1)
                ->create([
                    "bin_import_id" => $import->id,
                    "status" => LookupStatus::FAILED
                ]);

            BinLookup::factory()
                ->count(1)
                ->create([
                    "bin_import_id" => $import->id,
                    "status" => LookupStatus::PENDING
                ]);

            $response = $this->getJson("/api/bin-imports/{$import->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    "data" => [
                        "id",
                        "filename",
                        "total_bins",
                        "status",
                        "progress_percentage"
                    ],
                    "statistics" => [
                        "total_lookups",
                        "status_breakdown" => [
                            "completed",
                            "failed",
                            "pending"
                        ],
                        "recent_errors"
                    ]
                ]);

            expect($response->json("data.filename"))->toBe("test.csv");
            expect($response->json("statistics.total_lookups"))->toBe(10);
        });

        it("returns 404 for non-existent import", function () {
            $response = $this->getJson("/api/bin-imports/non-existent-uuid");

            $response->assertStatus(404);
        });
    });

    describe("DELETE /api/bin-imports/{import}", function () {
        it("deletes import and all related records", function () {
            $import = BinImport::factory()->create();
            
            BinLookup::factory()
                ->count(3)
                ->create(["bin_import_id" => $import->id]);

            $response = $this->deleteJson("/api/bin-imports/{$import->id}");

            $response->assertStatus(200)
                ->assertJson([
                    "message" => "Import deleted successfully"
                ]);

            $this->assertDatabaseMissing("bin_imports", ["id" => $import->id]);
            $this->assertDatabaseMissing("bin_lookups", ["bin_import_id" => $import->id]);
        });

        it("returns 404 for non-existent import", function () {
            $response = $this->deleteJson("/api/bin-imports/non-existent-uuid");

            $response->assertStatus(404);
        });
    });
});
