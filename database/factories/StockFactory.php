<?php

namespace Database\Factories;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 50);
        $minStock = 5;
        $status = $quantity === 0 ? 'out_of_stock' : ($quantity <= $minStock ? 'low_stock' : 'available');

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('SKU-###-???')),
            'description' => fake()->paragraph(),
            'quantity' => $quantity,
            'unit' => 'pcs',
            'price' => fake()->randomFloat(2, 1000, 1000000),
            'min_stock' => $minStock,
            'status' => $status,
        ];
    }

    /**
     * Indicate that the stock is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 20,
            'min_stock' => 5,
            'status' => 'available',
        ]);
    }

    /**
     * Indicate that the stock is low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 3,
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);
    }

    /**
     * Indicate that the stock is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'min_stock' => 5,
            'status' => 'out_of_stock',
        ]);
    }
}
