<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::whereIn('role', ['callcenter', 'delivery', 'reserve_delivery'])
    ->where(function($q) {
        $q->whereNull('code')->orWhere('code', '');
    })
    ->get();

echo "Found " . $users->count() . " users without codes.\n";

foreach($users as $u) {
    if ($u->role === 'callcenter') {
        $u->code = User::generateUniqueRoleCode('C');
    } else {
        $u->code = User::generateUniqueRoleCode('D');
    }
    $u->save();
    echo "Updated {$u->name} ({$u->role}) with code {$u->code}\n";
}
echo "Done.\n";
