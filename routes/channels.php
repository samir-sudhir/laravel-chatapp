<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat room channels
Broadcast::channel('chat-room.{roomId}', function ($user, $roomId) {
    // For now, allow all authenticated users to join any room
    // You can add more specific authorization logic here
    return $user;
});
