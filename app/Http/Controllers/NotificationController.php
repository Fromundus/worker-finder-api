<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // public function index(Request $request)
    // {
    //     return response()->json(
    //         $request->user()->notifications()->latest()->paginate(20)
    //     );
    // }

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'nullable|string',
            'content' => 'required|string',
            'send_now' => 'nullable|boolean',
        ]);

        $notification = Notification::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'] ?? 'system',
            'content' => $data['content'],
            'sent_at' => $data['send_now'] ? Carbon::now() : null,
        ]);

        return response()->json($notification, 201);
    }

    // public function markRead(Request $request, Notification $notification)
    // {
    //     if ($notification->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     $notification->update(['is_read' => true]);

    //     return response()->json($notification);
    // }

    public function markAsRead($id, Request $request)
    {
        $user = $request->user();
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->is_read = true;
        $notification->save();

        return response()->json(['message' => 'Notification marked as read']);
    }
}
