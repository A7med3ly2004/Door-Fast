<?php

namespace App\Http\Controllers\Api\Reserve;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    /**
     * POST /api/reserve/fcm-token
     * يحفظ FCM token للمندوب الاحتياطي المسجّل دخول حالياً
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
}
