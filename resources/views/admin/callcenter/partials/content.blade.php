<div class="section-header">
    <h2>📞 إدارة الكول سنتر</h2>
    <button class="btn btn-primary" onclick="openModal('modal-add-cc')">➕ إضافة موظف</button>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>الاسم</th><th>الكود</th><th>اسم المستخدم</th><th>الهاتف</th><th>نشط</th><th>أنشأ اليوم</th><th>إيراد اليوم</th><th>إجراءات</th></tr>
            </thead>
            <tbody>
                @forelse($agents as $cc)
                <tr>
                    <td><strong>{{ $cc['name'] }}</strong></td>
                    <td><span class="badge badge-gray">{{ $cc['code'] ?? '—' }}</span></td>
                    <td><code style="color:var(--yellow)">{{ $cc['username'] }}</code></td>
                    <td>{{ $cc['phone'] ?? '—' }}</td>
                    <td>
                        <button
                            id="status-btn-{{ $cc['id'] }}"
                            onclick="toggleActive({{ $cc['id'] }}, this, {{ json_encode($cc) }})"
                            style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;
                            {{ $cc['is_active'] ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);' }}"
                            data-active="{{ $cc['is_active'] ? '1' : '0' }}">
                            {{ $cc['is_active'] ? '✓ نشط' : '✗ غير نشط' }}
                        </button>
                    </td>
                    <td>{{ $cc['created'] }}</td>
                    <td>{{ number_format($cc['revenue'], 2) }} ج</td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <button class="btn btn-sm btn-secondary" onclick="openEdit({{ json_encode($cc) }})">✏️</button>
                            <button class="btn btn-sm btn-info" onclick="viewPerf({{ $cc['id'] }}, '{{ addslashes($cc['name']) }}')">📊</button>
                            <button class="btn btn-outline-warning btn-sm" onclick="openSettleModal({{ $cc['id'] }}, '{{ addslashes($cc['name']) }}')" title="تسوية">
                                <i class="fas fa-hand-holding-usd"></i>
                                <span class="d-none d-md-inline ms-1">تسوية</span>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">لا موظفين</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="modal-add-cc">
    <div class="modal">
        <div class="modal-header"><h3>➕ إضافة موظف كول سنتر</h3><button class="btn-close" onclick="closeModal('modal-add-cc')">✕</button></div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">كود الموظف</label><input id="add-code" type="text" class="form-control" placeholder="تلقائي إذا ترك فارغاً"></div>
                <div class="form-group"><label class="form-label">اسم المستخدم *</label><input id="add-username" type="text" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">كلمة المرور *</label><input id="add-password" type="password" class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف</label><input id="add-phone" type="text" class="form-control"></div>
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
        <div class="modal-header"><h3>✏️ تعديل موظف كول سنتر</h3><button class="btn-close" onclick="closeModal('modal-edit-cc')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">كود الموظف</label><input id="edit-code" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف</label><input id="edit-phone" type="text" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">كلمة مرور جديدة (اختياري)</label><input id="edit-password" type="password" class="form-control"></div>
                <div class="form-group"><label class="form-label">الحالة</label>
                    <select id="edit-active" class="form-select"><option value="1">نشط</option><option value="0">غير نشط</option></select>
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
        <div class="modal-header"><h3>📊 أداء: <span id="perf-name"></span></h3><button class="btn-close" onclick="closeModal('modal-perf')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="perf-id">
            <div class="filter-bar" style="margin-bottom:16px">
                <input type="date" id="perf-from" class="form-control">
                <input type="date" id="perf-to" class="form-control">
                <button class="btn btn-primary" onclick="loadPerf()">تحديث</button>
            </div>
            <div id="perf-body"></div>
        </div>
    </div>
</div>

{{-- ── Settlement Modal ─────────────────────────────────────── --}}
<div class="modal-overlay" id="settle-modal">
    <div class="modal">
        <div class="modal-header" style="background-color: rgba(245,158,11,.10); border-bottom:0;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="background-color: rgba(245,158,11,.20); padding:8px 10px; border-radius:50%; display:flex;">
                    <i class="fas fa-hand-holding-usd" style="color:#f59e0b; font-size:16px;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:700;">تسوية حساب موظف</h3>
                    <small class="text-muted" id="settle-agent-subtitle">—</small>
                </div>
            </div>
            <button class="btn-close" onclick="closeModal('settle-modal')">✕</button>
        </div>

        <div class="modal-body">
            {{-- Global error (non-422 failures) --}}
            <div class="error-text d-none" id="settle-global-error" style="background:var(--red-light); color:var(--red-dark); padding:10px; border-radius:8px; margin-bottom:12px; display:none;"></div>

            {{-- Amount field --}}
            <div class="form-group">
                <label for="settle-amount" class="form-label">
                    المبلغ <span style="color:var(--red)">*</span>
                </label>
                <div style="display:flex;">
                    <input type="number" id="settle-amount" class="form-control" placeholder="0.00" min="0.01" step="0.01" max="9999999.99" style="border-radius: 0 8px 8px 0; flex:1;">
                    <span style="background:var(--input-bg); padding:9px 12px; border-radius: 8px 0 0 8px; font-size:13px; color:var(--text-muted); border: 1px solid var(--border); border-right:none;">ج.م</span>
                </div>
                <div class="error-text" id="settle-amount-error"></div>
            </div>

            {{-- Note field --}}
            <div class="form-group">
                <label for="settle-note" class="form-label">
                    ملاحظة
                    <span class="text-muted" style="font-weight:normal; font-size:11px;">(اختياري)</span>
                </label>
                <textarea id="settle-note" class="form-control" rows="2" maxlength="500" placeholder="سبب التسوية أو أي ملاحظات إضافية..."></textarea>
                <div class="error-text" id="settle-note-error"></div>
            </div>
        </div>

        <div class="modal-footer" style="border-top:0;">
            <button class="btn btn-secondary" onclick="closeModal('settle-modal')">إلغاء</button>
            <button class="btn btn-primary" id="settle-submit-btn" onclick="submitSettle()">
                <span id="settle-submit-spinner" class="spin" style="display:none; width:14px; height:14px; margin-left:6px; border-width:2px;"></span>
                تأكيد التسوية
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    window.addCC = async function() {
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
        } catch(e) { 
            const err = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : 'حدث خطأ';
            if (typeof showError === 'function') showError(err);
            else if (typeof showToast === 'function') showToast(err, 'error');
        }
    };

    window.openEdit = function(cc) {
        document.getElementById('edit-id').value = cc.id;
        document.getElementById('edit-name').value = cc.name;
        document.getElementById('edit-code').value = cc.code ?? '';
        document.getElementById('edit-phone').value = cc.phone ?? '';
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-active').value = cc.is_active ? '1' : '0';
        openModal('modal-edit-cc');
    };

    window.saveCC = async function() {
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
        } catch(e) { 
            if (typeof showError === 'function') showError('حدث خطأ');
            else if (typeof showToast === 'function') showToast('حدث خطأ', 'error');
        }
    };

    window.toggleActive = async function(id, btn, cc) {
        const isCurrentlyActive = btn.dataset.active === '1';
        const newState = isCurrentlyActive ? 0 : 1;
        applyStatusBtn(btn, newState);
        try {
            const { data } = await axios.put(`/admin/callcenter/${id}`, { ...cc, is_active: newState });
            if (typeof showSuccess === 'function') showSuccess(data.message);
            else if (typeof showToast === 'function') showToast(data.message, 'success');
        } catch(e) {
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

    window.viewPerf = function(id, name) {
        document.getElementById('perf-id').value = id;
        document.getElementById('perf-name').textContent = name;
        openModal('modal-perf');
        loadPerf();
    };

    window.loadPerf = async function() {
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
        } catch(e) { 
            if (typeof showError === 'function') showError('حدث خطأ');
            else if (typeof showToast === 'function') showToast('حدث خطأ', 'error');
        }
    };

    // ── Settlement Modal state ────────────────────────────────────
    let settleTargetId  = null;

    // ── Open: called by the Settle button in each agent row ──────
    window.openSettleModal = function (agentId, agentName) {
        settleTargetId = agentId;

        // Display the agent's name in the modal subtitle
        const subtitle = document.getElementById('settle-agent-subtitle');
        if (subtitle) subtitle.textContent = agentName;

        // Reset all fields and error states
        document.getElementById('settle-amount').value = '';
        document.getElementById('settle-note').value   = '';
        clearSettleErrors();

        openModal('settle-modal');

        // Auto-focus the amount field
        setTimeout(() => {
            const input = document.getElementById('settle-amount');
            if (input) input.focus();
        }, 300);
    };

    window.submitSettle = async function () {
        if (!settleTargetId) return;

        clearSettleErrors();
        setSettleLoading(true);

        const payload = {
            amount: document.getElementById('settle-amount').value,
            note:   document.getElementById('settle-note').value.trim() || null,
        };

        try {
            const res = await axios.post(
                `/admin/callcenter/${settleTargetId}/settle`,
                payload
            );

            // Success path
            resetSettleFields();
            closeModal('settle-modal');

            if (typeof showToast === 'function') {
                showToast(res.data.message || 'تمت التسوية بنجاح ✓', 'success');
            } else if (typeof showSuccess === 'function') {
                showSuccess(res.data.message || 'تمت التسوية بنجاح');
            } else {
                alert(res.data.message || 'تمت التسوية بنجاح');
            }

            if (typeof navigate === 'function') {
                navigate('{{ route("admin.callcenter.index") }}');
            } else {
                window.location.reload();
            }

        } catch (error) {
            if (error.response?.status === 422) {
                // Laravel field-level validation errors
                const errors = error.response.data.errors || {};
                if (errors.amount) {
                    document.getElementById('settle-amount').style.borderColor = 'var(--red)';
                    document.getElementById('settle-amount-error').textContent = errors.amount[0];
                }
                if (errors.note) {
                    document.getElementById('settle-note').style.borderColor = 'var(--red)';
                    document.getElementById('settle-note-error').textContent = errors.note[0];
                }
            } else {
                const msg = error.response?.data?.message
                    || 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.';
                const globalErr = document.getElementById('settle-global-error');
                globalErr.textContent = msg;
                globalErr.style.display = 'block';
            }
        } finally {
            setSettleLoading(false);
        }
    };

    // ── Helpers ───────────────────────────────────────────────────
    function clearSettleErrors() {
        ['amount', 'note'].forEach(field => {
            const input = document.getElementById(`settle-${field}`);
            const error = document.getElementById(`settle-${field}-error`);
            if (input) input.style.borderColor = 'var(--border)';
            if (error) error.textContent = '';
        });
        const globalErr = document.getElementById('settle-global-error');
        if (globalErr) {
            globalErr.textContent = '';
            globalErr.style.display = 'none';
        }
    }

    function resetSettleFields() {
        document.getElementById('settle-amount').value = '';
        document.getElementById('settle-note').value   = '';
        clearSettleErrors();
    }

    function setSettleLoading(loading) {
        const btn     = document.getElementById('settle-submit-btn');
        const spinner = document.getElementById('settle-submit-spinner');
        if (btn)     btn.disabled = loading;
        if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
    }
})();
</script>
