<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $tx->activityLog?->id ?? '-' }}</title>
    <style>
        body {
            font-family: 'XBRiyaz', 'DejaVu Sans', sans-serif;
            direction: ltr;
            text-align: right;
            padding: 10px;
            color: #333;
            font-size: 12px;
        }

        @page {
            size: A5 portrait;
            margin: 10mm;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #a10303ff;
            margin: 0;
            font-size: 22px;
        }

        .header p {
            margin: 5px 0 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
            text-align: right;
        }

        .info-label {
            font-weight: bold;
            color: #475569;
            width: 150px;
            white-space: nowrap;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        .totals-table td {
            padding: 8px;
            text-align: right;
        }

        .totals-table .label {
            font-weight: bold;
            color: #000000ff;
            width: 150px;
        }

        .totals-table .value {
            font-weight: bold;
            text-align: right;
        }

        .totals-table .grand-total .label,
        .totals-table .grand-total .value {
            font-size: 18px;
            color: #a10303ff;
            border-top: 2px solid #a10303ff;
            padding-top: 10px;
        }

        .signatures {
            width: 100%;
            margin-top: 80px;
            text-align: center;
            direction: ltr;
        }

        .signatures td {
            width: 50%;
            font-weight: bold;
            color: #333;
        }

        .signatures .lines td {
            padding-top: 40px;
            color: #94a3b8;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: #000000ff;
            font-size: 12px;
            border-top: 1px solid #f0cacaff;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <table style="width: 100%; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        <tr>
            <td style="width: 30%; text-align: left; vertical-align: top;">
                @php
                    $logoPath = public_path('DF_logo_2026.PNG');
                    if (file_exists($logoPath)) {
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoSrc = 'data:image/png;base64,' . $logoData;
                    } else {
                        $logoSrc = '';
                    }
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" style="max-height: 80px; max-width: 120px;" />
                @endif
            </td>
            <td style="width: 40%; text-align: center;">
                <h1 style="color: #a10303ff; margin: 0; font-size: 22px;">DoorFast</h1>
                <p style="margin: 5px 0 0 0; color: #000000ff; font-size: 14px;">
                    إيصال كشف حساب خاص
                </p>
                <p style="margin: 5px 0 0 0; color: #000000ff; font-size: 12px;">
                    {{ now()->format('Y-m-d H:i') }} التاريخ:
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
            </td>
        </tr>
    </table>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td style="font-weight: bold; color: #a10303ff; font-size: 16px;">{{ $tx->activityLog?->id ?? '-' }}</td>
                <td class="info-label">رقم العملية:</td>
            </tr>
            <tr>
                <td style="color: #000000ff; font-size: 14px;">{{ $tx->transaction_date->format('Y-m-d') }}</td>
                <td class="info-label">تاريخ المعاملة:</td>
            </tr>
            <tr>
                <td style="color: #000000ff; font-size: 14px;">{{ $typeLabel }}</td>
                <td class="info-label">نوع المعاملة:</td>
            </tr>
            @if($relatedUser)
            <tr>
                <td style=" font-size: 14px; color: #000000ff;">{{ $relatedUser }}</td>
                <td class="info-label">الطرف الثاني:</td>
            </tr>
            @endif
            <tr>
                <td style="color: #000000ff; font-size: 14px;">{{ $tx->description ?? 'لا توجد ملاحظات' }}</td>
                <td class="info-label">الوصف / الملاحظة:</td>
            </tr>
            <tr>
                <td style="color: #000000ff; font-size: 14px;">{{ $tx->createdBy?->name ?? $admin->name }}</td>
                <td class="info-label">سجلت بواسطة:</td>
            </tr>
        </table>
    </div>

    <table class="totals-table">
        <tr class="grand-total">
            <td class="value">
                ج.م <span dir="ltr">{{ number_format((float) $tx->amount, 2) }}</span>
            </td>
            <td class="label">قيمة المعاملة : </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>توقيع المدير</td>
            <td>توقيع الطرف الثاني {{ $tx->direction === 'debit' ? '( مستلم من )' : '( مدفوع الي )' }}</td>
        </tr>
        <tr class="lines">
            <td>..............</td>
            <td>..............</td>
        </tr>
    </table>

    <div class="footer">
        تم إنشاؤه بواسطة نظام دور فاست — {{ now()->format('Y-m-d H:i:s') }}
    </div>

</body>

</html>
