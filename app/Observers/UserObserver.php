<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\WelcomeNotification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->notify(new WelcomeNotification);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }
}
