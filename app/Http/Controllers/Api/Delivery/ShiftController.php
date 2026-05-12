<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * POST /api/delivery/shift/start
     * Start a new shift for the authenticated delivery user.
     */
    public function start(Request $request)
    {
        $delivery = $request->user();

        // Check if an open shift already exists (ended_at IS NULL)
        $existing = Shift::where('delivery_id', $delivery->id)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'لديك شفت نشط بالفعل',
            ], 422);
        }

        [$startOfToday] = Setting::businessDayRange();
        $businessDate   = $startOfToday->toDateString();

        $shift = Shift::create([
            'delivery_id' => $delivery->id,
            'date'        => $businessDate,
            'started_at'  => Carbon::now(),
            'is_active'   => true,
        ]);

        ActivityLog::log(
            event:        'shift.started',
            description:  'بدء وردية — ' . $delivery->name,
            subjectType:  'user',
            subjectId:    $delivery->id,
            subjectLabel: $delivery->name,
            properties:   ['shift_id' => $shift->id],
            causerId:     $delivery->id
        );

        return response()->json([
            'success'    => true,
            'shift_id'   => $shift->id,
            'started_at' => $shift->started_at->toIso8601String(),
        ]);
    }

    /**
     * POST /api/delivery/shift/end
     * End the currently open shift.
     */
    public function end(Request $request)
    {
        $delivery = $request->user();

        // Find the open shift
        $shift = Shift::where('delivery_id', $delivery->id)
            ->whereNull('ended_at')
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد شفت نشط لإنهائه',
            ], 422);
        }

        // Check no received orders remain
        $pendingCount = Order::where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->count();

        if ($pendingCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكنك إنهاء الوردية. لديك {$pendingCount} طلب(ات) لم يتم توصيلها بعد.",
            ], 422);
        }

        $shift->update([
            'ended_at'  => Carbon::now(),
            'is_active' => false,
        ]);

        ActivityLog::log(
            event:        'shift.ended',
            description:  'إنهاء وردية — ' . $delivery->name,
            subjectType:  'user',
            subjectId:    $delivery->id,
            subjectLabel: $delivery->name,
            properties:   ['shift_id' => $shift->id],
            causerId:     $delivery->id
        );

        return response()->json([
            'success'  => true,
            'ended_at' => $shift->ended_at->toIso8601String(),
        ]);
    }

    /**
     * GET /api/delivery/shift/status
     * Return the current shift status for the authenticated user.
     */
    public function status(Request $request)
    {
        $delivery = $request->user();

        $shift = Shift::where('delivery_id', $delivery->id)
            ->whereNull('ended_at')
            ->first();

        return response()->json([
            'success'      => true,
            'shift_active' => (bool) $shift,
            'shift_id'     => $shift?->id,
            'started_at'   => $shift?->started_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/delivery/shift/times or /api/reserve/shift/times
     * Return detailed timing of the latest shift.
     */
    public function shiftTimes(Request $request)
    {
        $delivery = $request->user();

        // Get the latest shift regardless of status
        $shift = Shift::where('delivery_id', $delivery->id)
            ->latest('id')
            ->first();

        $durationMinutes = null;
        if ($shift && $shift->started_at && $shift->ended_at) {
            $durationMinutes = (int) $shift->started_at->diffInMinutes($shift->ended_at);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'has_active_shift' => (bool) ($shift && !$shift->ended_at),
                'shift_start'      => $shift?->started_at?->format('Y-m-d H:i:s'),
                'shift_end'        => $shift?->ended_at?->format('Y-m-d H:i:s'),
                'duration_minutes' => $durationMinutes,
            ]
        ]);
    }
}
