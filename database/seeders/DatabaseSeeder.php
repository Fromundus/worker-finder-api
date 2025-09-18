<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Feedback;
use App\Models\JobPost;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed barangays from JSON
        $this->call(BarangaySeeder::class);

        // 2. Admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 3. Employers & Workers
        $workers = User::factory(50)->create(['role' => 'worker']);
        $employers = User::factory(50)->create(['role' => 'employer']);

        // 4. Job posts linked to real barangays
        $locations = Location::all();

        // foreach($employers as $employer){
        //     $location = $locations->random();

        //     JobPost::factory(20)->create([
        //         'user_id' => $employer->id,
        //         'location_id' => $location->id,
        //         'lat' => $location->lat,
        //         'lng' => $location->lng,
        //     ]);
        // }
        
        foreach ($employers as $employer) {
            JobPost::factory(5)->create([
                'user_id' => $employer->id,
            ])->each(function ($job) use ($locations) {
                $location = $locations->random();
                $job->update([
                    'location_id' => $location->id,
                    'lat' => $location->lat,
                    'lng' => $location->lng,
                ]);
            });
        }

        // $jobPosts = JobPost::factory(20)->create([
        //     'user_id' => $employers->random()->id,
        //     'location_id' => $locations->random()->id,
        // ]);

        // 5. Applications
        // foreach ($jobPosts as $job) {
        //     // pick unique workers for this job
        //     $applicants = $workers->random(rand(1,3));

        //     foreach ($applicants as $worker) {
        //         Application::factory()->create([
        //             'job_post_id' => $job->id,
        //             'user_id' => $worker->id,
        //         ]);
        //     }
        // }

        // 6. Feedback
        // foreach ($jobPosts as $job) {
        //     Feedback::factory()->create([
        //         'from_user_id' => $workers->random()->id,
        //         'to_user_id' => $job->user_id,
        //         'job_post_id' => $job->id,
        //     ]);
        // }

        // 7. Notifications
        // Notification::factory(20)->create([
        //     'user_id' => $workers->random()->id,
        // ]);
    }
}
