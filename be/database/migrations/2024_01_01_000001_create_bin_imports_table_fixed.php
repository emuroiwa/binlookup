<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename');
            $table->integer('total_bins')->default(0);
            $table->integer('processed_bins')->default(0);
            $table->integer('failed_bins')->default(0);
            $table->enum('status', array_column(ImportStatus::cases(), 'value'))
                  ->default(ImportStatus::PENDING->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_imports');
    }
};