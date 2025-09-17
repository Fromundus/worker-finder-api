<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    \Log::info('Broadcast auth attempt', [
        'user' => $user->id ?? null,
        'conversationId' => $conversationId,
    ]);
    
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return $conversation->hasParticipant($user->id);
});
