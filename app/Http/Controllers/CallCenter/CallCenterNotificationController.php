<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;

class CallCenterNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AdminNotification::whereIn('audience', ['callcenter', 'all'])
            ->latest()
            ->take(20)
            ->get(['id', 'type', 'order_number', 'message', 'is_read_by_callcenter as is_read', 'created_at']);

        return response()->json([
            'items'        => $items,
            'unread_count' => AdminNotification::whereIn('audience', ['callcenter', 'all'])
                ->where('is_read_by_callcenter', false)
                ->count(),
        ]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => AdminNotification::whereIn('audience', ['callcenter', 'all'])
                ->where('is_read_by_callcenter', false)
                ->count(),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        AdminNotification::whereIn('audience', ['callcenter', 'all'])
            ->where('is_read_by_callcenter', false)
            ->update(['is_read_by_callcenter' => true]);

        return response()->json(['success' => true]);
    }
}
