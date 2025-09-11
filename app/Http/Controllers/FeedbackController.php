<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'job_post_id' => 'nullable|exists:job_posts,id',
        ]);

        if ($request->user()->id == $data['to_user_id']) {
            return response()->json(['message' => 'Cannot rate yourself'], 422);
        }

        $feedback = Feedback::create([
            'from_user_id' => $request->user()->id,
            'to_user_id' => $data['to_user_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'job_post_id' => $data['job_post_id'] ?? null,
        ]);

        // update average rating
        $toUser = User::findOrFail($data['to_user_id']);
        $avg = $toUser->feedbackReceived()->avg('rating') ?? 0;
        $toUser->update(['average_rating' => round($avg, 2)]);

        return response()->json($feedback, 201);
    }

    public function indexForUser($userId)
    {
        return response()->json(
            Feedback::where('to_user_id', $userId)->with('fromUser')->paginate(15)
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // If worker → feedback given to them
        // If employer → feedback they gave to workers (or received from workers, depending on spec)
        $feedback = Feedback::with(['fromUser', 'toUser', 'jobPost'])
            ->where('to_user_id', $user->id) // feedback received by logged-in user
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = round($feedback->avg('rating'), 2);

        $ratingCounts = $feedback->groupBy('rating')->map->count();

        return response()->json([
            'feedback' => $feedback,
            'averageRating' => $averageRating,
            'ratingCounts' => $ratingCounts,
        ]);
    }

}
