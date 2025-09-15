<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Booking;
use App\Models\JobPost;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'job_post_id' => 'required|exists:job_posts,id',
            'message'     => 'nullable|string|max:2000',
        ]);

        $exists = Application::where('job_post_id', $data['job_post_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        $alreadyBooked = Booking::where("worker_id", $request->user()->id)->where("status", 'active')->exists();

        if ($exists) {
            return response()->json(['message' => 'Already applied'], 422);
        }

        if ($alreadyBooked) {
            return response()->json(['message' => 'Application is not allowed when you have an active booking.'], 422);
        }

        $application = Application::create([
            'job_post_id' => $data['job_post_id'],
            'user_id'     => $request->user()->id,
            'message'     => $data['message'] ?? null,
        ]);

        // 🔔 Notify the employer (job post owner)
        $jobPost = JobPost::findOrFail($data['job_post_id']);
        NotificationService::storeNotification(
            $jobPost->user_id, // employer user_id
            'application',
            "{$request->user()->name} applied to your job post: {$jobPost->title}"
        );

        return response()->json($application->load('user', 'jobPost'), 201);
    }

    public function myApplications(Request $request)
    {
        $user = $request->user();

        $applications = Application::with([
            'jobPost.user',
            'jobPost.location',
        ])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($applications);
    }

    public function employerApplications(Request $request)
    {
        $user = $request->user();

        $applications = Application::with(['user', 'jobPost.location'])
            ->whereHas('jobPost', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($applications);
    }

    // public function updateStatus(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         'status' => 'required|string|in:pending,accepted,rejected,withdrawn,completed',
    //     ]);

    //     $application = Application::findOrFail($id);

    //     // Ensure employer owns the job
    //     if ($application->jobPost->user_id !== $request->user()->id) {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     $application->status = $validated['status'];
    //     $application->save();

    //     return response()->json([
    //         'message' => 'Application updated successfully',
    //         'application' => $application->load('user', 'jobPost'),
    //     ]);
    // }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,rejected,withdrawn,completed',
        ]);

        $application = Application::findOrFail($id);

        // Ensure employer owns the job
        if ($application->jobPost->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $application->status = $validated['status'];
        $application->save();

        // 🔔 Notify the worker (applicant)
        $workerId = $application->user_id;
        $jobTitle = $application->jobPost->title;

        $messages = [
            'accepted'  => "Your application for {$jobTitle} was accepted.",
            'rejected'  => "Your application for {$jobTitle} was rejected.",
            'withdrawn' => "Your application for {$jobTitle} was withdrawn by the employer.",
            'completed' => "Your application for {$jobTitle} was marked as completed.",
            'pending'   => "Your application for {$jobTitle} is pending review.",
        ];

        if (isset($messages[$validated['status']])) {
            NotificationService::storeNotification(
                $workerId,
                'application',
                $messages[$validated['status']]
            );
        }

        return response()->json([
            'message' => 'Application updated successfully',
            'application' => $application->load('user', 'jobPost'),
        ]);
    }

}
