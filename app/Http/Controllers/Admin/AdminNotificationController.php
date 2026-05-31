<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AdminNotification::whereIn('audience', ['admin', 'all'])
            ->latest()
            ->take(20)
            ->get(['id', 'type', 'order_number', 'message', 'is_read_by_admin as is_read', 'created_at']);

        return response()->json([
            'items'        => $items,
            'unread_count' => AdminNotification::whereIn('audience', ['admin', 'all'])
                ->where('is_read_by_admin', false)
                ->count(),
        ]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => AdminNotification::whereIn('audience', ['admin', 'all'])
                ->where('is_read_by_admin', false)
                ->count(),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        AdminNotification::whereIn('audience', ['admin', 'all'])
            ->where('is_read_by_admin', false)
            ->update(['is_read_by_admin' => true]);

        return response()->json(['success' => true]);
    }
}
