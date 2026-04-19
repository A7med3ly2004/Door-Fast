<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 11px; color: #1e293b; margin: 20px; }
    h1 { font-size: 18px; color: #f59e0b; margin-bottom: 4px; }
    h2 { font-size: 13px; color: #1e293b; margin: 16px 0 6px; border-bottom: 2px solid #f59e0b; padding-bottom: 4px; }
    .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
    .shop-info { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 8px; }
    th { background: #1e293b; color: #fff; padding: 6px 8px; }
    td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { padding: 2px 6px; border-radius: 8px; font-size: 9px; }
    .delivered { background: #dcfce7; color: #166534; }
    .cancelled { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; color: #94a3b8; font-size: 9px; }
    .kpis { display: table; width: 100%; margin-bottom: 12px; border-collapse: collapse; }
    .kpi { display: table-cell; border: 1px solid #e2e8f0; padding: 8px; text-align: center; }
    .kpi-label { font-size: 9px; color: #64748b; }
    .kpi-value { font-size: 16px; font-weight: bold; color: #f59e0b; }
</style>
</head>
<body>
<h1>دور فاست — تقرير هوبز المتجر</h1>
<div class="meta">من: {{ $filters['from'] }} إلى: {{ $filters['to'] }} | تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}</div>

<div class="shop-info">
    <strong>{{ $shop->name }}</strong>
    @if($shop->phone) | 📞 {{ $shop->phone }} @endif
    @if($shop->address) | 📍 {{ $shop->address }} @endif
</div>

@php
    $total = $orders->count();
    $completed = $orders->where('status','delivered')->count();
    $cancelled = $orders->where('status','cancelled')->count();
    $revenue = $orders->sum(fn($o) => $o->items->sum('total'));
@endphp

<div class="kpis">
    <div class="kpi"><div class="kpi-label">إجمالي الطلبات</div><div class="kpi-value">{{ $total }}</div></div>
    <div class="kpi"><div class="kpi-label">مُوصَّلة</div><div class="kpi-value" style="color:#166534">{{ $completed }}</div></div>
    <div class="kpi"><div class="kpi-label">ملغاة</div><div class="kpi-value" style="color:#dc2626">{{ $cancelled }}</div></div>
    <div class="kpi"><div class="kpi-label">إجمالي المشتريات</div><div class="kpi-value">{{ number_format($revenue, 2) }} ج</div></div>
</div>

<h2>أكثر الأصناف طلباً</h2>
<table>
    <thead><tr><th>الصنف</th><th>الكمية</th><th>القيمة الإجمالية</th></tr></thead>
    <tbody>
        @foreach($topItems as $item)
        <tr>
            <td>{{ $item->item_name }}</td>
            <td>{{ $item->total_qty }}</td>
            <td>{{ number_format($item->total_value, 2) }} ج</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight:bold;background:#f8fafc">
            <td>الإجمالي</td>
            <td>{{ $topItems->sum('total_qty') }}</td>
            <td>{{ number_format($topItems->sum('total_value'), 2) }} ج</td>
        </tr>
    </tfoot>
</table>

<h2>الطلبات</h2>
<table>
    <thead>
        <tr><th>رقم الطلب</th><th>التاريخ</th><th>العميل</th><th>الأصناف</th><th>الإجمالي</th><th>الحالة</th></tr>
    </thead>
    <tbody>
        @foreach($orders as $order)
        <tr>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->created_at->format('Y-m-d') }}</td>
            <td>{{ $order->client?->name ?? '—' }}</td>
            <td style="font-size:9px">{{ $order->items->map(fn($i) => $i->item_name . '×' . $i->quantity)->join(', ') }}</td>
            <td>{{ number_format($order->items->sum('total'), 2) }} ج</td>
            <td><span class="badge {{ $order->status }}">
                @switch($order->status)
                    @case('delivered') مُوصَّل @break
                    @case('cancelled') ملغي @break
                    @case('pending') باقي @break
                    @case('received') مُسلَّم @break
                @endswitch
            </span></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">تم إنشاؤه بواسطة دور فاست — {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>
