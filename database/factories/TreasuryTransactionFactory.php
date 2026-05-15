<?php

namespace Database\Factories;

use App\Models\Treasury;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreasuryTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'treasury_id' => Treasury::factory(),
            'user_id' => User::first()?->id ?? User::factory(),
            // توليد رقم معاملة مكون من 10 أرقام (يتناسب مع DECIMAL 18,0)
            'TransactionNo' => $this->faker->unique()->numerify('##########'),
            'transaction_type' => $this->faker->randomElement(['deposit', 'withdrawal']),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'transaction_date' => $this->faker->date(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
