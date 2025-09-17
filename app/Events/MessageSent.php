<?php

namespace App\Events;

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
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
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
