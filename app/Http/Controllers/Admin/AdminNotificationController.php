<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AdminNotification::latest()->take(50)->get(['id','type','order_number','message','is_read','created_at']);
        return response()->json([
            'items' => $items,
            'unread_count' => AdminNotification::where('is_read', false)->count(),
        ]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => AdminNotification::where('is_read', false)->count()
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        AdminNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
