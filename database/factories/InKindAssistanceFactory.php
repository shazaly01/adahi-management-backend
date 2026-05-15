<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

class InKindAssistanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'request_date' => $this->faker->date(),
            'reasons' => $this->faker->randomElement(['دخل محدود', 'تضرر مسكن', 'حالة صحية طارئة']),
        ];
    }
}
