@extends('layouts.admin')

@section('page-title', 'الإعدادات')

@section('content')
    <div class="section-header">
        <h2>⚙️ إعدادات النظام</h2>
    </div>

    <div class="card" style="max-width:600px">
        <div class="form-group">
            <label class="form-label">اسم الشركة</label>
            <input type="text" id="company_name" class="form-control" value="{{ $settings['company_name'] ?? '' }}"
                placeholder="دور فاست">
        </div>
        <div class="form-group">
            <label class="form-label">هاتف الشركة</label>
            <input type="text" id="company_phone" class="form-control" value="{{ $settings['company_phone'] ?? '' }}">
        </div>
        <hr class="divider">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">مدة الانتظار قبل الإرسال للمندوب (بالدقائق)</label>
                <input type="number" id="order_hold_minutes" class="form-control"
                    value="{{ $settings['order_hold_minutes'] ?? 10 }}" min="1" max="1440">
            </div>
            <div class="form-group">
                <label class="form-label">الحد الأقصى للطلبات لكل مندوب</label>
                <input type="number" id="max_orders_per_delivery" class="form-control"
                    value="{{ $settings['max_orders_per_delivery'] ?? 10 }}" min="1" max="100">
            </div>
        </div>
        <div class="form-row" style="margin-top:16px;">
            <div class="form-group">
                <label class="form-label">وقت انتظار الدلفري الاحتياطي (دقائق)</label>
                <input type="number" id="reserve_delay_minutes" class="form-control"
                    value="{{ $settings['reserve_delay_minutes'] ?? 5 }}" min="1" max="60">
                <span style="font-size:12px;color:var(--text-muted);">بعد كم دقيقة يظهر الطلب للاحتياطي إذا لم يُقبل؟</span>
            </div>
            <div class="form-group">
                <label class="form-label">نسبة الخصم لا تتجاوز (%)</label>
                <input type="number" id="max_discount_percentage" class="form-control"
                    value="{{ $settings['max_discount_percentage'] ?? 50 }}" min="0" max="100">
                <span style="font-size:12px;color:var(--text-muted);">أقصى نسبة مئوية مسموحة للخصم سواء في طلبات الأدمن أو
                    الكول سنتر</span>
            </div>
        </div>
        <div class="form-row" style="margin-top:16px;">
            <div class="form-group">
                <label class="form-label">الحد الأقصى للعهدة للمندوب</label>
                <input type="number" id="max_unsettled_limit" class="form-control"
                    value="{{ $settings['max_unsettled_limit'] ?? 500 }}" min="0">
                <span style="font-size:12px;color:var(--text-muted);">المبلغ الذي يتوقف عنده المندوب عن استلام طلبات
                    (بالجنيه)</span>
            </div>
            <div class="form-group">
                <label class="form-label">الحد الأقصى للطلبات قيد التوصيل</label>
                <input type="number" id="max_active_orders" class="form-control"
                    value="{{ $settings['max_active_orders'] ?? 3 }}" min="1">
                <span style="font-size:12px;color:var(--text-muted);">عدد الطلبات المستلمة مع المندوب في نفس الوقت</span>
            </div>
        </div>
        <hr class="divider">
        <div class="form-group">
            <label class="form-label">خدمة الرسائل (SMS)</label>
            <div class="toggle-wrap" style="margin-top:8px">
                <label class="toggle">
                    <input type="checkbox" id="sms_enabled" {{ ($settings['sms_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    <span class="toggle-slider"></span>
                </label>
                <span style="color:var(--text-muted);font-size:13px">تفعيل SMS (غير متاح حالياً)</span>
            </div>
        </div>
        <hr class="divider">
        <div style="display:flex;justify-content:flex-end">
            <button class="btn btn-primary" onclick="saveSettings()">💾 حفظ الإعدادات</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        async function saveSettings() {
            try {
                const { data } = await axios.post('{{ route("admin.settings.update") }}', {
                    company_name: document.getElementById('company_name').value,
                    company_phone: document.getElementById('company_phone').value,
                    order_hold_minutes: document.getElementById('order_hold_minutes').value,
                    max_orders_per_delivery: document.getElementById('max_orders_per_delivery').value,
                    reserve_delay_minutes: document.getElementById('reserve_delay_minutes').value,
                    max_discount_percentage: document.getElementById('max_discount_percentage').value,
                    max_unsettled_limit: document.getElementById('max_unsettled_limit').value,
                    max_active_orders: document.getElementById('max_active_orders').value,
                    sms_enabled: document.getElementById('sms_enabled').checked ? 1 : 0,
                });
                showSuccess(data.message);
            } catch (e) {
                const errors = e.response?.data?.errors;
                showError(errors ? Object.values(errors).flat().join(' | ') : 'حدث خطأ');
            }
        }
    </script>
@endpush