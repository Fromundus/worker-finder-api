<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    public $message;

    public function __construct(Message $message)
    {
        // Eager load sender so frontend gets user info
        $this->message = $message->load('sender');
        \Log::info("MessageSent event constructed", ['message_id' => $message->id]);
    }

    public function broadcastOn()
    {
        $senderId = $this->message->sender_id;

        $convo = Conversation::where("id", $this->message->conversation_id)->first();

        $receiverId = null;

        if($convo->user_one_id === $senderId){
            $receiverId = $convo->user_two_id;
        } else if ($convo->user_two_id === $senderId){
            $receiverId = $convo->user_one_id;
        }

        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
            new PrivateChannel('users.' . $receiverId), // make sure to put here the owner of the account, or the one who receives the messages
        ];
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message->toArray(),
        ];
    }

    // Optional: shorten event name so frontend can listen with `.MessageSent`
    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
