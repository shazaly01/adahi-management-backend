<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BeneficiaryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name('male'),
            // توليد رقم وطني عشوائي فريد مكون من 12 رقم
            'national_id' => $this->faker->unique()->numerify('############'),
            'birth_date' => $this->faker->date('Y-m-d', '-20 years'),
            'gender' => $this->faker->randomElement(['ذكر', 'أنثى']),
            'nationality' => 'ليبي',
            'marital_status' => $this->faker->randomElement(['أعزب', 'متزوج', 'مطلق', 'أرمل']),
            'family_members_count' => $this->faker->numberBetween(1, 10),
            'residence' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
