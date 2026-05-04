<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Base\BaseDeliveryShiftController;
use App\Models\ActivityLog;
use App\Models\Shift;

class ShiftController extends BaseDeliveryShiftController
{
    protected function afterStart(mixed $delivery, Shift $shift): void
    {
        ActivityLog::log(
            event: 'shift.started',
            description: 'تم بدء وردية لمندوب — ' . $delivery->name,
            subjectType: 'shift',
            subjectId: $delivery->id,
            subjectLabel: $delivery->name,
            properties: []
        );
    }

    protected function afterEnd(mixed $delivery, Shift $shift): void
    {
        ActivityLog::log(
            event: 'shift.ended',
            description: 'تم إنهاء وردية لمندوب — ' . $delivery->name,
            subjectType: 'shift',
            subjectId: $delivery->id,
            subjectLabel: $delivery->name
        );
    }
}
