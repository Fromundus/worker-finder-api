<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Feedback;
use App\Models\JobPost;
use App\Models\Notification;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function workerOverview(Request $request)
    {
        $user = $request->user();

        $pendingApplications = Application::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $acceptedJobs = Application::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->count();

        $averageRating = round(
            Feedback::where('to_user_id', $user->id)->avg('rating') ?? 0,
            2
        );

        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $recentApplications = Application::with(['jobPost.user', 'jobPost.location'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        $barangay = null;
        $municipality = null;

        if ($user->address) {
            $parts = explode(',', $user->address); // ["Marinawa", " Bato"]
            $barangay = trim($parts[0] ?? null);
            $municipality = trim($parts[1] ?? null);
        }

        $nearbyJobs = JobPost::with(['user', 'location'])
        ->where('status', 'open')
        ->whereHas('location', function ($q) use ($barangay, $municipality) {
            if ($municipality) {
                $q->where('municipality', 'like', "%{$municipality}%");
            }
            if ($barangay) {
                $q->orWhere('barangay', 'like', "%{$barangay}%");
            }
        })
        ->latest()
        ->take(3)
        ->get();

        return response()->json([
            'pendingApplications' => $pendingApplications,
            'acceptedJobs' => $acceptedJobs,
            'averageRating' => $averageRating,
            'unreadNotifications' => $unreadNotifications,
            'recentApplications' => $recentApplications,
            'nearbyJobs' => $nearbyJobs,
        ]);
    }

    public function employerOverview(Request $request)
    {
        $user = $request->user();

        $activeJobPosts = $user->jobPosts()->where('status', 'open')->count();

        $totalApplications = $user->jobPosts()
            ->withCount('applications')
            ->get()
            ->sum('applications_count');

        $pendingReviews = $user->jobPosts()
            ->with(['applications' => fn($q) => $q->where('status', 'completed')])
            ->get()
            ->pluck('applications')
            ->flatten()
            ->count();

        $averageRating = $user->feedbackReceived()->avg('rating') ?? 0;

        // Recent Applications
        $recentApplications = $user->jobPosts()
            ->with(['applications.user'])
            ->get()
            ->pluck('applications')
            ->flatten()
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        // Recent Active Job Posts
        $recentJobPosts = $user->jobPosts()
            ->where('status', 'open')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'active_job_posts'   => $activeJobPosts,
            'total_applications' => $totalApplications,
            'pending_reviews'    => $pendingReviews,
            'average_rating'     => round($averageRating, 1),
            'recent_applications'=> $recentApplications,
            'recent_job_posts'   => $recentJobPosts,
        ]);
    }


}
