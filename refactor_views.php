<?php
$views = [
    ["path" => "admin/reports/index.blade.php", "layout" => "admin", "title" => "التقارير", "partial" => "admin.reports.partials.content"],
    ["path" => "admin/report-hops/index.blade.php", "layout" => "admin", "title" => "تقارير المتاجر", "partial" => "admin.report-hops.partials.content"],
    ["path" => "delivery/dashboard.blade.php", "layout" => "delivery", "title" => "لوحة التحكم", "partial" => "delivery.partials.dashboard_content"],
    ["path" => "delivery/orders/new.blade.php", "layout" => "delivery", "title" => "طلبات جديدة", "partial" => "delivery.orders.partials.new_content"],
    ["path" => "delivery/orders/received.blade.php", "layout" => "delivery", "title" => "الطلبات المستلمة", "partial" => "delivery.orders.partials.received_content"],
    ["path" => "delivery/orders/delivered.blade.php", "layout" => "delivery", "title" => "الطلبات الموصلة", "partial" => "delivery.orders.partials.delivered_content"],
    ["path" => "reserve_delivery/dashboard.blade.php", "layout" => "reserve_delivery", "title" => "إحصائياتي", "partial" => "reserve_delivery.partials.dashboard_content"],
    ["path" => "reserve_delivery/orders/new.blade.php", "layout" => "reserve_delivery", "title" => "طلبات جديدة", "partial" => "reserve_delivery.orders.partials.new_content"],
    ["path" => "reserve_delivery/orders/received.blade.php", "layout" => "reserve_delivery", "title" => "طلبات مستلمة", "partial" => "reserve_delivery.orders.partials.received_content"],
    ["path" => "reserve_delivery/orders/delivered.blade.php", "layout" => "reserve_delivery", "title" => "طلبات موصلة", "partial" => "reserve_delivery.orders.partials.delivered_content"],
    ["path" => "callcenter/stats/index.blade.php", "layout" => "callcenter", "title" => "إحصائياتي", "partial" => "callcenter.stats.partials.content"],
    ["path" => "callcenter/clients/index.blade.php", "layout" => "callcenter", "title" => "العملاء", "partial" => "callcenter.clients.partials.content"],
    ["path" => "callcenter/shops/index.blade.php", "layout" => "callcenter", "title" => "المتاجر", "partial" => "callcenter.shops.partials.content"],
    ["path" => "callcenter/delivery/index.blade.php", "layout" => "callcenter", "title" => "إدارة المناديب", "partial" => "callcenter.delivery.partials.content"],
    ["path" => "callcenter/orders/index.blade.php", "layout" => "callcenter", "title" => "الطلبات", "partial" => "callcenter.orders.partials.index_content"],
    ["path" => "callcenter/orders/create.blade.php", "layout" => "callcenter", "title" => "إنشاء طلب", "partial" => "callcenter.orders.partials.create_content"],
];

foreach ($views as $v) {
    $filePath = __DIR__ . "/resources/views/" . $v["path"];
    if (!file_exists($filePath)) {
        echo "Missing {$v['path']}\n";
        continue;
    }
    
    $titleSection = in_array($v["layout"], ["delivery", "reserve_delivery"]) ? "page_title" : "page-title";
    
    $content = "@extends('layouts.{$v["layout"]}')\n\n";
    $content .= "@section('{$titleSection}', '{$v["title"]}')\n\n";
    $content .= "@section('content')\n";
    $content .= "    @include('{$v["partial"]}')\n";
    $content .= "@endsection\n";
    
    file_put_contents($filePath, $content);
    echo "Refactored {$v["path"]}\n";
}
