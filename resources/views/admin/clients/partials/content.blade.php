{{-- Admin Clients page as SPA-injectable partial --}}
<div class="section-header">
    <h2>👥 إدارة العملاء</h2>
    <button class="btn btn-primary" onclick="openModal('modal-add-client')">➕ إضافة عميل</button>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="text" id="filter-search" class="form-control" placeholder="بحث بالاسم / الهاتف / الكود"
            style="min-width:220px">
        <input type="date" id="filter-from" class="form-control">
        <input type="date" id="filter-to" class="form-control">
        <button class="btn btn-primary" onclick="loadClients(1)">🔍 بحث</button>
        <button class="btn btn-secondary" onclick="resetFilters()">↺ إعادة</button>
    </div>
</div>

<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="table-loading">
        <div class="spin" style="width:30px;height:30px;border-width:3px"></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th style="text-align: center;">الكود</th>
                    <th style="text-align: center;">الهاتف</th>
                    <th style="text-align: center;">هاتف 2</th>
                    <th style="text-align: center;">العناوين</th>
                    <th style="text-align: center;">الطلبات</th>
                    <th style="text-align: center;">إجمالي الإنفاق</th>
                    <th style="text-align: center;">آخر طلب</th>
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="clients-body">
                <tr>
                    <td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">جاري التحميل...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="pagination-wrap" style="padding:16px"></div>
</div>

<div class="modal-overlay" id="modal-add-client">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ إضافة عميل جديد</h3><button class="btn-close" onclick="closeModal('modal-add-client')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف *</label><input id="add-phone" type="text"
                        class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">هاتف 2</label><input id="add-phone2" type="text"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">الكود (يتولد تلقائياً)</label><input id="add-code"
                        type="text" class="form-control" placeholder="00001"></div>
            </div>
            <div class="form-group"><label class="form-label">العنوان الأول *</label><input id="add-address" type="text"
                    class="form-control"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-client')">إلغاء</button>
            <button class="btn btn-primary" onclick="addClient()">حفظ</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-view-client">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3>👁 بيانات العميل</h3><button class="btn-close" onclick="closeModal('modal-view-client')">✕</button>
        </div>
        <div class="modal-body" id="view-client-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-edit-client">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>✏️ تعديل بيانات العميل</h3><button class="btn-close"
                onclick="closeModal('modal-edit-client')">✕</button>
        </div>
        <div class="modal-body" id="edit-client-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-client')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveEditClient()">حفظ التعديلات</button>
        </div>
    </div>
</div>

<script>
    var currentPage = 1;
    var editClientId = null;
    var editAddresses = [];

    function getFilters() {
        return {
            search: document.getElementById('filter-search').value,
            from: document.getElementById('filter-from').value,
            to: document.getElementById('filter-to').value,
        };
    }
    function resetFilters() {
        document.getElementById('filter-search').value = '';
        document.getElementById('filter-from').value = '';
        document.getElementById('filter-to').value = '';
        loadClients(1);
    }

    async function loadClients(page = 1) {
        currentPage = page;
        document.getElementById('table-loading').classList.add('show');
        try {
            const { data } = await axios.get('{{ route("admin.clients.index") }}', {
                params: { ...getFilters(), page },
                headers: { 'Accept': 'application/json' }
            });
            var body = document.getElementById('clients-body');
            if (!data.data.length) {
                body.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">لا عملاء</td></tr>';
                document.getElementById('pagination-wrap').innerHTML = '';
                return;
            }
            body.innerHTML = data.data.map(c => `<tr>
            <td><strong>${c.name}</strong></td>
            <td><code style="color:var(--yellow);text-align: center;">${c.code}</code></td>
            <td style="text-align: center;">${c.phone}</td>
            <td style="text-align: center;">${c.phone2 ?? '—'}</td>
            <td style="text-align: center;">${c.addresses_count ?? 0}</td>
            <td style="text-align: center;">${c.orders_count}</td>
            <td style="text-align: center;">${parseFloat(c.orders_sum_total || 0).toFixed(2)} ج</td>
            <td style="font-size:12px;color:var(--text-muted);text-align: center;">${c.orders?.[0] ? formatDate(c.orders[0].created_at) : '—'}</td>
            <td><div style="display:flex;gap:6px;justify-content: center">
                <button class="btn btn-sm btn-info" onclick="viewClient(${c.id})">عرض</button>
                <button class="btn btn-sm btn-secondary" onclick="editClient(${c.id})">تعديل</button>
                <button class="btn btn-sm btn-danger" onclick="deleteClient(${c.id}, '${c.name}', ${c.orders_count})">حذف</button>
            </div></td>
        </tr>`).join('');
            renderPagination(data.last_page, data.current_page);
        } catch (e) { console.error(e); }
        finally { document.getElementById('table-loading').classList.remove('show'); }
    }

    function renderPagination(lastPage, current) {
        if (lastPage <= 1) { document.getElementById('pagination-wrap').innerHTML = ''; return; }
        var html = '<div class="pagination">';
        html += `<a class="${current === 1 ? 'disabled' : ''}" onclick="loadClients(${current - 1})">‹</a>`;
        for (let i = 1; i <= lastPage; i++) {
            if (i === 1 || i === lastPage || Math.abs(i - current) <= 2) html += `<a class="${i === current ? 'active' : ''}" onclick="loadClients(${i})">${i}</a>`;
            else if (Math.abs(i - current) === 3) html += '<span>…</span>';
        }
        html += `<a class="${current === lastPage ? 'disabled' : ''}" onclick="loadClients(${current + 1})">›</a></div>`;
        document.getElementById('pagination-wrap').innerHTML = html;
    }

    async function addClient() {
        var payload = {
            name: document.getElementById('add-name').value,
            phone: document.getElementById('add-phone').value,
            phone2: document.getElementById('add-phone2').value,
            code: document.getElementById('add-code').value,
            first_address: document.getElementById('add-address').value,
        };
        try {
            const { data } = await axios.post('{{ route("admin.clients.store") }}', payload);
            showSuccess(data.message);
            closeModal('modal-add-client');
            loadClients(1);
        } catch (e) {
            var errors = e.response?.data?.errors;
            if (errors) showError(Object.values(errors).flat().join(' | '));
            else showError('حدث خطأ');
        }
    }

    async function viewClient(id) {
        openModal('modal-view-client');
        document.getElementById('view-client-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>';
        try {
            const { data } = await axios.get(`/admin/clients/${id}`);
            var c = data.client;
            document.getElementById('view-client-body').innerHTML = `
            <div style="display:flex;align-items:center;gap:16px;background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:20px">
                <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--yellow),#f97316);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#1e293b;flex-shrink:0">${c.name.charAt(0)}</div>
                <div style="flex:1"><div style="font-size:17px;font-weight:700">${c.name}</div><div style="font-size:12px;color:var(--text-muted);margin-top:3px">منذ ${formatDate(c.created_at)}</div></div>
                <code style="background:rgba(245,158,11,.12);color:var(--yellow);padding:5px 12px;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:1px">${c.code}</code>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center"><div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:6px">إجمالي الطلبات</div><div style="font-size:22px;font-weight:800;color:var(--yellow)">${c.orders_count}</div></div>
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center"><div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:6px">إجمالي الإنفاق</div><div style="font-size:22px;font-weight:800;color:#34d399">${parseFloat(c.total_spent).toFixed(2)} ج</div></div>
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center"><div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:6px">العناوين المسجلة</div><div style="font-size:22px;font-weight:800;color:#60a5fa">${c.addresses.length}</div></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:16px">
                    <div style="font-size:11px;font-weight:700;color:var(--yellow);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px">بيانات التواصل</div>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:var(--text-muted)">الهاتف الأساسي</span><span dir="ltr" style="font-weight:600">${c.phone}</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:var(--text-muted)">هاتف بديل</span><span dir="ltr">${c.phone2 ?? '—'}</span></div>
                    </div>
                </div>
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:16px">
                    <div style="font-size:11px;font-weight:700;color:var(--yellow);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px">العناوين</div>
                    <div style="display:flex;flex-direction:column;gap:6px;max-height:120px;overflow-y:auto">
                        ${c.addresses.length ? c.addresses.map(a => `<div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid var(--border)"><span>${a.address}</span>${a.is_default ? '<span style="background:rgba(52,211,153,.15);color:#34d399;font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px">افتراضي</span>' : ''}</div>`).join('') : '<div style="color:var(--text-muted);font-size:13px">لا عناوين</div>'}
                    </div>
                </div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--yellow);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px">آخر 5 طلبات</div>
                <div class="table-wrap" style="border-radius:10px;overflow:hidden;border:1px solid var(--border)">
                    <table style="margin:0"><thead><tr><th style="text-align:center">رقم الطلب</th><th style="text-align:center">الإجمالي</th><th style="text-align:center">الحالة</th><th style="text-align:center">التاريخ</th></tr></thead>
                    <tbody>${c.orders.length ? c.orders.map(o => `<tr><td style="text-align:center;color:var(--yellow);font-weight:700">${o.order_number}</td><td style="text-align:center;font-weight:600">${parseFloat(o.total).toFixed(2)} ج</td><td style="text-align:center">${statusBadge(o.status)}</td><td style="text-align:center;font-size:12px;color:var(--text-muted)">${formatDate(o.created_at)}</td></tr>`).join('') : '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px">لا طلبات</td></tr>'}</tbody>
                    </table>
                </div>
            </div>
        `;
        } catch (e) { document.getElementById('view-client-body').innerHTML = '<div style="text-align:center;color:var(--red)">حدث خطأ</div>'; }
    }

    async function editClient(id) {
        editClientId = id;
        openModal('modal-edit-client');
        document.getElementById('edit-client-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>';
        try {
            const { data } = await axios.get(`/admin/clients/${id}`);
            var c = data.client;
            editAddresses = c.addresses.map(a => ({ ...a, _delete: false }));
            renderEditForm(c);
        } catch (e) { document.getElementById('edit-client-body').innerHTML = '<div style="text-align:center;color:var(--red)">حدث خطأ</div>'; }
    }

    function renderEditForm(c) {
        document.getElementById('edit-client-body').innerHTML = `
        <!-- Customer Info -->
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px">
            <div style="font-size:12px;font-weight:700;color:var(--yellow);text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;display:flex;align-items:center;gap:6px">
                <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                البيانات الأساسية
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم <span style="color:var(--red)">*</span></label><input id="edit-name" type="text" class="form-control" value="${c.name}"></div>
                <div class="form-group"><label class="form-label">الكود (للقراءة فقط)</label><input type="text" class="form-control" value="${c.code}" disabled style="opacity:0.6;background:var(--bg-light)"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الهاتف الأساسي <span style="color:var(--red)">*</span></label><input id="edit-phone" type="text" class="form-control" value="${c.phone}" dir="ltr" style="text-align:right"></div>
                <div class="form-group"><label class="form-label">هاتف إضافي</label><input id="edit-phone2" type="text" class="form-control" value="${c.phone2 ?? ''}" dir="ltr" style="text-align:right"></div>
            </div>
        </div>

        <!-- Addresses list -->
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:var(--yellow);text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;gap:6px">
                    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    عناوين استلام الطلبات
                </div>
                <button class="btn btn-sm btn-primary" onclick="addAddressRow()" style="border-radius:6px;padding:4px 10px;font-size:12px;display:flex;align-items:center;gap:4px">
                    <svg style="width:14px;height:14px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg> عنوان جديد
                </button>
            </div>
            <div id="addresses-list" style="display:flex;flex-direction:column;gap:12px"></div>
        </div>
    `;
        renderAddressList();
    }
    function renderAddressList() {
        var list = document.getElementById('addresses-list');
        list.innerHTML = editAddresses.filter(a => !a._delete).map((a) => {
            const idx = editAddresses.indexOf(a);
            return `
        <div style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid rgba(255,255,255,0.05);border-radius:8px;background:var(--bg-light)">
            <input type="text" class="form-control" value="${a.address}" oninput="editAddresses[${idx}].address = this.value" placeholder="اكتب العنوان بالتفصيل..." style="flex:1">
            <div style="display:flex;gap:10px;align-items:center;height:38px;padding-top:2px">
                <label class="toggle" title="تعيين كعنوان افتراضي" style="margin:0;display:flex;align-items:center;gap:6px;cursor:pointer">
                    <span style="font-size:11px;color:${a.is_default ? '#34d399' : 'var(--text-muted)'};font-weight:600">افتراضي</span>
                    <input type="checkbox" ${a.is_default ? 'checked' : ''} onchange="setDefault(${idx})">
                    <span class="toggle-slider"></span>
                </label>
                <button class="btn btn-sm" onclick="removeAddress(${idx})" style="background:rgba(239,68,68,0.1);color:#ef4444;border:none;padding:6px;border-radius:6px;height:30px;width:30px;display:flex;align-items:center;justify-content:center" title="حذف" tabindex="-1">
                    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        </div>`;
        }).join('') || '<div style="text-align:center;color:var(--text-muted);font-size:13px;padding:20px;border:1px dashed var(--border);border-radius:8px;background:var(--bg-light)">لا توجد عناوين مسجلة للعميل. يرجى إضافة عنوان للتوصيل الإفتراضي.</div>';
    }
    function addAddressRow() { editAddresses.push({ id: null, address: '', is_default: false, _delete: false }); renderAddressList(); }
    function removeAddress(idx) { if (editAddresses[idx].id) editAddresses[idx]._delete = true; else editAddresses.splice(idx, 1); renderAddressList(); }
    function setDefault(idx) { editAddresses.forEach((a, i) => a.is_default = (i === idx)); renderAddressList(); }

    async function saveEditClient() {
        var payload = {
            name: document.getElementById('edit-name').value,
            phone: document.getElementById('edit-phone').value,
            phone2: document.getElementById('edit-phone2').value,
            addresses: editAddresses,
        };
        try {
            const { data } = await axios.put(`/admin/clients/${editClientId}`, payload);
            showSuccess(data.message);
            closeModal('modal-edit-client');
            loadClients(currentPage);
        } catch (e) {
            var errors = e.response?.data?.errors;
            showError(errors ? Object.values(errors).flat().join(' | ') : 'حدث خطأ');
        }
    }

    async function deleteClient(id, name, ordersCount) {
        if (ordersCount > 0) { showError(`لا يمكن حذف العميل "${name}" لديه ${ordersCount} طلب`); return; }
        var ok = await confirmAction('حذف العميل', `هل تريد حذف "${name}"؟`, 'حذف');
        if (!ok) return;
        try {
            const { data } = await axios.delete(`/admin/clients/${id}`);
            showSuccess(data.message);
            loadClients(currentPage);
        } catch (e) { showError(e.response?.data?.message ?? 'حدث خطأ'); }
    }

    loadClients(1);
</script>