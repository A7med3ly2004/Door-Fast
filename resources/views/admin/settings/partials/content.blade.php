{{-- Admin Settings SPA partial --}}
<div class="section-header"><h2>إعدادات النظام</h2></div>
<div class="card" style="max-width:90%; margin: 0 auto;">
    <div class="form-group"><label class="form-label">اسم الشركة</label><input type="text" id="company_name" class="form-control" value="{{ $settings['company_name'] ?? '' }}" placeholder="دور فاست"></div>
    <div class="form-group"><label class="form-label">هاتف الشركة</label><input type="text" id="company_phone" class="form-control" value="{{ $settings['company_phone'] ?? '' }}"></div>
    <hr class="divider" style="display:none">
    
    <div class="form-row">
        <div class="form-group"><label class="form-label">وقت انتظار الدلفري الاحتياطي (دقائق)</label><input type="number" id="reserve_delay_minutes" class="form-control" value="{{ $settings['reserve_delay_minutes'] ?? 5 }}" min="1" max="60"><span style="font-size:12px;color:var(--text-muted)">بعد كم دقيقة يظهر الطلب للاحتياطي؟</span></div>
        <div class="form-group"><label class="form-label">نسبة الخصم لا تتجاوز (%)</label><input type="number" id="max_discount_percentage" class="form-control" value="{{ $settings['max_discount_percentage'] ?? 50 }}" min="0" max="100"><span style="font-size:12px;color:var(--text-muted)">أقصى نسبة مئوية مسموحة للخصم سواء في طلبات الأدمن أو الكول سنتر</span></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">الحد الأقصى للطلبات قيد التوصيل</label><input type="number" id="max_active_orders" class="form-control" value="{{ $settings['max_active_orders'] ?? 3 }}" min="1"><span style="font-size:12px;color:var(--text-muted)">عدد الطلبات المستلمة مع المندوب في نفس الوقت</span></div>
        <div class="form-group"><label class="form-label">مدة الانتظار قبل الإرسال (دقائق)</label><input type="number" id="order_hold_minutes" class="form-control" value="{{ $settings['order_hold_minutes'] ?? 10 }}" min="1" max="1440"></div>
    </div>
    
    <div class="section-header" style="margin-top: 24px; margin-bottom: 16px;"><h4>إعدادات التنبيهات</h4></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">تأخير التوصيل (دقائق)</label><input type="number" id="notif_delay_delivery_mins" class="form-control" value="{{ $settings['notif_delay_delivery_mins'] ?? 20 }}" min="1"></div>
        <div class="form-group"><label class="form-label">طلب غير مقبول من المندوب (دقائق)</label><input type="number" id="notif_unaccepted_mins" class="form-control" value="{{ $settings['notif_unaccepted_mins'] ?? 20 }}" min="1"></div>
    </div>
    
    <hr class="divider" style="display:none">
    <div class="form-group"><label class="form-label">خدمة الرسائل (SMS)</label>
        <div class="toggle-wrap" style="margin-top:8px"><label class="toggle"><input type="checkbox" id="sms_enabled" {{ ($settings['sms_enabled'] ?? '0') == '1' ? 'checked' : '' }}><span class="toggle-slider"></span></label><span style="color:var(--text-muted);font-size:13px">تفعيل SMS (غير متاح حالياً)</span></div>
    </div>
    <hr class="divider" style="display:none">
    <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary" onclick="saveSettings()">حفظ الإعدادات</button></div>
</div>
<script>
async function saveSettings() {
    try {
        const { data } = await axios.post('{{ route("admin.settings.update") }}', {
            company_name: document.getElementById('company_name').value,
            company_phone: document.getElementById('company_phone').value,
            order_hold_minutes: document.getElementById('order_hold_minutes').value,
            reserve_delay_minutes: document.getElementById('reserve_delay_minutes').value,
            max_discount_percentage: document.getElementById('max_discount_percentage').value,
            max_active_orders: document.getElementById('max_active_orders').value,

            notif_delay_delivery_mins: document.getElementById('notif_delay_delivery_mins').value,
            notif_unaccepted_mins: document.getElementById('notif_unaccepted_mins').value,
            sms_enabled: document.getElementById('sms_enabled').checked ? 1 : 0,
        });
        showSuccess(data.message);
    } catch(e) { showError(e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : 'حدث خطأ'); }
}
</script>
