<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')
            ->orderBy('name')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'username' => $a->username,
                'phone' => $a->phone,
                'is_active' => $a->is_active,
                'code' => $a->code,
            ]);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.admin-management.partials.content', compact('admins'))->render(),
                'title' => 'المديرين',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.admin-management.index', compact('admins'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:50',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Auto-create wallet so admin appears in ledger/trial-balance
        $user->getOrCreateWallet();

        ActivityLog::log(
            event: 'user.admin_created',
            description: 'تم إضافة مدير جديد — ' . $user->name,
            subjectType: 'user',
            subjectId: $user->id,
            subjectLabel: $user->name,
            properties: ['username' => $user->username, 'code' => $user->code]
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة المدير بنجاح', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::where('role', 'admin')->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
        ]);

        // Prevent admin from deactivating themselves
        if (isset($data['is_active']) && !$data['is_active'] && $user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعطيل حسابك أنت.',
                'errors' => ['is_active' => ['لا يمكنك تعطيل حسابك أنت.']]
            ], 422);
        }

        $updateData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $user->phone,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($updateData);

        if (isset($data['is_active']) && $data['is_active'] != $wasActive) {
            $evt  = $data['is_active'] ? 'user.admin_activated' : 'user.admin_deactivated';
            $desc = $data['is_active']
                ? 'تم تنشيط مدير — ' . $user->name
                : 'تم إلغاء تنشيط مدير — ' . $user->name;
            ActivityLog::log(
                event: $evt,
                description: $desc,
                subjectType: 'user',
                subjectId: $user->id,
                subjectLabel: $user->name
            );
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث بيانات المدير']);
    }
}
