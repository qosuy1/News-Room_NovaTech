<?php

namespace App\Services\Notifications;

use App\Models\User;

interface NotificationDispatcherInterface
{
    public function send(User $user, string $message): void;
}
