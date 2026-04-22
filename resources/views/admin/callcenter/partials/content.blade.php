<div class="section-header">
    <h2>إدارة الكول سنتر</h2>
    <button class="btn btn-primary" onclick="openModal('modal-add-cc')">إضافة موظف</button>
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
                    <th style="text-align: center;">نشط</th>
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
                        <td style="text-align: center;">{{ $cc['created'] }}</td>
                        <td style="text-align: center;">{{ number_format($cc['revenue'], 2) }} ج</td>
                        <td style="text-align: center;">
                            <div style="display:flex;gap:6px;justify-content: center;">
                                <button class="btn btn-sm btn-secondary"
                                    onclick="openEdit({{ json_encode($cc) }})">تعديل</button>
                                <button class="btn btn-sm btn-info"
                                    onclick="viewPerf({{ $cc['id'] }}, '{{ addslashes($cc['name']) }}')">احصائيات</button>
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
                        class="form-control" placeholder="تلقائي إذا ترك فارغاً"></div>
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
            <button class="btn btn-primary" onclick="saveCC()">حفظ التعديلات</button>
        </div>
    </div>
</div>

{{-- Performance Modal --}}
<div class="modal-overlay" id="modal-perf">
    <div class="modal">
        <div class="modal-header">
            <h3>أداء:<span id="perf-name"></span></h3><button class="btn-close"
                onclick="closeModal('modal-perf')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="perf-id">
            <div class="filter-bar" style="margin-bottom:16px">
                <input type="date" id="perf-from" class="form-control">
                <input type="date" id="perf-to" class="form-control">
                <button class="btn btn-primary" onclick="loadPerf()">بحث</button>
            </div>
            <div id="perf-body"></div>
        </div>
    </div>
</div>


<script>
    (function () {
        'use strict';

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

        window.viewPerf = function (id, name) {
            document.getElementById('perf-id').value = id;
            document.getElementById('perf-name').textContent = name;
            openModal('modal-perf');
            loadPerf();
        };

        window.loadPerf = async function () {
            const id = document.getElementById('perf-id').value;
            try {
                const { data } = await axios.get(`/admin/callcenter/${id}/performance`, {
                    params: { from: document.getElementById('perf-from').value, to: document.getElementById('perf-to').value }
                });
                document.getElementById('perf-body').innerHTML = `
                <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
                    <div class="kpi-card"><div class="kpi-label">إجمالي الطلبات</div><div class="kpi-value">${data.total}</div></div>
                    <div class="kpi-card red"><div class="kpi-label">ملغاة</div><div class="kpi-value">${data.cancelled}</div></div>
                    <div class="kpi-card blue"><div class="kpi-label">الإيراد</div><div class="kpi-value">${parseFloat(data.revenue).toFixed(0)}</div><div class="kpi-sub">ج.م</div></div>
                </div>`;
            } catch (e) {
                if (typeof showError === 'function') showError('حدث خطأ');
                else if (typeof showToast === 'function') showToast('حدث خطأ', 'error');
            }
        };

    })();
</script>