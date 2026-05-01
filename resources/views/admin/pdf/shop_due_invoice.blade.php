@php
    $arabic = new \ArPHP\I18N\Arabic();
    $ar = function ($str) use ($arabic) {
        if (preg_match('/[أ-ي]/ui', $str)) {
            return $arabic->utf8Glyphs($str);
        }
        return $str;
    };
@endphp
<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $ar('فاتورة مستحق -') }} {{ $ar($shop->name) }}</title>
    <style>
        body {
            font-family: 'XBRiyaz', 'DejaVu Sans', sans-serif;
            direction: ltr;
            text-align: right;
            padding: 20px;
            color: #333;
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
            width: 120px;
            white-space: nowrap;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border: 1px solid #e1cbcbff;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #ffafafff;
            padding: 10px;
            text-align: right;
            font-weight: bold;
            border-bottom: 2px solid #cbd5e1;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: right;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px;
            text-align: right;
        }

        .totals-table .label {
            font-weight: bold;
            color: #000000ff;
            width: 60%;
        }

        .totals-table .value {
            font-weight: bold;
            width: 40%;
        }

        .totals-table .grand-total .label,
        .totals-table .grand-total .value {
            font-size: 18px;
            color: #a10303ff;
            border-top: 2px solid #a10303ff;
            padding-top: 15px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: #000000ff;
            font-size: 11px;
            border-top: 1px solid #f0cacaff;
            padding-top: 10px;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .signatures {
            width: 100%;
            margin-top: 80px;
            text-align: center;
        }

        .signatures td {
            width: 33.33%;
            font-weight: bold;
        }

        .signatures .lines td {
            padding-top: 40px;
            color: #94a3b8;
        }
    </style>
</head>

<body style="font-size: 12px;">

    <table style="width: 100%; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
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
                    <img src="{{ $logoSrc }}" style="max-height: 70px; max-width: 110px;" />
                @endif
            </td>
            <td style="width: 40%; text-align: center;">
                <h1 style="color: #a10303ff; margin: 0; font-size: 24px;">DoorFast</h1>
                <p style="margin: 5px 0 0 0; color: #000000ff; font-size: 13px;">
                    {{ $ar('فاتورة مستحق -') }} {{ $ar($shop->name) }}
                </p>
                <p style="margin: 5px 0 0 0; color: #000000ff; font-size: 13px;">
                    {{ now()->format('Y-m-d H:i') }} {{ $ar('تاريخ الإصدار:') }}
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
            </td>
        </tr>
    </table>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td style="direction: ltr; text-align: right;">{{ $filters['from'] }}</td>
                <td class="info-label">{{ $ar('الفترة من:') }}</td>
            </tr>
            <tr>
                <td style="direction: ltr; text-align: right;">{{ $filters['to'] }}</td>
                <td class="info-label">{{ $ar('الفترة إلى:') }}</td>
            </tr>
            @if($shop->phone)
                <tr>
                    <td style="direction: ltr; text-align: right;">{{ $shop->phone }}</td>
                    <td class="info-label">{{ $ar('رقم الهاتف:') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <h3 style="text-align: right; color: #475569; font-size: 15px;">{{ $ar('تفاصيل المبيعات (الطلبات الموصله)') }}</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align: center">{{ $ar('الإجمالي') }}</th>
                <th style="text-align: center">{{ $ar('سعر الوحدة') }}</th>
                <th style="text-align: center">{{ $ar('الكمية') }}</th>
                <th style="text-align: center">{{ $ar('المتجر') }}</th>
                <th style="text-align: right">{{ $ar('الصنف') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td style="text-align: center">
                        {{ number_format($item->total_value, 2) }} {{ $ar('ج') }}
                    </td>
                    <td style="text-align: center">
                        {{ number_format($item->unit_price, 2) }} {{ $ar('ج') }}
                    </td>
                    <td style="text-align: center">{{ $item->total_qty }}</td>
                    <td style="text-align: center">{{ $ar($item->shop->name ?? 'بدون متجر') }}</td>
                    <td style="text-align: right">{{ $ar($item->item_name) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center">{{ $ar('لا توجد أصناف مباعة في هذه الفترة') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="width: 60%; float: right;">
        <table class="totals-table">
            <tr>
                <td class="value">{{ $ar('ج') }} {{ number_format($revenue, 2) }}</td>
                <td class="label">{{ $ar('الإيراد الإجمالي:') }}</td>
            </tr>
            <tr>
                <td class="value">{{ number_format($discountPercent, 2) }}%</td>
                <td class="label">{{ $ar('نسبة الخصم:') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="value">{{ $ar('ج') }} {{ number_format($discountValue, 2) }}</td>
                <td class="label">{{ $ar('المبلغ المستحق:') }}</td>
            </tr>
        </table>
    </div>
    <div style="clear: both;"></div>

    <table class="signatures">
        <tr>
            <td>{{ $ar('توقيع الجهة المستلمة') }}</td>
            <td>{{ $ar('توقيع الناقل') }}</td>
            <td>{{ $ar('توقيع المدير') }}</td>
        </tr>
        <tr class="lines">
            <td>..................................</td>
            <td>..................................</td>
            <td>..................................</td>
        </tr>
    </table>

    <div class="footer">
        <p>{{ $ar('تم إنشاء هذه الفاتورة آلياً بواسطة نظام') }} DoorFast</p>
    </div>

</body>

</html>