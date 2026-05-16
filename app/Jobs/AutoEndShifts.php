<?php

namespace App\Jobs;

use App\Models\CallcenterShift;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class AutoEndShifts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        [$startOfToday] = Setting::businessDayRange();
        $currentBusinessDate = $startOfToday->toDateString();

        $staleShifts = Shift::where('is_active', true)
            ->where('date', '<', $currentBusinessDate)
            ->get();

        // ✅ ورديات الكول سنتر المنتهية
        $staleCallcenterShifts = CallcenterShift::where('is_active', true)
            ->where('date', '<', $currentBusinessDate)
            ->get();

        if ($staleShifts->isEmpty() && $staleCallcenterShifts->isEmpty()) {
            return;
        }

        Log::info("AutoEndShifts: closing {$staleShifts->count()} delivery shifts and {$staleCallcenterShifts->count()} callcenter shifts for business date {$currentBusinessDate}");

        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster'      => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS'       => true,
                    'curl_options' => [
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error('AutoEndShifts: Pusher init failed: ' . $e->getMessage());
            $pusher = null;
        }

        $affectedDeliveryIds = [];

        // ── ورديات المناديب ──────────────────────────────────────────
        foreach ($staleShifts as $shift) {
            $shift->update([
                'is_active' => false,
                'ended_at'  => $shift->started_at->copy()->endOfDay(),
            ]);

            $affectedDeliveryIds[] = $shift->delivery_id;

            // أرسل event للموبايل
            if ($pusher) {
                try {
                    $pusher->trigger(
                        'delivery.' . $shift->delivery_id,
                        'shift.updated',
                        ['user_id' => $shift->delivery_id, 'status' => 'ended']
                    );
                } catch (\Exception $e) {
                    Log::error("AutoEndShifts: mobile push failed for delivery {$shift->delivery_id}: " . $e->getMessage());
                }
            }
        }

        // ── ورديات الكول سنتر ────────────────────────────────────────
        foreach ($staleCallcenterShifts as $ccShift) {
            $ccShift->update([
                'is_active' => false,
                'ended_at'  => $ccShift->started_at->copy()->endOfDay(),
            ]);
            Log::info("AutoEndShifts: closed callcenter shift #{$ccShift->id} for callcenter_id={$ccShift->callcenter_id}");
        }

        // ── أرسل event واحد للويب (الأدمن والكول سنتر) ──────────────
        if ($pusher && !empty($affectedDeliveryIds)) {
            try {
                $pusher->trigger(
                    'admin-notifications',
                    'shifts.auto_ended',
                    ['delivery_ids' => array_unique($affectedDeliveryIds)]
                );
                Log::info('AutoEndShifts: sent shifts.auto_ended to admin-notifications');
            } catch (\Exception $e) {
                Log::error('AutoEndShifts: admin push failed: ' . $e->getMessage());
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AutoEndShifts Job failed', ['error' => $e->getMessage()]);
    }
}