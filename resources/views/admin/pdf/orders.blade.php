<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face { font-family: 'Arial'; }
    body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 11px; color: #1e293b; margin: 20px; }
    h1 { font-size: 18px; color: #f59e0b; margin-bottom: 4px; }
    .meta { color: #64748b; font-size: 10px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #1e293b; color: #fff; padding: 8px; font-size: 10px; }
    td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .pending { background: #fef3c7; color: #92400e; }
    .received { background: #dbeafe; color: #1e40af; }
    .delivered { background: #dcfce7; color: #166534; }
    .cancelled { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; color: #94a3b8; font-size: 9px; }
</style>
</head>
<body>
<h1>دور فاست — تقرير الطلبات</h1>
<div class="meta">
    @if(!empty($filters['from'])) من: {{ $filters['from'] }} @endif
    @if(!empty($filters['to'])) إلى: {{ $filters['to'] }} @endif
    | إجمالي: {{ $orders->count() }} طلب
    | تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}
</div>

<table>
    <thead>
        <tr>
            <th>رقم الطلب</th>
            <th>التاريخ</th>
            <th>العميل</th>
            <th>كول سنتر</th>
            <th>المندوب</th>
            <th>رسوم التوصيل</th>
            <th>الخصم</th>
            <th>الإجمالي</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $order)
        <tr>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->created_at->format('Y-m-d') }}</td>
            <td>{{ $order->client?->name ?? '—' }}</td>
            <td>{{ $order->callcenter?->name ?? '—' }}</td>
            <td>{{ $order->delivery?->name ?? '—' }}</td>
            <td>{{ number_format($order->delivery_fee, 2) }} ج</td>
            <td>{{ number_format($order->discount, 2) }} {{ $order->discount_type === 'percent' ? '%' : 'ج' }}</td>
            <td><strong>{{ number_format($order->total, 2) }} ج</strong></td>
            <td>
                <span class="badge {{ $order->status }}">
                    @switch($order->status)
                        @case('pending') باقي @break
                        @case('received') مُسلَّم @break
                        @case('delivered') مُوصَّل @break
                        @case('cancelled') ملغي @break
                    @endswitch
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="font-weight:bold">الإجمالي</td>
            <td>{{ number_format($orders->sum('delivery_fee'), 2) }} ج</td>
            <td>{{ number_format($orders->sum('discount'), 2) }} ج</td>
            <td><strong>{{ number_format($orders->sum('total'), 2) }} ج</strong></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="footer">تم إنشاؤه بواسطة دور فاست — {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>
