<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

auth()->loginUsingId(2);

$from = now()->toDateString();
$to = now()->toDateString();

$query = App\Models\Order::with(['client', 'delivery', 'items'])
    ->where('callcenter_id', auth()->id())
    ->latest();

$query->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);

// Let's get the SQL
echo "SQL: " . $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";

$orders = $query->paginate(15);
echo "Count: " . count($orders->items()) . "\n";
