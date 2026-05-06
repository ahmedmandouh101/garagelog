<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    // Get all notifications
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(10);

        return response()->json($notifications);
    }

    // Get unread notifications only
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

    // Mark a notification as read
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

    // Mark all notifications as read
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
