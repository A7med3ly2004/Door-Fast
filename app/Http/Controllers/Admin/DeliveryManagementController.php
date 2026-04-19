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
            $today      = today();
            $allDeliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
                ->with([
                    'activeShift',
                    'deliveryOrders' => fn($q) => $q->whereDate('created_at', $today),
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

        $today      = today();
        $allDeliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->with([
                'activeShift',
                'deliveryOrders' => fn($q) => $q->whereDate('created_at', $today),
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

}
