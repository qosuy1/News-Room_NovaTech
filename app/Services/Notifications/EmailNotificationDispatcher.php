<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\WriterAndAdminNotification;

class EmailNotificationDispatcher implements NotificationDispatcherInterface
{
    public function send(User $user, string $message): void
    {
        $user->notify(new WriterAndAdminNotification($message, ['mail']));
    }
}
