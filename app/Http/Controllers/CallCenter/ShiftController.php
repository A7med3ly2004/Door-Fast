<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\CallcenterShift;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function toggle(Request $request)
    {
        $callcenter = auth()->user();
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $activeShift = CallcenterShift::where('callcenter_id', $callcenter->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        if ($activeShift) {
            $activeShift->update([
                'ended_at' => Carbon::now(),
                'is_active' => false,
            ]);
            $msg = 'تم إنهاء الوردية بنجاح';
        } else {
            CallcenterShift::create([
                'callcenter_id' => $callcenter->id,
                'date' => $businessDate,
                'started_at' => Carbon::now(),
                'is_active' => true,
            ]);
            $msg = 'تم بدء الوردية بنجاح';
        }

        ActivityLog::log(
            event: 'callcenter.shift_toggled_self',
            description: $msg . ' — ' . $callcenter->name,
            subjectType: 'user',
            subjectId: $callcenter->id,
            subjectLabel: $callcenter->name
        );

        return response()->json(['success' => true, 'message' => $msg, 'is_active' => !$activeShift]);
    }

    public function status(Request $request)
    {
        $callcenter = auth()->user();
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $shift = CallcenterShift::where('callcenter_id', $callcenter->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'is_active' => $shift ? true : false,
            'started_at' => $shift ? $shift->started_at : null
        ]);
    }
}
