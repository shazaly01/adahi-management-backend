<?php

namespace Database\Factories;

use App\Models\InKindAssistance;
use Illuminate\Database\Eloquent\Factories\Factory;

class InKindAssistanceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'in_kind_assistance_id' => InKindAssistance::factory(),
            'description' => $this->faker->randomElement(['سلة غذائية متكاملة', 'بطانية ومستلزمات شتاء', 'حقيبة مدرسية', 'أدوية مزمنة']),
        ];
    }
}
