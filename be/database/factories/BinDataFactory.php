<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BinLookup;
use Illuminate\Database\Eloquent\Factories\Factory;

class BinDataFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $binNumber = $this->faker->numerify('######');
        $countryData = $this->faker->randomElement([
            ['US', 'United States'],
            ['CA', 'Canada'],
            ['GB', 'United Kingdom'],
            ['DE', 'Germany'],
            ['FR', 'France'],
            ['AU', 'Australia'],
            ['JP', 'Japan'],
        ]);

        $apiResponse = [
            'scheme' => $this->faker->randomElement(['visa', 'mastercard', 'amex', 'discover']),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'brand' => $this->faker->randomElement(['Classic', 'Gold', 'Platinum']),
            'bank' => [
                'name' => $this->faker->company().' Bank',
                'url' => $this->faker->url(),
                'phone' => $this->faker->phoneNumber(),
            ],
            'country' => [
                'alpha2' => $countryData[0],
                'name' => $countryData[1],
            ],
        ];

        return [
            'bin_lookup_id' => BinLookup::factory()->completed(),
            'bin_number' => $binNumber,
            'bank_name' => $apiResponse['bank']['name'],
            'card_type' => $apiResponse['type'],
            'card_brand' => $apiResponse['scheme'],
            'country_code' => $apiResponse['country']['alpha2'],
            'country_name' => $apiResponse['country']['name'],
            'website' => $apiResponse['bank']['url'],
            'phone' => $apiResponse['bank']['phone'],
            'api_response' => $apiResponse,
        ];
    }
}
