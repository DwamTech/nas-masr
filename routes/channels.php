<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private channel for user-to-user messaging.
 * Only the user themselves can listen to their own channel.
 */
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/**
 * Private channel for admin support.
 * Only admins can listen to this channel.
 */
Broadcast::channel('admin.support', function (User $user) {
    return $user->isAdmin();
});
