<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private array $keys = [
        'company_name',
        'company_phone',
        'order_hold_minutes',
        'sms_enabled',
        'reserve_delay_minutes',
        'max_discount_percentage',
        'max_unsettled_limit',
        'max_active_orders',
    ];

    public function index()
    {
        $settings = [];
        foreach ($this->keys as $key) {
            $settings[$key] = Setting::get($key);
        }

        // Defaults
        $settings['order_hold_minutes']      ??= '10';
        $settings['sms_enabled']             ??= '0';
        $settings['reserve_delay_minutes']   ??= '5';
        $settings['max_discount_percentage'] ??= '50';
        $settings['max_unsettled_limit']     ??= '500';
        $settings['max_active_orders']       ??= '3';

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.settings.partials.content', compact('settings'))->render(),
                'title'      => 'الإعدادات',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name'            => 'nullable|string|max:255',
            'company_phone'           => 'nullable|string|max:30',
            'order_hold_minutes'      => 'nullable|integer|min:1|max:1440',
            'sms_enabled'             => 'nullable|boolean',
            'reserve_delay_minutes'   => 'nullable|integer|min:1|max:60',
            'max_discount_percentage' => 'nullable|integer|min:0|max:100',
            'max_unsettled_limit'     => 'nullable|numeric|min:0',
            'max_active_orders'       => 'nullable|integer|min:1|max:100',
        ]);

        foreach ($this->keys as $key) {
            Setting::set($key, $data[$key] ?? ($key === 'sms_enabled' ? '0' : null));
        }

        return response()->json(['success' => true, 'message' => 'تم حفظ الإعدادات']);
    }
}
