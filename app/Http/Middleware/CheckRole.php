<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        if (!in_array(auth()->user()->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            abort(403, 'غير مصرح لك بالدخول');
        }

        if (!auth()->user()->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'هذا الحساب موقوف'], 403);
            }
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['username' => 'هذا الحساب موقوف']);
        }

        return $next($request);
    }
}