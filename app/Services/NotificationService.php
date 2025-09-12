<?php

namespace App\Services;

use App\Models\Notification;
use Carbon\Carbon;

class NotificationService
{
    public static function storeNotification(int $userId, string $type, string $content): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'content' => $content,
            'sent_at' => Carbon::now(),
        ]);
    }
}
