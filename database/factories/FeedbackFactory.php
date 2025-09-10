<?php

namespace Database\Factories;

use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = User::factory()->state(['role' => 'worker']);
        $to = User::factory()->state(['role' => 'employer']);

        return [
            'from_user_id' => $from,
            'to_user_id' => $to,
            'rating' => $this->faker->numberBetween(1,5),
            'comment' => $this->faker->sentence(),
            'job_post_id' => JobPost::factory(),
        ];
    }
}
