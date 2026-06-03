<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    /**
     * POST /api/delivery/fcm-token
     * يحفظ FCM token للمندوب الأساسي المسجّل دخول حالياً
     */
    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string|max:500',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التوكن بنجاح',
        ]);
    }

    /**
     * DELETE /api/delivery/fcm-token
     * يحذف FCM token للمندوب الأساسي إذا تطابق مع التوكن المُرسَل فقط
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        if ($user->fcm_token === $request->fcm_token) {
            $user->update(['fcm_token' => null]);
        }

        return response()->json(['success' => true], 200);
    }
}
