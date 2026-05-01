<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 11px; color: #1e293b; margin: 20px; }
    h1 { text-align: center; font-size: 20px; color: #001d5aff; margin-bottom: 20px; }
    .meta { text-align: left; color: #000000ff; font-size: 12px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; border: 1px solid #000000ff; font-size: 11px; margin-top: 8px; }
    th { background: #1e293b; color: #fff; padding: 6px 8px; }
    td { padding: 6px 8px; border-bottom: 1px solid #5b5e63ff; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { padding: 2px 6px; border-radius: 8px; font-size: 9px; }
    .pending { background: #fef3c7; color: #92400e; }
    .received { background: #dbeafe; color: #1e40af; }
    .delivered { background: #dcfce7; color: #166534; }
    .cancelled { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; color: #000000ff; font-size: 9px; }
</style>
</head>
<body>
<h1>تقرير الطلبات</h1>
<div class="meta">
    from : <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ $filters['from'] ?? '—' }}</span>
    &nbsp;
    To : <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ $filters['to'] ?? '—' }}</span>
    &nbsp; | &nbsp;
    Total Orders: <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ $orders->count() }}</span>
    &nbsp; | &nbsp;
    Export Date: <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ now()->format('Y-m-d H:i') }}</span>
</div>

<table>
    <thead>
        <tr>
            <th style="text-align:center">الحالة</th>
            <th style="text-align:center">الإجمالي</th>
            <th style="text-align:center">الخصم</th>
            <th style="text-align:center">توصيل</th>
            <th style="text-align:right">المندوب</th>
            <th style="text-align:right">كول سنتر</th>
            <th style="text-align:right">العميل</th>
            <th style="text-align:center">التاريخ</th>
            <th style="text-align:center">رقم الطلب</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $order)
        <tr>
            <td style="text-align:center">
                <span class="badge {{ $order->status }}">
                    @switch($order->status)
                        @case('pending') باقي @break
                        @case('received') مسلم للمندوب @break
                        @case('delivered') تم التوصيل @break
                        @case('cancelled') ملغي @break
                    @endswitch
                </span>
            </td>
            <td style="text-align:center">{{ number_format($order->total, 2) }} ج</td>
            <td style="text-align:center">{{ number_format($order->discount, 2) }} ج</td>
            <td style="text-align:center">{{ number_format($order->delivery_fee, 2) }} ج</td>
            <td style="text-align:right">{{ $order->delivery?->name ?? '—' }}</td>
            <td style="text-align:right">{{ $order->callcenter?->name ?? '—' }}</td>
            <td style="text-align:right">{{ $order->client?->name ?? '—' }}</td>
            <td style="text-align:center">{{ $order->created_at->format('Y-m-d') }}</td>
            <td style="text-align:center">{{ $order->order_number }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight:bold; background:#f8fafc">
            <td style="text-align:center"></td>
            <td style="text-align:center">{{ number_format($orders->sum('total'), 2) }} ج</td>
            <td style="text-align:center">{{ number_format($orders->sum('discount'), 2) }} ج</td>
            <td style="text-align:center">{{ number_format($orders->sum('delivery_fee'), 2) }} ج</td>
            <td colspan="5" style="text-align:right; padding-right:20px">الإجمالي ({{ $orders->count() }} طلب)</td>
        </tr>
    </tfoot>
</table>

<div class="footer">تم إنشاؤه بواسطة دور فاست — {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>
