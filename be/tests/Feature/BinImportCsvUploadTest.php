<?php

use App\Models\BinImport;
use App\Enums\ImportStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

describe("BIN Import CSV Upload Feature", function () {
    beforeEach(function () {
        Storage::fake("local");
        Queue::fake();
    });

    describe("POST /api/bin-imports", function () {
        it("successfully processes valid CSV upload", function () {
            $csvContent = "bin\n123456\n789012\n456789";
            $file = UploadedFile::fake()->createWithContent("valid.csv", $csvContent);

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    "message",
                    "data" => [
                        "id",
                        "filename", 
                        "total_bins",
                        "status",
                        "created_at"
                    ]
                ])
                ->assertJson([
                    "message" => "CSV import started successfully"
                ]);

            expect(BinImport::count())->toBe(1);
            
            $import = BinImport::first();
            expect($import->filename)->toBe("valid.csv");
            expect($import->total_bins)->toBe(3);
            expect($import->status)->toBe(ImportStatus::PROCESSING);
        });

        it("rejects CSV without required BIN column", function () {
            $csvContent = "name,value\nTest Bank,123\nAnother Bank,456";
            $file = UploadedFile::fake()->createWithContent("invalid.csv", $csvContent);

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure([
                    "message",
                    "error"
                ])
                ->assertJson([
                    "message" => "Invalid CSV format"
                ]);

            expect(BinImport::count())->toBe(0);
        });

        it("rejects files with invalid extensions", function () {
            $file = UploadedFile::fake()->create("test.pdf", 100, "application/pdf");

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(["file"]);

            expect(BinImport::count())->toBe(0);
        });

        it("rejects oversized files", function () {
            $file = UploadedFile::fake()->create("large.csv", 11 * 1024 * 1024);

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(["file"]);
            
            expect(BinImport::count())->toBe(0);
        });

        it("processes CSV with various BIN column formats", function () {
            $csvContent = "BIN,name\n123456,Test Bank\n789012,Another Bank";
            $file = UploadedFile::fake()->createWithContent("uppercase.csv", $csvContent);

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(201);
            expect(BinImport::count())->toBe(1);
        });

        it("filters out invalid BIN numbers", function () {
            $csvContent = "bin\n123456\ninvalid\n78901\n456789";
            $file = UploadedFile::fake()->createWithContent("mixed.csv", $csvContent);

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(201);
            
            $import = BinImport::first();
            expect($import->total_bins)->toBe(2); // Only valid BINs: 123456, 456789
        });

        it("removes duplicate BIN numbers", function () {
            $csvContent = "bin\n123456\n789012\n123456\n456789\n789012";
            $file = UploadedFile::fake()->createWithContent("duplicates.csv", $csvContent);

            $response = $this->postJson("/api/bin-imports", [
                "file" => $file
            ]);

            $response->assertStatus(201);
            
            $import = BinImport::first();
            expect($import->total_bins)->toBe(3); // Unique BINs only
        });

        it("requires file parameter", function () {
            $response = $this->postJson("/api/bin-imports", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(["file"]);
        });
    });
});
