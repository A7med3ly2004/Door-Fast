<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\ = \App\Models\Setting::businessDayRange()[0];
\ = \->copy()->startOfMonth();
\ = \->copy()->endOfMonth();
\ = \App\Models\Setting::businessDayRange(\)[0];
\ = \App\Models\Setting::businessDayRange(\)[1];
echo \ . ' to ' . \;

