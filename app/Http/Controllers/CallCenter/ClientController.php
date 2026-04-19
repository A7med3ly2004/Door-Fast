<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('callcenter.clients.partials.content')->render(),
                'title'      => 'العملاء',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            if (!$request->filled('search')) {
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 15
                ]);
            }

            $query = Client::withCount('orders')->with(['addresses', 'orders' => fn($q) => $q->latest()->limit(1)])->latest();

            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));

            return response()->json($query->paginate(15)->through(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'code'          => $c->code,
                'phone'         => $c->phone,
                'phone2'        => $c->phone2,
                'addresses_count' => $c->addresses->count(),
                'orders_count'  => $c->orders_count,
                'last_order_at' => $c->orders->first()?->created_at?->toIso8601String(),
            ]));
        }

        return view('callcenter.clients.index');
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'required|string|max:30|unique:clients,phone',
            'phone2'        => 'nullable|string|max:30',
            'first_address' => 'required|string|max:500',
        ], [
            'name.required'          => 'الاسم مطلوب',
            'phone.required'         => 'رقم الهاتف مطلوب',
            'phone.unique'           => 'رقم الهاتف مسجل مسبقاً',
            'first_address.required' => 'العنوان مطلوب',
        ]);

        $client = Client::create([
            'name'   => $data['name'],
            'phone'  => $data['phone'],
            'phone2' => $data['phone2'] ?? null,
            'code'   => Client::generateCode(),
        ]);

        $client->addresses()->create([
            'address'    => $data['first_address'],
            'is_default' => true,
        ]);

        ActivityLog::log(
            event: 'client.created',
            description: 'تم إضافة عميل جديد (كول سنتر) — ' . $client->name,
            subjectType: 'client',
            subjectId: $client->id,
            subjectLabel: $client->name,
            properties: ['client_code' => $client->code, 'phone' => $client->phone]
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة العميل بنجاح', 'client' => $client]);
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => ['required', 'string', 'max:30', \Illuminate\Validation\Rule::unique('clients', 'phone')->ignore($client->id)],
            'phone2' => 'nullable|string|max:30',
        ], [
            'name.required'  => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique'   => 'رقم الهاتف مسجل مسبقاً لعميل آخر.',
        ]);

        $client->update($data);

        // Handle addresses
        if ($request->has('addresses')) {
            $existingIds = $client->addresses()->pluck('id')->toArray();
            $receivedIds = [];

            foreach ($request->addresses as $addrData) {
                if (!empty($addrData['id'])) {
                    $receivedIds[] = (int)$addrData['id'];
                    $addr = ClientAddress::where('client_id', $client->id)->find($addrData['id']);
                    if ($addr) {
                        if (!empty($addrData['_delete'])) {
                            $addr->delete();
                        } else {
                            $addr->update(['address' => $addrData['address'], 'is_default' => !empty($addrData['is_default'])]);
                        }
                    }
                } elseif (!empty($addrData['address'])) {
                    // Check limit
                    if ($client->addresses()->count() < 5) {
                        $client->addresses()->create([
                            'address'    => $addrData['address'],
                            'is_default' => !empty($addrData['is_default']),
                        ]);
                    }
                }
            }

            // Ensure at least one default address
            if ($client->addresses()->count() > 0 && !$client->addresses()->where('is_default', true)->exists()) {
                $client->addresses()->first()->update(['is_default' => true]);
            }
        }

        ActivityLog::log(
            event: 'client.updated',
            description: 'تم تحديث بيانات وعناوين العميل (كول سنتر) — ' . $client->name,
            subjectType: 'client',
            subjectId: $client->id,
            subjectLabel: $client->name,
            properties: ['client_code' => $client->code, 'phone' => $client->phone]
        );

        return response()->json(['success' => true, 'message' => 'تم تحديث بيانات وعناوين العميل']);
    }

    public function show($id)
    {
        $client = Client::with(['addresses', 'orders' => fn($q) => $q->with('delivery')->latest()->limit(5)])->findOrFail($id);

        return response()->json([
            'client' => [
                'id'          => $client->id,
                'name'        => $client->name,
                'code'        => $client->code,
                'phone'       => $client->phone,
                'phone2'      => $client->phone2,
                'created_at'  => $client->created_at->toIso8601String(),
                'addresses'   => $client->addresses->map(fn($a) => ['id' => $a->id, 'address' => $a->address, 'is_default' => $a->is_default]),
                'orders_count'=> $client->orders->count(),
                'orders'      => $client->orders->map(fn($o) => [
                    'order_number' => $o->order_number,
                    'status'       => $o->status,
                    'total'        => $o->total,
                    'created_at'   => $o->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function searchByPhone(Request $request)
    {
        $phone = $request->phone;
        $code  = $request->code;
        if (!$phone && !$code) return response()->json(['found' => false]);

        $query = Client::with('addresses');
        if ($phone) {
            $query->where('phone', $phone);
        } else {
            $query->where('code', $code);
        }
        
        $client = $query->first();

        if (!$client) return response()->json(['found' => false]);

        return response()->json([
            'found'     => true,
            'id'        => $client->id,
            'name'      => $client->name,
            'code'      => $client->code,
            'phone'     => $client->phone,
            'phone2'    => $client->phone2,
            'addresses' => $client->addresses->map(fn($a) => [
                'id'         => $a->id,
                'address'    => $a->address,
                'is_default' => $a->is_default,
            ]),
        ]);
    }
}
