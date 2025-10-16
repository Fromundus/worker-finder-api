<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::with([
            'userOne:id,first_name,middle_name,last_name,suffix',
            'userTwo:id,first_name,middle_name,last_name,suffix',
            'messages' => fn($q) => $q->latest()->limit(1),
        ])
        ->where('user_one_id', $user->id)
        ->orWhere('user_two_id', $user->id)
        ->orderBy('last_message_at', 'desc')
        ->get();

        return response()->json($conversations);
    }

    // Create or fetch conversation with another user
    public function start(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'user_id' => 'required|exists:users,id|different:' . $user->id,
        ]);

        $otherUserId = $request->input('user_id');

        $conversation = Conversation::where(function ($q) use ($user, $otherUserId) {
            $q->where('user_one_id', $user->id)->where('user_two_id', $otherUserId);
        })
        ->orWhere(function ($q) use ($user, $otherUserId) {
            $q->where('user_one_id', $otherUserId)->where('user_two_id', $user->id);
        })
        ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $user->id,
                'user_two_id' => $otherUserId,
                'last_message_at' => Carbon::now(),
            ]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
        ]);
    }

    // Get single conversation details
    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        if (! $conversation->hasParticipant($user->id)) {
            abort(403);
        }

        $conversation->load(['userOne:id,first_name,middle_name,last_name,suffix', 'userTwo:id,first_name,middle_name,last_name,suffix']);
        return response()->json($conversation);
    }
}
