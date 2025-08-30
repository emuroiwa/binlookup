<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LookupStatus;
use App\Models\BinImport;
use Illuminate\Database\Eloquent\Factories\Factory;

class BinLookupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bin_import_id' => BinImport::factory(),
            'bin_number' => $this->faker->numerify('######'),
            'status' => LookupStatus::PENDING,
            'attempts' => 0,
            'last_attempted_at' => null,
            'error_message' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => LookupStatus::PENDING,
            'attempts' => 0,
            'last_attempted_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => LookupStatus::PROCESSING,
            'attempts' => 1,
            'last_attempted_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => LookupStatus::COMPLETED,
            'attempts' => 1,
            'last_attempted_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => LookupStatus::FAILED,
            'attempts' => 3,
            'last_attempted_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
