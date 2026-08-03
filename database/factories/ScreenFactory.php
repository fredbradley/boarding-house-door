<?php

namespace Database\Factories;

use App\Models\Screen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Screen>
 */
class ScreenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->name(),
            'ics_url' => null,
            'default_heading' => 'In School',
            'default_subheading' => null,
            'notification_email' => fake()->safeEmail(),
        ];
    }
}
