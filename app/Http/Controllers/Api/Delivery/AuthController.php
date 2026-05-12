<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/delivery/login or /api/reserve/login
     * Authenticate a delivery user and return a Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        // Determine target role based on URL
        $isReserve = $request->is('*/reserve/*');
        $targetRole = $isReserve ? 'reserve_delivery' : 'delivery';

        if ($user->role !== $targetRole) {
            $errorMsg = $isReserve 
                ? 'هذا الحساب مخصص للدليفري الأساسي، يرجى استخدامه في التطبيق الصحيح' 
                : 'هذا الحساب مخصص للدليفري الاحتياطي، يرجى استخدامه في تطبيق الاحتياطي';
            
            return response()->json([
                'success' => false,
                'message' => $errorMsg,
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب موقوف',
            ], 403);
        }

        // Revoke all existing delivery-mobile tokens
        $user->tokens()->where('name', 'delivery-mobile')->delete();

        $token = $user->createToken('delivery-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'code'  => $user->code,
                'phone' => $user->phone,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * POST /api/delivery/logout
     * Revoke the current access token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * GET /api/delivery/me
     * Return authenticated delivery user's basic info.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'code'  => $user->code,
                'phone' => $user->phone,
                'role'  => $user->role,
            ],
        ]);
    }
}
