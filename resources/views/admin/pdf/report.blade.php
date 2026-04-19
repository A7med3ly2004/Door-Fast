<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 11px; color: #1e293b; margin: 20px; }
    h1 { font-size: 18px; color: #f59e0b; margin-bottom: 4px; }
    h2 { font-size: 13px; color: #1e293b; margin: 16px 0 6px; border-bottom: 2px solid #f59e0b; padding-bottom: 4px; }
    .meta { color: #64748b; font-size: 10px; margin-bottom: 16px; }
    .kpis { display: table; width: 100%; margin-bottom: 16px; }
    .kpi { display: table-cell; border: 1px solid #e2e8f0; padding: 10px; text-align: center; }
    .kpi-label { font-size: 9px; color: #64748b; }
    .kpi-value { font-size: 18px; font-weight: bold; color: #f59e0b; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
    th { background: #1e293b; color: #fff; padding: 6px 8px; }
    td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { padding: 2px 6px; border-radius: 8px; font-size: 9px; }
    .delivered { background: #dcfce7; color: #166534; }
    .cancelled { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; color: #94a3b8; font-size: 9px; }
    .progress-bar-bg { background: #e2e8f0; height: 6px; border-radius: 3px; }
    .progress-bar { background: #22c55e; height: 6px; border-radius: 3px; }
</style>
</head>
<body>
<h1>دور فاست — تقرير الفترة</h1>
<div class="meta">
    من: {{ $filters['from'] }} إلى: {{ $filters['to'] }}
    | إجمالي الطلبات: {{ $orders->count() }}
    | تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}
</div>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">إجمالي الطلبات</div><div class="kpi-value">{{ $orders->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">مُوصَّلة</div><div class="kpi-value" style="color:#166534">{{ $orders->where('status','delivered')->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">ملغاة</div><div class="kpi-value" style="color:#dc2626">{{ $orders->where('status','cancelled')->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">الإيرادات</div><div class="kpi-value">{{ number_format($totals['revenue'], 2) }} ج</div></div>
    <div class="kpi"><div class="kpi-label">رسوم التوصيل</div><div class="kpi-value" style="font-size:14px">{{ number_format($totals['delivery_fee'], 2) }} ج</div></div>
    <div class="kpi"><div class="kpi-label">الخصومات</div><div class="kpi-value" style="font-size:14px">{{ number_format($totals['discount'], 2) }} ج</div></div>
</div>

<h2>تفاصيل الطلبات</h2>
<table>
    <thead>
        <tr><th>رقم الطلب</th><th>التاريخ</th><th>العميل</th><th>كول سنتر</th><th>المندوب</th><th>الإجمالي</th><th>الحالة</th></tr>
    </thead>
    <tbody>
        @foreach($orders as $o)
        <tr>
            <td>{{ $o->order_number }}</td>
            <td>{{ $o->created_at->format('Y-m-d') }}</td>
            <td>{{ $o->client?->name ?? '—' }}</td>
            <td>{{ $o->callcenter?->name ?? '—' }}</td>
            <td>{{ $o->delivery?->name ?? '—' }}</td>
            <td>{{ number_format($o->total, 2) }} ج</td>
            <td><span class="badge {{ $o->status }}">
                @switch($o->status)
                    @case('delivered') مُوصَّل @break
                    @case('cancelled') ملغي @break
                    @default {{ $o->status }}
                @endswitch
            </span></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight:bold;background:#f8fafc">
            <td colspan="5">الإجمالي ({{ $orders->count() }} طلب)</td>
            <td>{{ number_format($orders->sum('total'), 2) }} ج</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="footer">تم إنشاؤه بواسطة دور فاست — {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>
