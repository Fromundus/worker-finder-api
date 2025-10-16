<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        if (! $conversation->hasParticipant($user->id)) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,first_name,middle_name,last_name,suffix')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            "data" => $messages,
            "conversation" => $conversation->load(['userOne:id,first_name,middle_name,last_name,suffix', 'userTwo:id,first_name,middle_name,last_name,suffix']),
        ]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        if (! $conversation->hasParticipant($user->id)) {
            abort(403);
        }

        $data = $request->validate(['body' => 'required|string']);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => Carbon::now()]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message->load('sender:id,first_name,middle_name,last_name,suffix'), 201);
    }
}
