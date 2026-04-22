{{-- Single log row — used by the Blade initial render --}}
@php
    $rowClass = match(true) {
        str_starts_with($log->event, 'order.')       => 'al-row-order',
        str_starts_with($log->event, 'client.')      => 'al-row-client',
        str_starts_with($log->event, 'user.')        => 'al-row-user',
        str_starts_with($log->event, 'shop.')        => 'al-row-shop',
        str_starts_with($log->event, 'treasury.')    => 'al-row-treasury',
        str_starts_with($log->event, 'shift.')       => 'al-row-shift',
        str_starts_with($log->event, 'settlement.')  => 'al-row-settlement',
        default                                      => '',
    };
    $subjectLabels = [
        'order'      => 'طلب',
        'client'     => 'عميل',
        'user'       => 'مستخدم',
        'shop'       => 'متجر',
        'treasury'   => 'خزينة',
        'shift'      => 'وردية',
        'settlement' => 'تسوية',
    ];
@endphp
<tr class="{{ $rowClass }}">
    <td>
        <div class="al-desc">{{ $log->description }}</div>
    </td>
    <td>
        @if($log->subject_label)
            <div class="al-sub">{{ $log->subject_label }}</div>
        @else
            —
        @endif
    </td>
    <td>
        <div style="font-size:13px; text-align: center;">{{ $subjectLabels[$log->subject_type] ?? ($log->subject_type ?? '—') }}</div>
    </td>
    <td style="font-size:13px;font-weight:600; text-align: center;">{{ $log->causer?->name ?? '—' }}</td>
    <td style="text-align: center;"><span class="badge {{ $log->causer_role_badge }}">{{ $log->causer_role_label }}</span></td>
    <td>
        <div class="al-time-main" style="direction:ltr;text-align:center;">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
        <div class="al-time-ago" style="text-align:center;">{{ $log->created_at->diffForHumans() }}</div>
    </td>
</tr>
