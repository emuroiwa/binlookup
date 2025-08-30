<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BinImportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => $this->faker->words(2, true).'.csv',
            'total_bins' => $this->faker->numberBetween(10, 1000),
            'processed_bins' => 0,
            'failed_bins' => 0,
            'status' => ImportStatus::PENDING,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state([
            'status' => ImportStatus::PROCESSING,
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $this->faker->dateTimeBetween('-2 hours', '-1 hour');

            return [
                'status' => ImportStatus::COMPLETED,
                'started_at' => $startedAt,
                'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
                'processed_bins' => $attributes['total_bins'],
            ];
        });
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ImportStatus::FAILED,
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
