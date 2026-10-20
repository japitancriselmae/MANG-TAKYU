<?php

namespace App\Http\Controllers;

use App\Notifications\CookingBatchNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KitchenBatchNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->where('type', CookingBatchNotification::class)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'message' => $notification->data['message'],
                'status' => $notification->data['status'],
                'started_at' => $notification->data['started_at'] ?? null,
                'duration_minutes' => $notification->data['duration_minutes'] ?? null,
            ]);

        return response()->json([
            'unread_count' => $request->user()
                ->unreadNotifications()
                ->where('type', CookingBatchNotification::class)
                ->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notification): Response
    {
        $userNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->where('type', CookingBatchNotification::class)
            ->firstOrFail();

        $userNotification->markAsRead();

        return response()->noContent();
    }
}
