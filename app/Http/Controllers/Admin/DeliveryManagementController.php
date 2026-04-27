<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DeliveryManagementController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
            $allDeliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
                ->with([
                    'activeShift',
                    'deliveryOrders' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday]),
                ])
                ->orderBy('name')
                ->get()
                ->map(fn($d) => [
                    'id'         => $d->id,
                    'name'       => $d->name,
                    'username'   => $d->username,
                    'role'       => $d->role,
                    'phone'      => $d->phone,
                    'is_active'  => $d->is_active,
                    'completed'  => $d->deliveryOrders->where('status', 'delivered')->count(),
                    'revenue'    => $d->deliveryOrders->where('status', 'delivered')->sum('total'),
                    'code'       => $d->code,
                    'shift_active' => $d->activeShift !== null,
                    'incentive_slices' => $d->incentive_slices,
                ]);

            $deliveries = $allDeliveries->where('role', 'delivery')->values();
            $reserveDeliveries = $allDeliveries->where('role', 'reserve_delivery')->values();

            return response()->json([
                'html'       => view('admin.delivery.partials.content', compact('deliveries', 'reserveDeliveries'))->render(),
                'title'      => 'المناديب',
                'csrf_token' => csrf_token(),
            ]);
        }

        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $allDeliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->with([
                'activeShift',
                'deliveryOrders' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday]),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'username'   => $d->username,
                'role'       => $d->role,
                'phone'      => $d->phone,
                'is_active'  => $d->is_active,
                'completed'  => $d->deliveryOrders->where('status', 'delivered')->count(),
                'revenue'    => $d->deliveryOrders->where('status', 'delivered')->sum('total'),
                'code'       => $d->code,
                'shift_active' => $d->activeShift !== null,
                'incentive_slices' => $d->incentive_slices,
            ]);

        $deliveries = $allDeliveries->where('role', 'delivery')->values();
        $reserveDeliveries = $allDeliveries->where('role', 'reserve_delivery')->values();

        return view('admin.delivery.index', compact('deliveries', 'reserveDeliveries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|unique:users,username|max:50',
            'password'   => 'required|string|min:6',
            'phone'      => 'nullable|string|max:30',
            'code'       => 'nullable|string|max:50',
            'role'       => 'nullable|in:delivery,reserve_delivery',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'username'  => $data['username'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'code'      => $data['code'] ?? null,
            'role'      => $data['role'] ?? 'delivery',
            'is_active' => true,
        ]);

        ActivityLog::log(
            event: 'user.delivery_created',
            description: 'تم إضافة مندوب جديد — ' . $user->name,
            subjectType: 'user',
            subjectId: $user->id,
            subjectLabel: $user->name,
            properties: ['role' => $user->role, 'username' => $user->username]
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة المندوب', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'nullable|string|max:30',
            'is_active'  => 'boolean',
            'password'   => 'nullable|string|min:6',
            'code'       => 'nullable|string|max:50',
            'incentive_slices' => 'nullable|array',
        ]);

        $updateData = [
            'name'      => $data['name'],
            'phone'     => $data['phone'] ?? $user->phone,
            'code'      => $data['code'] ?? $user->code,
            'is_active' => $data['is_active'] ?? $user->is_active,
            'incentive_slices' => $data['incentive_slices'] ?? $user->incentive_slices,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($updateData);

        // Log toggle activation
        if (isset($data['is_active']) && $data['is_active'] != $wasActive) {
            $evt = $data['is_active'] ? 'user.delivery_activated' : 'user.delivery_deactivated';
            $desc = $data['is_active']
                ? 'تم تنشيط مندوب من الأدمن — ' . $user->name
                : 'تم إلغاء تنشيط مندوب من الأدمن — ' . $user->name;
            ActivityLog::log(
                event: $evt,
                description: $desc,
                subjectType: 'user',
                subjectId: $user->id,
                subjectLabel: $user->name
            );
        }


        return response()->json(['success' => true, 'message' => 'تم تحديث المندوب']);
    }

    public function toggleShift(Request $request, $id)
    {
        $delivery = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $activeShift = \App\Models\Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        if ($activeShift) {
            $activeShift->update([
                'ended_at' => Carbon::now(),
                'is_active' => false,
            ]);
            $msg = 'تم إنهاء الوردية للمندوب بنجاح';
        } else {
            \App\Models\Shift::create([
                'delivery_id' => $delivery->id,
                'date' => $businessDate,
                'started_at' => Carbon::now(),
                'is_active' => true,
            ]);
            $msg = 'تم بدء وردية جديدة للمندوب بنجاح';
        }

        ActivityLog::log(
            event: 'delivery.shift_toggled',
            description: $msg . ' — ' . $delivery->name,
            subjectType: 'user',
            subjectId: $delivery->id,
            subjectLabel: $delivery->name
        );

        return response()->json(['success' => true, 'message' => $msg]);
    }

}
