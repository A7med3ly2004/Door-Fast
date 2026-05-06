<?php
$file = 'c:/xampp/htdocs/door-fast/app/Http/Controllers/CallCenter/OrderController.php';
$content = file_get_contents($file);

$target = <<<'EOF'
        if (!empty($request->client_address)) {
            $order->client->addresses()->firstOrCreate(
                ['address' => $request->client_address],
                ['is_default' => $order->client->addresses()->count() === 0]
            );
        }
EOF;

$replacement = <<<'EOF'
        if (!empty($request->client_address) && $order->client) {
            $order->client->addresses()->firstOrCreate(
                ['address' => $request->client_address],
                ['is_default' => $order->client->addresses()->count() === 0]
            );
        }
EOF;

if (strpos($content, $target) !== false) {
    $content = str_replace($target, $replacement, $content);
    file_put_contents($file, $content);
    echo "Updated successfully!\n";
} else {
    echo "Target string not found!\n";
}
