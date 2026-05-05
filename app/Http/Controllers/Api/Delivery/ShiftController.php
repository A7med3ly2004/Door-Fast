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

        $shift = Shift::create([
            'delivery_id' => $delivery->id,
            'date'        => now()->toDateString(),
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
}
