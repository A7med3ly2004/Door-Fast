<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size:13px; color:#1a1a1a; background:#fff; direction:rtl; }
    .page { width:100%; max-width:540px; margin:0 auto; padding:30px 20px; }
    .header { text-align:center; margin-bottom:24px; border-bottom:2px solid #1e293b; padding-bottom:16px; }
    .header .logo { font-size:22px; font-weight:900; color:#1e293b; letter-spacing:1px; }
    .header .subtitle { font-size:12px; color:#64748b; margin-top:4px; }
    .title-box { background:#f1f5f9; border-right:4px solid #1e293b; padding:10px 14px; margin-bottom:20px; border-radius:4px; }
    .title-box h2 { font-size:15px; font-weight:700; color:#0f172a; }
    .info-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
    .info-table tr td { padding:8px 12px; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .info-table tr td:first-child { color:#64748b; font-weight:600; width:40%; }
    .info-table tr td:last-child { font-weight:700; color:#0f172a; }
    .type-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; }
    .type-pay     { background:#fef3c7; color:#92400e; }
    .type-receive { background:#dcfce7; color:#166534; }
    .type-expense { background:#fee2e2; color:#991b1b; }
    .amount-box { text-align:center; background:#0f172a; color:#f1f5f9; border-radius:8px; padding:16px; margin-bottom:20px; }
    .amount-box .label { font-size:12px; color:#94a3b8; margin-bottom:4px; }
    .amount-box .value { font-size:28px; font-weight:900; }
    .footer { text-align:center; color:#94a3b8; font-size:11px; margin-top:24px; padding-top:12px; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="logo">Door Fast</div>
        <div class="subtitle">كشف حساب خاص — {{ $admin->name }}</div>
    </div>

    <div class="title-box">
        <h2>{{ $typeLabel }} — رقم العملية #{{ $tx->id }}</h2>
    </div>

    <div class="amount-box">
        <div class="label">المبلغ</div>
        <div class="value">{{ number_format((float)$tx->amount, 2) }} ج</div>
    </div>

    <table class="info-table">
        <tr>
            <td>رقم العملية</td>
            <td>#{{ $tx->id }}</td>
        </tr>
        <tr>
            <td>نوع العملية</td>
            <td>
                @php
                    $cls = match($tx->type) {
                        'admin_pay'     => 'type-pay',
                        'admin_receive' => 'type-receive',
                        'admin_expense' => 'type-expense',
                        default         => ''
                    };
                @endphp
                <span class="type-badge {{ $cls }}">{{ $typeLabel }}</span>
            </td>
        </tr>
        <tr>
            <td>التاريخ</td>
            <td>{{ $tx->transaction_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>الوصف / الملاحظة</td>
            <td>{{ $tx->description ?? '—' }}</td>
        </tr>
        @if($relatedUser)
        <tr>
            <td>الموظف المرتبط</td>
            <td>{{ $relatedUser }}</td>
        </tr>
        @endif
        <tr>
            <td>الرصيد بعد العملية</td>
            <td>{{ number_format((float)$tx->balance_after, 2) }} ج</td>
        </tr>
        <tr>
            <td>تاريخ التسجيل</td>
            <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="footer">
        DoorFast — تم الإنشاء بتاريخ {{ now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
