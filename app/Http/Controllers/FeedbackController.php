<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Booking;
use App\Models\Feedback;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, $applicationId)
    {
        $data = $request->validate([
            'to_user_id'  => 'required|exists:users,id',
            'rating'      => 'required|integer|min:1|max:5',
            'comment'     => 'nullable|string|max:2000',
            'job_post_id' => 'nullable|exists:job_posts,id',
        ]);

        // Prevent self-feedback
        if ($request->user()->id == $data['to_user_id']) {
            return response()->json(['message' => 'Cannot rate yourself'], 422);
        }

        // Prevent duplicate feedback for the same job_post
        $existing = Feedback::where('from_user_id', $request->user()->id)
            ->where('to_user_id', $data['to_user_id'])
            ->where('job_post_id', $data['job_post_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already left feedback for this user on this job.'], 422);
        }

        // Create feedback
        $feedback = Feedback::create([
            'from_user_id' => $request->user()->id,
            'to_user_id'   => $data['to_user_id'],
            'rating'       => $data['rating'],
            'comment'      => $data['comment'] ?? null,
            'job_post_id'  => $data['job_post_id'] ?? null,
        ]);

        // Update application rating flags
        $application = Application::findOrFail($applicationId);
        if ($application) {
            if ($request->user()->role === "employer") {
                $application->update([
                    "workerIsRated" => Carbon::now(),
                ]);
            } else if ($request->user()->role === "worker") {
                $application->update([
                    "employerIsRated" => Carbon::now(),
                ]);
            }
        }

        // Update average rating of the user who got rated
        $toUser = User::findOrFail($data['to_user_id']);
        $avg = $toUser->feedbackReceived()->avg('rating') ?? 0;
        $toUser->update(['average_rating' => round($avg, 2)]);

        // 🔔 Notify the rated user
        NotificationService::storeNotification(
            $data['to_user_id'],
            'feedback',
            "⭐ You received new feedback from {$request->user()->name} on job '{$application->jobPost->title}' with a rating of {$data['rating']}."
        );

        return response()->json([
            'message'        => 'Feedback submitted successfully',
            'feedback'       => $feedback,
            'average_rating' => $toUser->average_rating,
        ], 201);
    }

    public function storeBooking(Request $request, $bookingId)
    {
        $data = $request->validate([
            'to_user_id'  => 'required|exists:users,id',
            'rating'      => 'required|integer|min:1|max:5',
            'comment'     => 'nullable|string|max:2000',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        // Prevent self-feedback
        if ($request->user()->id == $data['to_user_id']) {
            return response()->json(['message' => 'Cannot rate yourself'], 422);
        }

        // Prevent duplicate feedback for the same job_post
        $existing = Feedback::where('from_user_id', $request->user()->id)
            ->where('to_user_id', $data['to_user_id'])
            ->where('booking_id', $data['booking_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already left feedback for this user on this job.'], 422);
        }

        // Create feedback
        $feedback = Feedback::create([
            'from_user_id' => $request->user()->id,
            'to_user_id'   => $data['to_user_id'],
            'rating'       => $data['rating'],
            'comment'      => $data['comment'] ?? null,
            'booking_id'  => $data['booking_id'] ?? null,
        ]);

        // Update application rating flags
        $booking = Booking::findOrFail($bookingId);
        if ($booking) {
            if ($request->user()->role === "employer") {
                $booking->update([
                    "workerIsRated" => Carbon::now(),
                ]);
            } else if ($request->user()->role === "worker") {
                $booking->update([
                    "employerIsRated" => Carbon::now(),
                ]);
            }
        }

        // Update average rating of the user who got rated
        $toUser = User::findOrFail($data['to_user_id']);
        $avg = $toUser->feedbackReceived()->avg('rating') ?? 0;
        $toUser->update(['average_rating' => round($avg, 2)]);

        // 🔔 Notify the rated user
        NotificationService::storeNotification(
            $data['to_user_id'],
            'feedback',
            "⭐ You received new feedback from {$request->user()->name} on job '{$booking->job_title}' with a rating of {$data['rating']}."
        );

        return response()->json([
            'message'        => 'Feedback submitted successfully',
            'feedback'       => $feedback,
            'average_rating' => $toUser->average_rating,
        ], 201);
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
        $feedback = Feedback::with(['fromUser', 'toUser', 'jobPost', 'booking'])
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
