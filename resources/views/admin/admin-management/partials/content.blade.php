<div class="section-header">
    <h2>إدارة المديرين</h2>
    <button class="btn btn-primary" onclick="openModal('modal-add-admin')">إضافة مدير</button>
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
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td style="text-align: right;font-size: 15px;"><strong>{{ $admin['name'] }}</strong></td>
                        <td style="text-align: center;font-size: 12px;"><span class="badge badge-gray">{{ $admin['code'] ?? '—' }}</span></td>
                        <td style="text-align: center;"><code style="color:var(--yellow);font-size: 15px;">{{ $admin['username'] }}</code></td>
                        <td style="text-align: center;font-size: 15px;">{{ $admin['phone'] ?? '—' }}</td>
                        <td style="text-align: center;">
                            <button id="status-btn-{{ $admin['id'] }}"
                                onclick="toggleAdminActive({{ $admin['id'] }}, this, {{ json_encode($admin) }})"
                                style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;
                                {{ $admin['is_active'] ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);' }}"
                                data-active="{{ $admin['is_active'] ? '1' : '0' }}">
                                {{ $admin['is_active'] ? '✓ نشط' : '✗ غير نشط' }}
                            </button>
                        </td>
                        <td style="text-align: center;">
                            <div style="display:flex;gap:6px;justify-content: center;">
                                <button class="btn btn-sm btn-secondary"
                                    onclick="openEditAdmin({{ json_encode($admin) }})">تعديل</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">لا يوجد مديرين</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="modal-add-admin">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة مدير جديد</h3><button class="btn-close" onclick="closeModal('modal-add-admin')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-admin-name" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">اسم المستخدم *</label><input id="add-admin-username"
                        type="text" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">كلمة المرور *</label><input id="add-admin-password"
                        type="password" class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف</label><input id="add-admin-phone" type="text"
                        class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-admin')">إلغاء</button>
            <button class="btn btn-primary" onclick="addAdmin()">حفظ</button>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="modal-edit-admin">
    <div class="modal">
        <div class="modal-header">
            <h3>تعديل بيانات المدير</h3><button class="btn-close" onclick="closeModal('modal-edit-admin')">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-admin-id">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-admin-name" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف</label><input id="edit-admin-phone" type="text"
                        class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">كلمة مرور جديدة (اختياري)</label><input
                        id="edit-admin-password" type="password" class="form-control"></div>
                <div class="form-group"><label class="form-label">الحالة</label>
                    <select id="edit-admin-active" class="form-select">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-admin')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveAdmin()">حفظ التعديلات</button>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        window.addAdmin = async function () {
            try {
                const { data } = await axios.post('{{ route("admin.admin-management.store") }}', {
                    name: document.getElementById('add-admin-name').value,
                    username: document.getElementById('add-admin-username').value,
                    password: document.getElementById('add-admin-password').value,
                    phone: document.getElementById('add-admin-phone').value,
                });
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');

                if (typeof navigate === 'function') {
                    closeModal('modal-add-admin');
                    navigate('{{ route("admin.admin-management.index") }}');
                } else {
                    window.location.reload();
                }
            } catch (e) {
                const err = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : (e.response?.data?.message || 'حدث خطأ');
                if (typeof showError === 'function') showError(err);
                else if (typeof showToast === 'function') showToast(err, 'error');
            }
        };

        window.openEditAdmin = function (admin) {
            document.getElementById('edit-admin-id').value = admin.id;
            document.getElementById('edit-admin-name').value = admin.name;
            document.getElementById('edit-admin-phone').value = admin.phone ?? '';
            document.getElementById('edit-admin-password').value = '';
            document.getElementById('edit-admin-active').value = admin.is_active ? '1' : '0';
            openModal('modal-edit-admin');
        };

        window.saveAdmin = async function () {
            const id = document.getElementById('edit-admin-id').value;
            try {
                const { data } = await axios.put(`/admin/admin-management/${id}`, {
                    name: document.getElementById('edit-admin-name').value,
                    phone: document.getElementById('edit-admin-phone').value,
                    password: document.getElementById('edit-admin-password').value,
                    is_active: document.getElementById('edit-admin-active').value,
                });
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');

                if (typeof navigate === 'function') {
                    closeModal('modal-edit-admin');
                    navigate('{{ route("admin.admin-management.index") }}');
                } else {
                    window.location.reload();
                }
            } catch (e) {
                const err = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : (e.response?.data?.message || 'حدث خطأ');
                if (typeof showError === 'function') showError(err);
                else if (typeof showToast === 'function') showToast(err, 'error');
            }
        };

        window.toggleAdminActive = async function (id, btn, admin) {
            const isCurrentlyActive = btn.dataset.active === '1';
            const newState = isCurrentlyActive ? 0 : 1;
            applyStatusBtn(btn, newState);
            try {
                const { data } = await axios.put(`/admin/admin-management/${id}`, { ...admin, is_active: newState });
                if (typeof showSuccess === 'function') showSuccess(data.message);
                else if (typeof showToast === 'function') showToast(data.message, 'success');
            } catch (e) {
                applyStatusBtn(btn, isCurrentlyActive ? 1 : 0);
                const err = e.response?.data?.message || 'حدث خطأ';
                if (typeof showError === 'function') showError(err);
                else if (typeof showToast === 'function') showToast(err, 'error');
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
    })();
</script>
