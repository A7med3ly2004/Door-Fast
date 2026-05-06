<?php
$file = 'c:/xampp/htdocs/door-fast/app/Http/Controllers/CallCenter/OrderController.php';
$content = file_get_contents($file);

$target = <<<'EOF'
        $order->update([
            'delivery_id'         => $request->delivery_id ?: null,
            'is_delivery_chosen'  => $isDeliveryChosen,
            'client_address'      => $request->client_address,
            'send_to_phone'       => $request->send_to_phone ?: null,
            'send_to_phone2'      => $request->send_to_phone2 ?: null,
            'send_to_address'     => $request->send_to_address ?: null,
            'notes'               => $request->notes,
            'delivery_fee'        => $deliveryFee,
            'discount'            => $discount,
            'discount_type'       => $discountType,
            'total'               => $total,
            'status'              => $isDeliveryChosen ? 'received' : 'pending',
            'accepted_at'         => $isDeliveryChosen ? Carbon::now() : null,
            'sent_to_delivery_at' => $newSentAt,
        ]);

        if (!empty($request->send_to_phone)) {
            $sendToClient = \App\Models\Client::where('phone', $request->send_to_phone)->first();
            if ($sendToClient) {
                if (!empty($request->send_to_phone2) && $sendToClient->phone2 !== $request->send_to_phone2) {
                    $sendToClient->update(['phone2' => $request->send_to_phone2]);
                }
                if (!empty($request->send_to_name) && $sendToClient->name !== $request->send_to_name) {
                    $sendToClient->update(['name' => $request->send_to_name]);
                }
                $order->update(['send_to_client_id' => $sendToClient->id]);
            } else {
                $sendToClient = \App\Models\Client::create([
                    'phone' => $request->send_to_phone,
                    'phone2' => $request->send_to_phone2,
                    'name' => $request->send_to_name ?? 'Unnamed',
                    'code' => \App\Models\Client::generateCode()
                ]);
                $sendToClient->addresses()->create([
                    'address' => $request->send_to_address ?? '',
                    'is_default' => true,
                ]);
                $order->update(['send_to_client_id' => $sendToClient->id]);
            }
        } else {
            $order->update(['send_to_client_id' => null]);
        }
EOF;

$replacement = <<<'EOF'
        $order->update([
            'delivery_id'         => $request->delivery_id ?: null,
            'is_delivery_chosen'  => $isDeliveryChosen,
            'client_address'      => $request->client_address,
            'send_to_phone'       => $request->send_to_phone ?: null,
            'send_to_phone2'      => $request->send_to_phone2 ?: null,
            'send_to_address'     => $request->send_to_address ?: null,
            'notes'               => $request->notes,
            'delivery_fee'        => $deliveryFee,
            'discount'            => $discount,
            'discount_type'       => $discountType,
            'total'               => $total,
            'status'              => $isDeliveryChosen ? 'received' : 'pending',
            'accepted_at'         => $isDeliveryChosen ? Carbon::now() : null,
            'sent_to_delivery_at' => $newSentAt,
        ]);

        if (!empty($request->client_address)) {
            $order->client->addresses()->firstOrCreate(
                ['address' => $request->client_address],
                ['is_default' => $order->client->addresses()->count() === 0]
            );
        }

        if (!empty($request->send_to_phone)) {
            $sendToClient = \App\Models\Client::where('phone', $request->send_to_phone)->first();
            if ($sendToClient) {
                if (!empty($request->send_to_phone2) && $sendToClient->phone2 !== $request->send_to_phone2) {
                    $sendToClient->update(['phone2' => $request->send_to_phone2]);
                }
                if (!empty($request->send_to_name) && $sendToClient->name !== $request->send_to_name) {
                    $sendToClient->update(['name' => $request->send_to_name]);
                }
                
                if (!empty($request->send_to_address)) {
                    $sendToClient->addresses()->firstOrCreate(
                        ['address' => $request->send_to_address],
                        ['is_default' => $sendToClient->addresses()->count() === 0]
                    );
                }
                
                $order->update(['send_to_client_id' => $sendToClient->id]);
            } else {
                $sendToClient = \App\Models\Client::create([
                    'phone' => $request->send_to_phone,
                    'phone2' => $request->send_to_phone2,
                    'name' => $request->send_to_name ?? 'Unnamed',
                    'code' => \App\Models\Client::generateCode()
                ]);
                $sendToClient->addresses()->create([
                    'address' => $request->send_to_address ?? '',
                    'is_default' => true,
                ]);
                $order->update(['send_to_client_id' => $sendToClient->id]);
            }
        } else {
            $order->update(['send_to_client_id' => null]);
        }
EOF;

if (strpos($content, $target) !== false) {
    $content = str_replace($target, $replacement, $content);
    file_put_contents($file, $content);
    echo "Updated successfully!\n";
} else {
    echo "Target string not found!\n";
}
