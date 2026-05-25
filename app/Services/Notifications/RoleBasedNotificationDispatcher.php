<?php

namespace App\Services\Notifications;

use App\Models\User;

class RoleBasedNotificationDispatcher implements NotificationDispatcherInterface
{
    public function __construct(
        private readonly EmailNotificationDispatcher $emailDispatcher,
        private readonly DatabaseNotificationDispatcher $databaseDispatcher,
    ) {}

    public function send(User $user, string $message): void
    {
        if ($user->isAdmin()) {
            $this->databaseDispatcher->send($user, $message);

            return;
        }

        $this->emailDispatcher->send($user, $message);
    }
}
