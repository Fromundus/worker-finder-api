<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = $this->faker->randomElement(['worker','employer']);

        $locations = Location::all();

        $location = $locations->random();

        return [
            'name' => $this->faker->name(),
            'contact_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // default password
            'role' => $role,
            'status' => 'active',
            'skills' => $role === 'worker' ? implode(', ', $this->faker->words(3)) : null,
            'experience' => $role === 'worker' ? $this->faker->sentence(6) : null,
            'average_rating' => 0,
            'business_name' => $role === 'employer' ? $this->faker->company() : null,
            'remember_token' => Str::random(10),
            'location_id' => $location->id,
            'lat' => $location->lat,
            'lng' => $location->lng,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
