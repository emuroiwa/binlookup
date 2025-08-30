<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bin_lookup_id')->unique()->constrained()->onDelete('cascade');
            $table->string('bin_number', 8);
            $table->string('bank_name')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->json('api_response');
            $table->timestamps();

            $table->index('bin_number');
            $table->index('country_code');
            $table->index('card_brand');
            $table->index('card_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_data');
    }
};
