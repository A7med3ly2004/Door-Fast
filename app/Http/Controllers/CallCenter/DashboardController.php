<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('callcenter.orders.create');
    }
}