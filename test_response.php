<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
auth()->loginUsingId(2);

$query = App\Models\Order::latest();
$orders = $query->paginate(15);

$paginated = $orders->through(fn($o) => [
    'id' => $o->id,
    'order_number' => $o->order_number,
]);

echo json_encode($paginated);
