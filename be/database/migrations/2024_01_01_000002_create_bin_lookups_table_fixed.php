<?php

declare(strict_types=1);

use App\Enums\LookupStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_lookups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bin_import_id')->constrained()->cascadeOnDelete();
            $table->string('bin_number', 8);
            $table->enum('status', array_column(LookupStatus::cases(), 'value'))
                  ->default(LookupStatus::PENDING->value);
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['bin_import_id', 'bin_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_lookups');
    }
};