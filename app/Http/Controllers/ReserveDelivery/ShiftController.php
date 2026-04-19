<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Setting;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function start(Request $request)
    {
        $delivery = auth()->user();

        // ── Guard: CC must have enabled the shift first ───────────
        if (! $delivery->cc_shift_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك بدء الوردية. يجب على الكول سنتر تفعيل وردية أولاً.',
            ], 403);
        }

        // Ensure no active shift today
        $existingShift = Shift::where('delivery_id', $delivery->id)
            ->whereDate('date', Carbon::today())
            ->where('is_active', true)
            ->first();

        if ($existingShift) {
            return response()->json(['success' => false, 'message' => 'لديك شفت نشط بالفعل']);
        }


        Shift::create([
            'delivery_id' => $delivery->id,
            'date'        => Carbon::today(),
            'started_at'  => Carbon::now(),
            'is_active'   => true,
        ]);

        return response()->json(['success' => true, 'message' => 'تم بدء الوردية بنجاح']);
    }

    public function end(Request $request)
    {
        $delivery = auth()->user();
        
        $shift = Shift::where('delivery_id', $delivery->id)
            ->whereDate('date', Carbon::today())
            ->where('is_active', true)
            ->first();

        if ($shift) {
            $shift->update([
                'ended_at' => Carbon::now(),
                'is_active' => false,
            ]);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'لا يوجد شفت نشط لإنهائه']);
    }

    public function status(Request $request)
    {
        $delivery = auth()->user();
        $shift = Shift::where('delivery_id', $delivery->id)
            ->whereDate('date', Carbon::today())
            ->where('is_active', true)
            ->first();

        return response()->json([
            'is_active' => $shift ? true : false,
            'started_at' => $shift ? $shift->started_at : null
        ]);
    }
}
