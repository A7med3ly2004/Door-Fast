<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Setting;
use Carbon\Carbon;

abstract class BaseDeliveryShiftController extends Controller
{
    /**
     * Hook called after shift is successfully started — override for extra logging
     */
    protected function afterStart(mixed $delivery, Shift $shift): void
    {
        // default: no-op
    }

    /**
     * Hook called after shift is successfully ended — override for extra logging
     */
    protected function afterEnd(mixed $delivery, Shift $shift): void
    {
        // default: no-op
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared methods
    // ─────────────────────────────────────────────────────────────────────────

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

        $businessDate = Setting::businessDayRange()[0]->toDateString();

        $existingShift = Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        if ($existingShift) {
            return response()->json(['success' => false, 'message' => 'لديك شفت نشط بالفعل']);
        }

        $shift = Shift::create([
            'delivery_id' => $delivery->id,
            'date'        => $businessDate,
            'started_at'  => Carbon::now(),
            'is_active'   => true,
        ]);

        $this->afterStart($delivery, $shift);

        return response()->json(['success' => true, 'message' => 'تم بدء الوردية بنجاح']);
    }

    public function end(Request $request)
    {
        $delivery     = auth()->user();
        $businessDate = Setting::businessDayRange()[0]->toDateString();

        $shift = Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        if ($shift) {
            $shift->update([
                'ended_at'  => Carbon::now(),
                'is_active' => false,
            ]);

            $this->afterEnd($delivery, $shift);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'لا يوجد شفت نشط لإنهائه']);
    }

    public function status(Request $request)
    {
        $delivery     = auth()->user();
        $businessDate = Setting::businessDayRange()[0]->toDateString();

        $shift = Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'is_active'  => (bool) $shift,
            'started_at' => $shift ? $shift->started_at : null,
        ]);
    }
}
