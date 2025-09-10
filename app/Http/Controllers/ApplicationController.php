<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'job_post_id' => 'required|exists:job_posts,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $exists = Application::where('job_post_id', $data['job_post_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already applied'], 422);
        }

        $application = Application::create([
            'job_post_id' => $data['job_post_id'],
            'user_id' => $request->user()->id,
            'message' => $data['message'] ?? null,
        ]);

        return response()->json($application, 201);
    }

    public function myApplications(Request $request)
    {
        return response()->json(
            $request->user()->applications()->with('jobPost')->paginate(15)
        );
    }

    public function updateStatus(Request $request, Application $application)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,accepted,rejected,withdrawn',
        ]);

        $job = $application->jobPost;
        if ($request->user()->id !== $job->user_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $application->update(['status' => $data['status']]);

        if ($data['status'] === 'accepted') {
            $job->update(['status' => 'filled']);
        }

        return response()->json($application);
    }
}
