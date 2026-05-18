<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class VersionController extends Controller
{
    /**
     * GET /api/app/version
     * لا يحتاج authentication — متاح لجميع الطلبات
     */
    public function index()
    {
        return response()->json([
            'minimum_version' => Setting::get('app_minimum_version', '1.0.0'),
            'latest_version'  => Setting::get('app_latest_version',  '1.0.0'),
            'update_url'      => Setting::get('app_update_url',       ''),
        ]);
    }
}
