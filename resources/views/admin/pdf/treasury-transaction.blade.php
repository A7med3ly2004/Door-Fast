<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; font-size: 12px; color: #1e293b; margin: 30px; }
    h1   { font-size: 20px; color: #f59e0b; margin-bottom: 4px; }
    .meta { color: #64748b; font-size: 10px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
    .section-title { font-size: 13px; font-weight: bold; color: #1e293b; border-bottom: 2px solid #f59e0b; padding-bottom: 4px; margin: 20px 0 12px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table tr td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
    .info-table tr td:first-child { color: #64748b; width: 40%; font-weight: bold; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
    .badge-income     { background: #dcfce7; color: #166534; }
    .badge-expense    { background: #fee2e2; color: #991b1b; }
    .badge-settlement { background: #fef9c3; color: #854d0e; }
    .badge-dain       { background: #e0e7ff; color: #3730a3; }
    .badge-discount   { background: #fee2e2; color: #991b1b; }
    .amount-value { font-size: 22px; font-weight: bold; color: #f59e0b; }
    .footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 9px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<h1>دور فاست — إيصال معاملة مالية</h1>
<div class="meta">
    رقم العملية: #{{ $transaction->id }}
    | تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}
</div>

<div class="section-title">تفاصيل المعاملة</div>
<table class="info-table">
    <tr>
        <td>رقم العملية</td>
        <td><strong>#{{ $transaction->id }}</strong></td>
    </tr>
    <tr>
        <td>التاريخ</td>
        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td>نوع المعاملة</td>
        <td>
            @php
                $badgeClass = match($transaction->type) {
                    'income'     => 'badge-income',
                    'expense'    => 'badge-expense',
                    'settlement' => 'badge-settlement',
                    'dain'       => 'badge-dain',
                    'discount'   => 'badge-discount',
                    default      => 'badge-expense',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $transaction->type_label }}</span>
        </td>
    </tr>
    <tr>
        <td>المبلغ</td>
        <td><span class="amount-value">{{ number_format((float) $transaction->amount, 2) }}</span> ج.م</td>
    </tr>
    <tr>
        <td>بواسطة</td>
        <td>{{ $transaction->by_whom }}</td>
    </tr>
    <tr>
        <td>سُجِّل بواسطة</td>
        <td>{{ $transaction->recordedBy?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td>ملاحظة</td>
        <td>{{ $transaction->note ?? '—' }}</td>
    </tr>
    <tr>
        <td>تاريخ الإنشاء</td>
        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
    </tr>
</table>

<div class="footer">تم إنشاؤه بواسطة دور فاست — {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>
