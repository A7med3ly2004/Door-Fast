<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount(['orders', 'recipientOrders', 'addresses'])
            ->withSum(['orders' => fn($q) => $q->where('status', 'delivered')], 'total')
            ->withSum(['recipientOrders' => fn($q) => $q->where('status', 'delivered')], 'total')
            ->withSum(['orders' => fn($q) => $q->where('status', 'delivered')], 'delivery_fee')
            ->withSum(['recipientOrders' => fn($q) => $q->where('status', 'delivered')], 'delivery_fee')
            ->with([
                'orders' => fn($q) => $q->latest()->take(1),
                'recipientOrders' => fn($q) => $q->latest()->take(1)
            ])
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%"));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.clients.partials.content')->render(),
                'title'      => 'العملاء',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json($query->paginate(15)->through(fn($c) => [
                'id'              => $c->id,
                'name'            => $c->name,
                'phone'           => $c->phone,
                'phone2'          => $c->phone2,
                'code'            => $c->code,
                'orders_count'    => $c->orders_count + $c->recipient_orders_count,
                'orders_sum_total'=> ($c->orders_sum_total ?? 0) + ($c->recipient_orders_sum_total ?? 0),
                'orders_sum_delivery_fee'=> ($c->orders_sum_delivery_fee ?? 0) + ($c->recipient_orders_sum_delivery_fee ?? 0),
                'addresses_count' => $c->addresses_count,
                'created_at'      => $c->created_at->toIso8601String(),
                'orders'          => [
                    [
                        'created_at' => collect([
                            $c->orders->first()?->created_at,
                            $c->recipientOrders->first()?->created_at,
                        ])->filter()->max()
                    ]
                ]
            ]));
        }

        $clients = $query->paginate(15);
        return view('admin.clients.index', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:30|unique:clients,phone',
            'phone2'          => 'nullable|string|max:30',
            'code'            => 'nullable|string|max:20|unique:clients,code',
            'first_address'   => 'required|string|max:500',
        ], [
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لعميل آخر.'
        ]);

        $client = Client::create([
            'name'   => $data['name'],
            'phone'  => $data['phone'],
            'phone2' => $data['phone2'] ?? null,
            'code'   => $data['code'] ?? Client::generateCode(),
        ]);

        $client->addresses()->create([
            'address'    => $data['first_address'],
            'is_default' => true,
        ]);

        ActivityLog::log(
            event: 'client.created',
            description: 'تم إضافة عميل جديد من الأدمن',
            subjectType: 'client',
            subjectId: $client->id,
            subjectLabel: $client->name . ' — ' . $client->code,
            properties: ['client_code' => $client->code, 'client_name' => $client->name, 'phone' => $client->phone]
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة العميل', 'client' => $client]);
    }

    public function show($id)
    {
        $client = Client::with([
            'addresses',
            'orders' => fn($q) => $q->with(['callcenter', 'delivery'])->latest(),
            'recipientOrders' => fn($q) => $q->with(['callcenter', 'delivery'])->latest(),
        ])
        ->withCount(['orders', 'recipientOrders'])
        ->withSum(['orders' => fn($q) => $q->where('status', 'delivered')], 'total')
        ->withSum(['recipientOrders' => fn($q) => $q->where('status', 'delivered')], 'total')
        ->withSum(['orders' => fn($q) => $q->where('status', 'delivered')], 'delivery_fee')
        ->withSum(['recipientOrders' => fn($q) => $q->where('status', 'delivered')], 'delivery_fee')
        ->findOrFail($id);

        $primaryOrders = $client->orders->toBase()->map(fn($o) => array_merge(
            $this->formatOrder($o), ['role' => 'عميل أساسي']
        ));

        $recipientOrders = $client->recipientOrders->toBase()->map(fn($o) => array_merge(
            $this->formatOrder($o), ['role' => 'مستلم']
        ));

        $allOrders = $primaryOrders->merge($recipientOrders)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        return response()->json([
            'client' => [
                'id'          => $client->id,
                'name'        => $client->name,
                'phone'       => $client->phone,
                'phone2'      => $client->phone2,
                'code'        => $client->code,
                'orders_count'=> $client->orders_count + $client->recipient_orders_count,
                'total_spent' => ($client->orders_sum_total ?? 0) + ($client->recipient_orders_sum_total ?? 0),
                'total_delivery_fee' => ($client->orders_sum_delivery_fee ?? 0) + ($client->recipient_orders_sum_delivery_fee ?? 0),
                'created_at'  => $client->created_at->toIso8601String(),
                'addresses'   => $client->addresses->take(5),
                'orders'      => $allOrders,
            ],
        ]);
    }

    private function formatOrder($o): array
    {
        return [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'total'        => $o->total,
            'status'       => $o->status,
            'callcenter'   => $o->callcenter?->name,
            'delivery'     => $o->delivery?->name,
            'created_at'   => $o->created_at->toIso8601String(),
        ];
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => ['required', 'string', 'max:30', \Illuminate\Validation\Rule::unique('clients', 'phone')->ignore($client->id)],
            'phone2' => 'nullable|string|max:30',
        ], [
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لعميل آخر.'
        ]);

        $client->update($data);

        // Handle addresses
        if ($request->filled('addresses')) {
            foreach ($request->addresses as $addrData) {
                if (!empty($addrData['id'])) {
                    $addr = ClientAddress::where('client_id', $client->id)->find($addrData['id']);
                    if ($addr) {
                        if (!empty($addrData['_delete'])) {
                            $addr->delete();
                        } else {
                            $addr->update(['address' => $addrData['address'], 'is_default' => !empty($addrData['is_default'])]);
                        }
                    }
                } elseif (!empty($addrData['address'])) {
                    $client->addresses()->create([
                        'address'    => $addrData['address'],
                        'is_default' => !empty($addrData['is_default']),
                    ]);
                }
            }
            // Ensure only one default
            if ($client->addresses()->where('is_default', true)->count() === 0 && $client->addresses()->count() > 0) {
                $client->addresses()->first()->update(['is_default' => true]);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث العميل']);
    }

    public function destroy($id)
    {
        $client = Client::withCount(['orders', 'recipientOrders'])->findOrFail($id);

        if ($client->orders_count > 0 || $client->recipient_orders_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف عميل لديه طلبات (أساسية أو كمستلم)'
            ], 422);
        }

        $client->addresses()->delete();
        $client->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف العميل']);
    }
}
