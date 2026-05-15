<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TreasuryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['الخزينة الرئيسية', 'خزينة الصدقات', 'خزينة الزكاة', 'خزينة الطوارئ']),
            'balance' => $this->faker->randomFloat(2, 5000, 50000), // رصيد ابتدائي عشوائي
        ];
    }
}
