<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPost;
use App\Models\Location;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobPostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $jobs = JobPost::with(['user', 'location'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $appliedJobs = Application::where('user_id', $user->id)
            ->pluck('job_post_id')
            ->toArray();

        return response()->json([
            'jobs' => $jobs,
            'appliedJobs' => $appliedJobs,
        ]);
    }

    public function indexEmployer(Request $request)
    {
        $user = $request->user();
        
        $jobs = JobPost::with([
            'location',
            'applications' => function ($q) {
                $q->where('status', 'accepted')->orWhere('status', 'active')->orWhere('status', 'completed')->with('user');
            }
        ])
        ->withCount('applications')
        ->where('user_id', $user->id)
        ->get();

        return response()->json($jobs);
    }


    public function indexPublic()
    {
        $jobs = JobPost::with(['user', 'location'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'title'       => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'job_type'    => 'required|string|in:full-time,part-time,contract,freelance',
    //         'salary'      => 'required|numeric|min:1',
    //         'location_id' => 'required|string', // temporarily accept string like "Barangay, Municipality"
    //     ]);

    //     // Parse location string: "Barangay, Municipality"
    //     $locationParts = explode(',', $request->location_id);

    //     if (count($locationParts) < 2) {
    //         return response()->json([
    //             'message' => 'Invalid location format. Must be "Barangay, Municipality".'
    //         ], 422);
    //     }

    //     $barangay = trim($locationParts[0]);
    //     $municipality = trim($locationParts[1]);

    //     // Find location by barangay + municipality
    //     $location = Location::where('barangay', $barangay)
    //         ->where('municipality', $municipality)
    //         ->first();

    //     if (!$location) {
    //         return response()->json([
    //             'message' => "Location not found: {$barangay}, {$municipality}"
    //         ], 404);
    //     }

    //     // Create job post
    //     $job = JobPost::create([
    //         'title'       => $request->title,
    //         'description' => $request->description,
    //         'job_type'    => $request->job_type,
    //         'salary'      => $request->salary,
    //         'location_id' => $location->id,
    //         'user_id'     => $request->user()->id, // employer id
    //         'status'      => 'open', // default
    //     ]);

    //     return response()->json([
    //         'message' => 'Job post created successfully',
    //         'job'     => $job->load('location'),
    //     ], 201);
    // }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'job_type'    => 'required|string|in:full-time,part-time,contract,freelance',
            'salary'      => 'required|numeric|min:1',
            'location_id' => 'required|string|exists:locations,id',
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
        ]);

        $job = JobPost::create([
            'title'       => $request->title,
            'description' => $request->description,
            'job_type'    => $request->job_type,
            'salary'      => $request->salary,
            'location_id' => $request->location_id,
            'lat'         => $request->lat,
            'lng'         => $request->lng,
            'user_id'     => $request->user()->id, // employer
            'status'      => 'open',
        ]);

        return response()->json([
            'message' => 'Job post created successfully',
            'job'     => $job->load('location'),
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:open,paused,filled,closed',
        ]);

        $job = JobPost::findOrFail($id);

        // Ensure employer owns the job
        if ($job->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($job, $validated) {
            $job->status = $validated['status'];
            $job->save();

            if ($validated['status'] === 'filled') {
                $hiredApplicants = Application::where('job_post_id', $job->id)
                    ->where('status', 'accepted')
                    ->get();
                
                    
                    foreach ($hiredApplicants as $app) {
                        $app->update(['status' => 'active']);

                        $hiredApplicantsPendingApplications = Application::where('job_post_id', '!=', $job->id)
                            ->where('status', 'pending')
                            ->where('user_id', $app->user_id)
                            ->get();
                        
                        foreach($hiredApplicantsPendingApplications as $pending){
                            $pending->delete();
                        }

                        NotificationService::storeNotification(
                        $app->user_id,
                        'application',
                        "🎉 Congratulations! You have been hired for the job '{$job->title}'."
                    );
                }

                $rejectedApplicants = Application::where('job_post_id', $job->id)
                    ->whereIn('status', ['pending', 'withdrawn'])
                    ->get();

                foreach ($rejectedApplicants as $app) {
                    $app->update(['status' => 'rejected']);
                    NotificationService::storeNotification(
                        $app->user_id,
                        'application',
                        "Unfortunately, your application for '{$job->title}' was not selected."
                    );
                }
            } 
            else if ($validated['status'] === 'closed') {
                $completedApplicants = Application::where('job_post_id', $job->id)
                    ->where('status', 'active')
                    ->get();

                foreach ($completedApplicants as $app) {
                    $app->update(['status' => 'completed']);
                    NotificationService::storeNotification(
                        $app->user_id,
                        'application',
                        "Your job '{$job->title}' has been marked as completed. Thank you for your work!"
                    );
                }
            }
        });

        return response()->json([
            'message' => 'Job status updated successfully',
            'job' => $job->load('applications'),
        ]);
    }

    public function show(JobPost $jobPost)
    {
        return response()->json($jobPost->load('user','location','applications'));
    }

    // public function update(Request $request, JobPost $jobPost)
    // {
    //     if ($request->user()->id !== $jobPost->user_id && $request->user()->role !== 'admin') {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     $data = $request->validate([
    //         'title' => 'sometimes|string|max:255',
    //         'description' => 'nullable|string',
    //         'job_type' => 'nullable|string|max:50',
    //         'salary' => 'nullable|numeric',
    //         'status' => 'nullable|in:open,closed,filled',
    //     ]);

    //     $jobPost->update($data);

    //     return response()->json($jobPost);
    // }

    public function update(Request $request, JobPost $jobPost)
    {
        if ($request->user()->id !== $jobPost->user_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'job_type'    => 'nullable|string|in:full-time,part-time,contract,freelance',
            'salary'      => 'nullable|numeric|min:1',
            'status'      => 'nullable|in:open,paused,filled,closed',
            'location_id' => 'sometimes|string|exists:locations,id',
            'lat'         => 'sometimes|numeric|between:-90,90',
            'lng'         => 'sometimes|numeric|between:-180,180',
        ]);

        $jobPost->update($data);

        return response()->json([
            'message' => 'Job post updated successfully',
            'job'     => $jobPost->load('location'),
        ]);
    }

    public function destroy(Request $request, JobPost $jobPost)
    {
        if ($request->user()->id !== $jobPost->user_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobPost->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
