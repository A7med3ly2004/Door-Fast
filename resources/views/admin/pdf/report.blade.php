<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 11px; color: #1e293b; margin: 20px; }
    h1 { text-align: center; font-size: 20px; color: #001d5aff; margin-bottom: 20px; }
    h2 { font-size: 14px; color: #1e293b; margin: 16px 0 6px; border-bottom: 2px solid #f59e0b; padding-bottom: 4px; }
    .meta { text-align: left; color: #000000ff; font-size: 12px; margin-bottom: 12px; }
    .kpis { display: table; background: #cfe5f5ff; width: 100%; margin-bottom: 12px;border: 1px solid #000000ff; border-radius: 8px;}
    .kpi { display: table-cell; padding: 8px; text-align: center;}
    .kpi-label { font-size: 12px; color: #0d0d0dff; }
    .kpi-value { font-size: 16px; font-weight: bold; color: #f59e0b; }
    table { width: 100%; border-collapse: collapse; border: 1px solid #000000ff; font-size: 12px; margin-top: 8px; }
    th { background: #1e293b; color: #fff; padding: 6px 8px; }
    td { padding: 6px 8px; border-bottom: 1px solid #5b5e63ff; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { padding: 2px 6px; border-radius: 8px; font-size: 9px; }
    .delivered { background: #dcfce7; color: #166534; }
    .cancelled { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; color: #000000ff; font-size: 9px; }
</style>
</head>
<body>
<h1>تقرير الفترة العامة</h1>
<div class="meta">
    from : <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ $filters['from'] }}</span>
    &nbsp;
    To : <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ $filters['to'] }}</span>
    &nbsp; | &nbsp;
    Export Date: <span style="direction:ltr; unicode-bidi:embed; font-family:'DejaVu Sans',sans-serif;">{{ now()->format('Y-m-d H:i') }}</span>
</div>

<div class="kpis">
    <div class="kpi"><div class="kpi-label">إجمالي الطلبات</div><div class="kpi-value">{{ $orders->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">تم التوصيلة</div><div class="kpi-value" style="color:#166534">{{ $orders->where('status','delivered')->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">ملغاة</div><div class="kpi-value" style="color:#dc2626">{{ $orders->where('status','cancelled')->count() }}</div></div>
    <div class="kpi"><div class="kpi-label">الإيرادات</div><div class="kpi-value">{{ number_format($totals['revenue'], 2) }} ج</div></div>
    <div class="kpi"><div class="kpi-label">رسوم التوصيل</div><div class="kpi-value">{{ number_format($totals['delivery_fee'], 2) }} ج</div></div>
    <div class="kpi"><div class="kpi-label">الخصومات</div><div class="kpi-value">{{ number_format($totals['discount'], 2) }} ج</div></div>
</div>

<h2>تفاصيل الطلبات</h2>
<table>
    <thead>
        <tr>
            <th style="text-align:center">الحالة</th>
            <th style="text-align:center">الإجمالي</th>
            <th style="text-align:right">المندوب</th>
            <th style="text-align:right">كول سنتر</th>
            <th style="text-align:right">العميل</th>
            <th style="text-align:center">التاريخ</th>
            <th style="text-align:center">رقم الطلب</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $o)
        <tr>
            <td style="text-align:center">
                <span class="badge {{ $o->status }}">
                @switch($o->status)
                    @case('delivered') تم التوصيل @break
                    @case('cancelled') ملغي @break
                    @case('pending') باقي @break
                    @case('received') مسلم للمندوب @break
                    @default {{ $o->status }}
                @endswitch
                </span>
            </td>
            <td style="text-align:center">{{ number_format($o->total, 2) }} ج</td>
            <td style="text-align:right">{{ $o->delivery?->name ?? '—' }}</td>
            <td style="text-align:right">{{ $o->callcenter?->name ?? '—' }}</td>
            <td style="text-align:right">{{ $o->client?->name ?? '—' }}</td>
            <td style="text-align:center">{{ $o->created_at->format('Y-m-d') }}</td>
            <td style="text-align:center">{{ $o->order_number }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight:bold;background:#f8fafc">
            <td style="text-align:center"></td>
            <td style="text-align:center">{{ number_format($orders->sum('total'), 2) }} ج</td>
            <td colspan="5" style="text-align:right; padding-right: 20px;">الإجمالي ({{ $orders->count() }} طلب)</td>
        </tr>
    </tfoot>
</table>

<div class="footer">تم إنشاؤه بواسطة دور فاست — {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>

