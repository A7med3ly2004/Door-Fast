@extends('layouts.reserve_delivery')

@section('page_title', 'إحصائياتي')

@section('content')
    @if(auth()->user()->cc_shift_enabled)
        @include('reserve_delivery.partials.dashboard_content')
    @else
        <div class="day-not-started-card">
            <div class="day-not-started-icon">⏳</div>
            <h2>لم يبدأ يوم عملك بعد</h2>
            <p>يُرجى مراجعة الكول سنتر أو الأدمن لتفعيل وردية اليوم.</p>
        </div>
        <style>
            .day-not-started-card {
                max-width: 480px;
                margin: 80px auto;
                padding: 40px 24px;
                background: var(--card-bg, #fff);
                border: 1px solid var(--border, #e6e6e6);
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            }
            .day-not-started-icon {
                font-size: 56px;
                line-height: 1;
                margin-bottom: 12px;
            }
            .day-not-started-card h2 {
                margin: 0 0 8px;
                font-size: 22px;
                color: var(--text, #222);
            }
            .day-not-started-card p {
                margin: 0;
                color: var(--text-muted, #777);
                font-size: 15px;
            }
        </style>
    @endif
@endsection
