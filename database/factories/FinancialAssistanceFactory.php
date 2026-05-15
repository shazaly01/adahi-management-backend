<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialAssistanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'type' => $this->faker->randomElement(['social', 'medical']),
            'request_date' => $this->faker->date(),
            'approved_amount' => $this->faker->randomFloat(2, 100, 2000),
        ];
    }
}
