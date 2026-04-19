<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'اسم المستخدم مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        $credentials = [
            'username'  => $request->username,
            'password'  => $request->password,
            'is_active' => true,
        ];

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return response()->json([
                'success'  => true,
                'redirect' => $this->redirectByRole(Auth::user()->role),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
        ], 401);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectByRole(string $role): string
    {
        return match($role) {
            'admin'            => route('admin.dashboard'),
            'callcenter'       => route('callcenter.dashboard'),
            'delivery'         => route('delivery.dashboard'),
            'reserve_delivery' => route('reserve.dashboard'),
            default            => route('login'),
        };
    }
}