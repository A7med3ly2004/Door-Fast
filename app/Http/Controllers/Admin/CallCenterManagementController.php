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
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $agents = User::where('role', 'callcenter')
            ->with(['createdOrders' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])])
            ->orderBy('name')
            ->get()
            ->map(function ($cc) use ($startOfToday) {
                $activeShift = \App\Models\CallcenterShift::where('callcenter_id', $cc->id)
                    ->where('date', $startOfToday->toDateString())
                    ->where('is_active', true)
                    ->exists();

                return [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'username' => $cc->username,
                    'phone' => $cc->phone,
                    'is_active' => $cc->is_active,
                    'shift_active' => $activeShift,
                    'created' => $cc->createdOrders->count(),
                    'revenue' => $cc->createdOrders->where('status', 'delivered')->sum('total'),
                    'code' => $cc->code,
                ];
            });

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.callcenter.partials.content', compact('agents'))->render(),
                'title' => 'كول سنتر',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.callcenter.index', compact('agents'));
    }

    public function toggleShift(Request $request, $id)
    {
        $callcenter = User::where('role', 'callcenter')->findOrFail($id);
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $activeShift = \App\Models\CallcenterShift::where('callcenter_id', $callcenter->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        if ($activeShift) {
            $activeShift->update([
                'ended_at' => Carbon::now(),
                'is_active' => false,
            ]);
            $msg = 'تم إنهاء وردية الموظف بنجاح';
        } else {
            \App\Models\CallcenterShift::create([
                'callcenter_id' => $callcenter->id,
                'date' => $businessDate,
                'started_at' => Carbon::now(),
                'is_active' => true,
            ]);
            $msg = 'تم بدء وردية جديدة للموظف بنجاح';
        }

        ActivityLog::log(
            event: 'callcenter.shift_toggled',
            description: $msg . ' — ' . $callcenter->name,
            subjectType: 'user',
            subjectId: $callcenter->id,
            subjectLabel: $callcenter->name
        );

        return response()->json(['success' => true, 'message' => $msg]);
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
