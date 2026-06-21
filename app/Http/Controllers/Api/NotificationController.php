<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(10);

        return response()->json($notifications);
    }

    public function unread()
    {
        $notifications = auth()->user()
            ->unreadNotifications()
            ->get();

        return response()->json([
            'count'         => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(string $id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
