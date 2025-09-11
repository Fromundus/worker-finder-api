<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPost;
use App\Models\Location;
use Illuminate\Http\Request;

class JobPostController extends Controller
{
    // public function index()
    // {
    //     return response()->json(JobPost::with('user','location')->latest()->paginate(15));
    // }

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

    public function indexPublic()
    {
        $jobs = JobPost::with(['user', 'location'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'job_type' => 'nullable|string|max:50',
            'salary' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'barangay' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
        ]);

        $locationId = null;
        if (isset($data['lat']) && isset($data['lng'])) {
            $location = Location::create([
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'barangay' => $data['barangay'] ?? null,
                'municipality' => $data['municipality'] ?? null,
            ]);
            $locationId = $location->id;
        }

        $job = JobPost::create([
            'user_id' => $request->user()->id,
            'location_id' => $locationId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'job_type' => $data['job_type'] ?? null,
            'salary' => $data['salary'] ?? null,
        ]);

        return response()->json($job, 201);
    }

    public function show(JobPost $jobPost)
    {
        return response()->json($jobPost->load('user','location','applications'));
    }

    public function update(Request $request, JobPost $jobPost)
    {
        if ($request->user()->id !== $jobPost->user_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'job_type' => 'nullable|string|max:50',
            'salary' => 'nullable|numeric',
            'status' => 'nullable|in:open,closed,filled',
        ]);

        $jobPost->update($data);

        return response()->json($jobPost);
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
