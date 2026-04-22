<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class CallCenterManagementController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
            $agents = User::where('role', 'callcenter')
                ->with(['createdOrders' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])])
                ->orderBy('name')
                ->get()
                ->map(fn($cc) => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'username' => $cc->username,
                    'phone' => $cc->phone,
                    'is_active' => $cc->is_active,
                    'created' => $cc->createdOrders->count(),
                    'revenue' => $cc->createdOrders->where('status', 'delivered')->sum('total'),
                    'code' => $cc->code,
                ]);
            return response()->json([
                'html' => view('admin.callcenter.partials.content', compact('agents'))->render(),
                'title' => 'كول سنتر',
                'csrf_token' => csrf_token(),
            ]);
        }

        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $agents = User::where('role', 'callcenter')
            ->with(['createdOrders' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])])
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => [
                'id' => $cc->id,
                'name' => $cc->name,
                'username' => $cc->username,
                'phone' => $cc->phone,
                'is_active' => $cc->is_active,
                'created' => $cc->createdOrders->count(),
                'revenue' => $cc->createdOrders->where('status', 'delivered')->sum('total'),
                'code' => $cc->code,
            ]);

        return view('admin.callcenter.index', compact('agents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:50',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
            'code' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'code' => $data['code'] ?? null,
            'role' => 'callcenter',
            'is_active' => true,
        ]);

        ActivityLog::log(
            event: 'user.callcenter_created',
            description: 'تم إضافة كول سنتر جديد — ' . $user->name,
            subjectType: 'user',
            subjectId: $user->id,
            subjectLabel: $user->name,
            properties: ['username' => $user->username]
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة موظف الكول سنتر', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::where('role', 'callcenter')->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
            'code' => 'nullable|string|max:50',
        ]);

        $updateData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $user->phone,
            'code' => $data['code'] ?? $user->code,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($updateData);

        if (isset($data['is_active']) && $data['is_active'] != $wasActive) {
            $evt  = $data['is_active'] ? 'user.callcenter_activated' : 'user.callcenter_deactivated';
            $desc = $data['is_active']
                ? 'تم تنشيط كول سنتر من الأدمن — ' . $user->name
                : 'تم إلغاء تنشيط كول سنتر من الأدمن — ' . $user->name;
            ActivityLog::log(
                event: $evt,
                description: $desc,
                subjectType: 'user',
                subjectId: $user->id,
                subjectLabel: $user->name
            );
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث الموظف']);
    }

    public function performance(Request $request, $id)
    {
        $user = User::where('role', 'callcenter')->findOrFail($id);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : Carbon::now()->endOfDay();

        $orders = Order::where('callcenter_id', $id)->whereBetween('created_at', [$from, $to])->get();
        $total = $orders->count();
        $cancelled = $orders->where('status', 'cancelled')->count();
        $revenue = $orders->where('status', 'delivered')->sum('total');

        return response()->json([
            'name' => $user->name,
            'total' => $total,
            'cancelled' => $cancelled,
            'revenue' => $revenue,
        ]);
    }


}
