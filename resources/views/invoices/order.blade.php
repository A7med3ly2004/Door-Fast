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
    <title>{{ $order->order_number }} {{ $ar('فاتورة طلب #') }}</title>
    <style>
        body {
            font-family: 'XBRiyaz', 'DejaVu Sans', sans-serif;
            direction: ltr;
            text-align: right;
            padding: 20px;
            color: #333;
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
            font-size: 28px;
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
            font-size: 20px;
            color: #a10303ff;
            border-top: 2px solid #a10303ff;
            padding-top: 15px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: #000000ff;
            font-size: 12px;
            border-top: 1px solid #f0cacaff;
            padding-top: 10px;
        }

        /* LTR Overrides */
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
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
                <h1 style="color: #a10303ff; margin: 0; font-size: 28px;">DoorFast</h1>
                <p style="margin: 5px 0 0 0; color: #000000ff; font-size: 14px;">{{ $order->order_number }}
                    {{ $ar('فاتورة طلب رقم #') }}
                </p>
                <p style="margin: 5px 0 0 0; color: #000000ff; font-size: 14px;">
                    {{ $order->created_at->format('Y-m-d H:i') }} {{ $ar('التاريخ:') }}
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
            </td>
        </tr>
    </table>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td>({{ $order->client->phone ?? '' }}) {{ $ar($order->client->name ?? 'غير محدد') }}</td>
                <td class="info-label">{{ $ar('العميل المالك:') }}</td>
            </tr>
            <tr>
                <td>{{ $ar($order->client_address ?? 'غير محدد') }}</td>
                <td class="info-label">{{ $ar('عنوان العميل:') }}</td>
            </tr>
            @if($order->send_to_phone)
                <tr>
                    <td>({{ $order->send_to_phone }})</td>
                    <td class="info-label">{{ $ar('العميل المستلم:') }}</td>
                </tr>
                <tr>
                    <td>{{ $ar($order->send_to_address ?? 'غير محدد') }}</td>
                    <td class="info-label">{{ $ar('عنوان التوصيل:') }}</td>
                </tr>
            @endif
            @if($order->notes)
                <tr>
                    <td>{{ $ar($order->notes) }}</td>
                    <td class="info-label">{{ $ar('ملاحظات:') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align: left">{{ $ar('الإجمالي') }}</th>
                <th style="text-align: left">{{ $ar('سعر الوحدة') }}</th>
                <th style="text-align: center">{{ $ar('الكمية') }}</th>
                <th>{{ $ar('المتجر') }}</th>
                <th>{{ $ar('المنتج') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
                <tr>
                    <td style="text-align: left">
                        {{ $item->total ? number_format($item->total, 2) : number_format(($item->unit_price * $item->quantity), 2) }}
                        {{ $ar('ج') }}
                    </td>
                    <td style="text-align: left">{{ $item->unit_price ? number_format($item->unit_price, 2) : '-' }}
                        {{ $item->unit_price ? $ar('ج') : '' }}
                    </td>
                    <td style="text-align: center">{{ $item->quantity }}</td>
                    <td>{{ $ar($item->shop->name ?? 'بدون متجر') }}</td>
                    <td>{{ $ar($item->item_name) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center">{{ $ar('لا توجد أصناف مسجلة') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="width: 60%; float: right;">
        <table class="totals-table">
            <tr>
                <td class="value">{{ number_format($order->total - $order->delivery_fee + $order->discount, 2) }}
                    {{ $ar('ج') }}
                </td>
                <td class="label">{{ $ar('إجمالي المنتجات:') }}</td>
            </tr>
            <tr>
                <td class="value">{{ number_format($order->delivery_fee, 2) }} {{ $ar('ج') }}</td>
                <td class="label">{{ $ar('رسوم التوصيل:') }}</td>
            </tr>
            @if($order->discount > 0)
                <tr>
                    <td class="value" style="color: #dc2626">-{{ number_format($order->discount, 2) }} {{ $ar('ج') }}</td>
                    <td class="label" style="color: #dc2626">{{ $ar('الخصم:') }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="value">{{ number_format($order->total, 2) }} {{ $ar('ج') }}</td>
                <td class="label">{{ $ar('المطلوب تحصيله:') }}</td>
            </tr>
        </table>
    </div>
    <div style="clear: both;"></div>

    <div class="footer">
        <p>{{ $ar('تم إنشاء هذه الفاتورة آلياً.') }} DoorFast {{ $ar('شكراً لتعاملكم مع') }}</p>
    </div>

</body>

</html>