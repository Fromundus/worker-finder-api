<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index($id)
    {
        $projects = Project::where('user_id', $id)->latest()->get();
        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'employer' => "required|string|max:255",
            'description' => 'nullable|string',
            'date_started' => 'required|date',
            'date_ended' => 'required|date|after_or_equal:date_started',
            'picture' => 'nullable|image|max:2048',
        ]);

        $uploadFolder = 'uploads/users';
        $filename = null;

        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs($uploadFolder, $filename, 'public');
        }

        $project = Project::create([
            ...$validated,
            'user_id' => auth()->id(),
            'picture' => $filename,
        ]);

        return response()->json($project, 201);
    }

    // public function update(Request $request, Project $project)
    // {
    //     $validated = $request->validate([
    //         'title' => 'sometimes|string|max:255',
    //         'employer' => "sometimes|string|max:255",
    //         'description' => 'nullable|string',
    //         'date_started' => 'nullable|date',
    //         'date_ended' => 'nullable|date|after_or_equal:date_started',
    //         'picture' => 'nullable|image|max:2048',
    //     ]);

    //     if ($request->hasFile('picture')) {
    //         if ($project->picture) {
    //             Storage::disk('public')->delete($project->picture);
    //         }
    //         $validated['picture'] = $request->file('picture')->store('projects', 'public');
    //     }

    //     $project->update($validated);

    //     return response()->json($project);
    // }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'employer' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_started' => 'nullable|date',
            'date_ended' => 'nullable|date|after_or_equal:date_started',
            'picture' => 'nullable|image|max:2048',
        ]);

        $uploadFolder = 'uploads/users';

        // Handle picture update
        if ($request->hasFile('picture')) {
            // Delete old picture if it exists
            if ($project->picture) {
                Storage::disk('public')->delete($uploadFolder . '/' . $project->picture);
            }

            // Save new picture
            $file = $request->file('picture');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs($uploadFolder, $filename, 'public');

            $validated['picture'] = $filename;
        }

        $project->update($validated);

        return response()->json($project);
    }


    public function destroy(Project $project)
    {
        if ($project->picture) {
            Storage::disk('public')->delete($project->picture);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }
}
