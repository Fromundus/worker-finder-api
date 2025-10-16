<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Booking;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'employer') {
            $bookings = Booking::with(['worker', 'employer'])
                ->where('employer_id', $user->id)
                ->latest()
                ->get();
        } else {
            $bookings = Booking::with(['worker', 'employer'])
                ->where('worker_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'worker_id'   => 'required|exists:users,id',
            'job_title'   => 'required|string|max:255',
            'description' => 'nullable|string',
            'salary'      => 'nullable|numeric|min:1',
        ]);

        $employer = $request->user();
        $workerId = $request->worker_id;

        // Check if worker already has active booking
        $hasActiveBooking = Booking::where('worker_id', $workerId)
            ->where('status', 'active')
            ->exists();
        
        $hasActiveApplication = Application::where("user_id", $workerId)->where("status", "active")->exists();

        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'Worker already has an active booking.'
            ], 422);
        }

        if ($hasActiveApplication) {
            return response()->json([
                'message' => 'Worker already has an active application.'
            ], 422);
        }

        $booking = Booking::create([
            'employer_id' => $employer->id,
            'worker_id'   => $workerId,
            'job_title'   => $request->job_title,
            'description' => $request->description,
            'salary'      => $request->salary,
            'status'      => 'pending',
        ]);

        NotificationService::storeNotification(
            $workerId, // employer user_id
            'booking',
            "You have a new booking request from {$request->user()->first_name} {$request->user()->middle_name} {$request->user()->last_name} {$request->user()->suffix}. Job Title: {$request->job_title}"
        );

        return response()->json([
            'message' => 'Booking request sent to worker.',
            'booking' => $booking->load('worker:id,first_name,middle_name,last_name,suffix,email'),
        ], 201);
    }

    // Worker accepts booking → status = active, remove their applications
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            "status" => "required",
        ]);

        
        $booking = Booking::findOrFail($id);
        
        $worker = User::where('id', $booking->worker_id)->first();
        $employer = User::where('id', $booking->employer_id)->first();
        
        // Delete all pending applications of this worker
        if($validated["status"] === "active"){
            $activeApplication = Application::where("user_id", $worker->id)->where("status", "active")->exists();

            $activeBooking = Booking::where("worker_id", $worker->id)->where("status", "active")->exists();

            if($activeApplication || $activeBooking){
                return response()->json(['message' => "You can't accept bookings when you have an active application/booking."], 422);
            }

            $booking->update(['status' => $validated["status"]]);
            
            Application::where('user_id', $worker->id)
            ->whereIn('status', ['pending', 'accepted']) // adjust if you only want pending
            ->delete();

            Booking::where("worker_id", $worker->id)->where("status", "pending")->delete();

            NotificationService::storeNotification(
                $booking->employer_id, // employer user_id
                'booking',
                "{$worker->first_name} {$worker->middle_name} {$worker->last_name} {$worker->suffix} accepted your booking. Job Title: {$booking->job_title}"
            );
        } else if ($validated["status"] === "cancelled"){
            if($request->user()->role === "worker"){
                NotificationService::storeNotification(
                    $booking->employer_id, // employer user_id
                    'booking',
                    "{$worker->first_name} {$worker->middle_name} {$worker->last_name} {$worker->suffix} rejected your booking. Job Title: {$booking->job_title}"
                );
            } else {
                NotificationService::storeNotification(
                    $booking->worker_id, // employer user_id
                    'booking',
                    "{$employer->first_name} {$employer->middle_name} {$employer->last_name} {$employer->suffix} cancelled the booking. Job Title: {$booking->job_title}"
                );
            }
        } else if ($validated["status"] === "completed"){
            NotificationService::storeNotification(
                $booking->worker_id, // employer user_id
                'booking',
                "{$employer->first_name} {$employer->middle_name} {$employer->last_name} {$employer->suffix} marked the booking as completed. Job Title: {$booking->job_title}"
            );
        }
        
        $booking->update(['status' => $validated["status"]]);

        return response()->json([
            'message' => 'Booking status updated successfully.',
            'booking' => $booking,
        ]);
    }

    // Employer or worker can complete/cancel active bookings
    public function update(Request $request, Booking $booking)
    {
        $user = $request->user();

        if (!in_array($user->id, [$booking->employer_id, $booking->worker_id]) && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:completed,cancelled',
        ]);

        if ($booking->status !== 'active') {
            return response()->json(['message' => 'Only active bookings can be updated.'], 422);
        }

        $booking->update($data);

        return response()->json([
            'message' => "Booking {$data['status']} successfully.",
            'booking' => $booking,
        ]);
    }
}
