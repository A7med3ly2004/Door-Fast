<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$orders = App\Models\Order::latest()->take(3)->get(['id', 'order_number', 'callcenter_id', 'created_at']);
echo "Recent Orders:\n";
foreach($orders as $o) {
    echo "ID: {$o->id}, Num: {$o->order_number}, CC User: {$o->callcenter_id}, Created: {$o->created_at}\n";
}
echo "Today (now()->toDateString()): " . now()->toDateString() . "\n";
echo "DB Date function check: ";
$dbQ = App\Models\Order::query()->whereDate('created_at', now()->toDateString())->count();
echo "$dbQ matching directly.\n";
