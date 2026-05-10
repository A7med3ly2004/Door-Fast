<div class="section-header">
    <h2>إدارة الكول سنتر</h2>
    <div style="display:flex;gap:10px;align-items:center;">
        <button class="btn btn-success" onclick="exportCCExcel()" style="background:#217346;color:#fff;">تصدير Excel</button>
        <button class="btn btn-primary" onclick="openModal('modal-add-cc')">إضافة موظف</button>
    </div>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align: right;">الاسم</th>
                    <th style="text-align: center;">الكود</th>
                    <th style="text-align: center;">اسم المستخدم</th>
                    <th style="text-align: center;">الهاتف</th>
                    <th style="text-align: center;">حالة الحساب</th>
                    <th style="text-align: center;">حالة الوردية</th>
                    <th style="text-align: center;">أنشأ اليوم</th>
                    <th style="text-align: center;">إيراد اليوم</th>
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $cc)
                    <tr>
                        <td style="text-align: right;font-size: 15px;"><strong>{{ $cc['name'] }}</strong></td>
                        <td style="text-align: center;font-size: 12px;"><span class="badge badge-gray">{{ $cc['code'] ?? '—' }}</span></td>
                        <td style="text-align: center;"><code style="color:var(--yellow);font-size: 15px;">{{ $cc['username'] }}</code></td>
                        <td style="text-align: center;font-size: 15px;">{{ $cc['phone'] ?? '—' }}</td>
                        <td style="text-align: center;">
                            <button id="status-btn-{{ $cc['id'] }}"
                                onclick="toggleActive({{ $cc['id'] }}, this, {{ json_encode($cc) }})"
                                style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;
                                {{ $cc['is_active'] ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);' }}"
                                data-active="{{ $cc['is_active'] ? '1' : '0' }}">
                                {{ $cc['is_active'] ? '✓ نشط' : '✗ غير نشط' }}
                            </button>
                        </td>
                        <td style="text-align: center;">
                            <button id="shift-btn-{{ $cc['id'] }}"
                                onclick="toggleShiftCC({{ $cc['id'] }}, this)"
                                style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;
                                {{ $cc['shift_active'] ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);' }}"
                                data-active="{{ $cc['shift_active'] ? '1' : '0' }}">
                                {{ $cc['shift_active'] ? '⏱ تعمل الآن' : '⏸ متوقفة' }}
                            </button>
                        </td>
                        <td style="text-align: center;">{{ $cc['created'] }}</td>
                        <td style="text-align: center;">{{ number_format($cc['revenue'], 2) }} ج</td>
                        <td style="text-align: center;">
                            <div style="display:flex;gap:6px;justify-content: center;">
                                <button class="btn btn-sm btn-secondary"
                                    onclick="openEdit({{ json_encode($cc) }})">تعديل</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">لا موظفين</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="modal-add-cc">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ إضافة موظف كول سنتر</h3><button class="btn-close" onclick="closeModal('modal-add-cc')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">كود الموظف</label><input id="add-code" type="text"
                        class="form-control" placeholder="تلقائي..." readonly style="background: var(--bg-light); cursor: not-allowed;"></div>
                <div class="form-group"><label class="form-label">اسم المستخدم *</label><input id="add-username"
                        type="text" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">كلمة المرور *</label><input id="add-password"
                        type="password" class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف</label><input id="add-phone" type="text"
                        class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-cc')">إلغاء</button>
            <button class="btn btn-primary" onclick="addCC()">حفظ</button>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="modal-edit-cc">
    <div class="modal">
        <div class="modal-header">
            <h3>تعديل موظف كول سنتر</h3><button class="btn-close" onclick="closeModal('modal-edit-cc')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-name" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">كود الموظف</label><input id="edit-code" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف</label><input id="edit-phone" type="text"
                        class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">كلمة مرور جديدة (اختياري)</label><input
                        id="edit-password" type="password" class="form-control"></div>
                <div class="form-group"><label class="form-label">الحالة</label>
                    <select id="edit-active" class="form-select">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-cc')">إلغاء</button>
            <button class="btn btn-warning" onclick="openCCIncentiveModal()"
                    style="background:#f59e0b;color:#fff;border:none;">
                🏆 إعدادات شرائح الحوافز
            </button>
            <button class="btn btn-primary" onclick="saveCC()">حفظ التعديلات</button>
        </div>
    </div>
</div>

{{-- CC Incentive Slices Modal --}}
<div class="modal-overlay" id="modal-cc-incentive-slices">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>🏆 شرائح الحوافز — <span id="cc-inc-name"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-cc-incentive-slices')">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:15px">
                تنبيه: ستبدأ الشريحة الأولى من رقم 1، وكل شريحة تالية تتبع نهاية التي تسبقها.
                الحساب يعتمد على عدد الطلبات المُنشأة في يوم العمل.
            </p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:100px">رقم الشريحة</th>
                            <th>من (عدد طلبات)</th>
                            <th>إلى (عدد طلبات)</th>
                            <th>المبلغ (لكل طلب بالجنيه)</th>
                        </tr>
                    </thead>
                    <tbody id="cc-slices-body"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-cc-incentive-slices')">رجوع</button>
            <button class="btn btn-primary" onclick="confirmCCIncentiveSlices()">موافق</button>
        </div>
    </div>
</div>



<script>
    (function () {
        'use strict';

        let _ccTempSlices = [];

        window.openCCIncentiveModal = function () {
            renderCCSlicesForm();
            openModal('modal-cc-incentive-slices');
        };

        function renderCCSlicesForm() {
            const body = document.getElementById('cc-slices-body');
            body.innerHTML = '';

            let slices = _ccTempSlices;
            if (!slices || slices.length === 0) {
                slices = [
                    { from: 1,  to: 20,     amount: 0 },
                    { from: 21, to: 40,     amount: 0 },
                    { from: 41, to: 60,     amount: 0 },
                    { from: 61, to: 80,     amount: 0 },
                    { from: 81, to: 999999, amount: 0 },
                ];
            }

            for (let i = 1; i <= 5; i++) {
                const s       = slices[i - 1] || { from: 0, to: 0, amount: 0 };
                const fromVal = (i === 1) ? 1 : (parseInt(slices[i - 2].to) + 1);

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>الشريحة ${i}</td>
                    <td><input type="number" class="form-control cc-slice-from" data-idx="${i - 1}" value="${fromVal}" readonly disabled></td>
                    <td>
                        ${i < 5
                            ? `<input type="number" class="form-control cc-slice-to" data-idx="${i - 1}" value="${s.to}" oninput="updateCCSlicesRanges()">`
                            : `<input type="text" class="form-control" value="∞ (إلى ما لا نهاية)" disabled>`
                        }
                    </td>
                    <td><input type="number" class="form-control cc-slice-amount" data-idx="${i - 1}" value="${s.amount}" step="0.1"></td>
                `;
                body.appendChild(tr);
            }
        }

        window.updateCCSlicesRanges = function () {
            const sliceToInputs   = document.querySelectorAll('.cc-slice-to');
            const sliceFromInputs = document.querySelectorAll('.cc-slice-from');
            
            for (let i = 0; i < sliceToInputs.length; i++) {
                const currentTo = parseInt(sliceToInputs[i].value) || 0;
                
                // تحديث خانة "من" للشريحة التالية
                if (sliceFromInputs[i + 1]) {
                    sliceFromInputs[i + 1].value = currentTo + 1;
                }
            }
        };

        window.confirmCCIncentiveSlices = function () {
            const sliceToInputs    = document.querySelectorAll('.cc-slice-to');
            const sliceAmtInputs   = document.querySelectorAll('.cc-slice-amount');
            const newSlices        = [];
            let lastTo             = 0;

            for (let i = 0; i < 5; i++) {
                const toVal  = i < 4 ? parseInt(sliceToInputs[i]?.value || 0) : 999999;
                const amount = parseFloat(sliceAmtInputs[i]?.value || 0);
                const from   = lastTo + 1;

                if (i < 4 && toVal <= lastTo) {
                    showError(`الشريحة ${i + 1}: يجب أن يكون "إلى" أكبر من ${lastTo}`);
                    return;
                }

                newSlices.push({ from, to: toVal, amount });
                lastTo = toVal;
            }

            _ccTempSlices = newSlices;
            closeModal('modal-cc-incentive-slices');
            showSuccess('تم حفظ الشرائح مؤقتاً — اضغط "حفظ التعديلات" لتأكيدها');
        };

        window.addCC = async function () {
            try {
                const { data } = await axios.post('{{ route("admin.callcenter.store") }}', {
                    name: document.getElementById('add-name').value,
                    username: document.getElementById('add-username').value,
                    password: document.getElementById('add-password').value,
                    phone: document.getElementById('add-phone').value,
                    code: document.getElementById('add-code').value,
                });
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');

                if (typeof navigate === 'function') {
                    closeModal('modal-add-cc');
                    navigate('{{ route("admin.callcenter.index") }}');
                } else {
                    window.location.reload();
                }
            } catch (e) {
                const err = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : 'حدث خطأ';
                if (typeof showError === 'function') showError(err);
                else if (typeof showToast === 'function') showToast(err, 'error');
            }
        };

        window.openEdit = function (cc) {
            document.getElementById('edit-id').value = cc.id;
            document.getElementById('edit-name').value = cc.name;
            document.getElementById('edit-code').value = cc.code ?? '';
            document.getElementById('edit-phone').value = cc.phone ?? '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-active').value = cc.is_active ? '1' : '0';
            _ccTempSlices = cc.incentive_slices || [];
            document.getElementById('cc-inc-name').textContent = cc.name;
            openModal('modal-edit-cc');
        };

        window.saveCC = async function () {
            const id = document.getElementById('edit-id').value;
            try {
                const { data } = await axios.put(`/admin/callcenter/${id}`, {
                    name: document.getElementById('edit-name').value,
                    phone: document.getElementById('edit-phone').value,
                    code: document.getElementById('edit-code').value,
                    password: document.getElementById('edit-password').value,
                    is_active: document.getElementById('edit-active').value,
                    incentive_slices:  _ccTempSlices,
                });
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');

                if (typeof navigate === 'function') {
                    closeModal('modal-edit-cc');
                    navigate('{{ route("admin.callcenter.index") }}');
                } else {
                    window.location.reload();
                }
            } catch (e) {
                if (typeof showError === 'function') showError('حدث خطأ');
                else if (typeof showToast === 'function') showToast('حدث خطأ', 'error');
            }
        };

        window.toggleActive = async function (id, btn, cc) {
            const isCurrentlyActive = btn.dataset.active === '1';
            const newState = isCurrentlyActive ? 0 : 1;
            applyStatusBtn(btn, newState);
            try {
                const { data } = await axios.put(`/admin/callcenter/${id}`, { ...cc, is_active: newState });
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');
            } catch (e) {
                applyStatusBtn(btn, isCurrentlyActive ? 1 : 0);
                if (typeof showError === 'function') showError('حدث خطأ');
                else if (typeof showToast === 'function') showToast('حدث خطأ', 'error');
            }
        };

        function applyStatusBtn(btn, active) {
            btn.dataset.active = active ? '1' : '0';
            if (active) {
                btn.style.background = 'rgba(34,197,94,.15)';
                btn.style.color = 'var(--success)';
                btn.textContent = '✓ نشط';
            } else {
                btn.style.background = 'rgba(220,38,38,.12)';
                btn.style.color = 'var(--red)';
                btn.textContent = '✗ غير نشط';
            }
        }

        window.toggleShiftCC = async function (id, btn) {
            const isCurrentlyActive = btn.dataset.active === '1';
            const newState = isCurrentlyActive ? 0 : 1;
            applyShiftBtn(btn, newState);
            try {
                const { data } = await axios.patch(`/admin/callcenter/${id}/toggle-shift`);
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');
            } catch (e) {
                applyShiftBtn(btn, isCurrentlyActive ? 1 : 0);
                if (typeof showError === 'function') showError('حدث خطأ');
                else if (typeof showToast === 'function') showToast('حدث خطأ', 'error');
            }
        };

        function applyShiftBtn(btn, active) {
            btn.dataset.active = active ? '1' : '0';
            if (active) {
                btn.style.background = 'rgba(34,197,94,.15)';
                btn.style.color = 'var(--success)';
                btn.textContent = '⏱ تعمل الآن';
            } else {
                btn.style.background = 'rgba(220,38,38,.12)';
                btn.style.color = 'var(--red)';
                btn.textContent = '⏸ متوقفة';
            }
        }

        window.exportCCExcel = function () {
            const rows = @json($agents);
            const columns = [
                { header: 'الاسم',        key: 'name',         width: 22 },
                { header: 'الكود',        key: 'code',         width: 12 },
                { header: 'اسم المستخدم', key: 'username',     width: 16 },
                { header: 'الهاتف',       key: 'phone',        width: 16 },
                { header: 'حالة الحساب',  key: 'is_active',    width: 14 },
                { header: 'حالة الوردية',  key: 'shift_active', width: 14 },
                { header: 'أنشأ اليوم',   key: 'created',      width: 14 },
                { header: 'إيراد اليوم',  key: 'revenue',      width: 16 },
            ];
            const mapped = rows.map(cc => ({
                ...cc,
                is_active:    cc.is_active    ? 'نشط'    : 'غير نشط',
                shift_active: cc.shift_active ? 'نشطة'   : 'متوقفة',
                revenue:      parseFloat(cc.revenue || 0).toFixed(2),
            }));
            exportToExcel(mapped, columns, 'callcenter-' + new Date().toISOString().slice(0, 10), 'الكول سنتر');
            if (typeof showSuccess === 'function') showSuccess('تم التصدير');
        };

    })();
</script>