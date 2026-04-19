@extends('layouts.admin')

@section('page-title', 'العملاء')

@section('content')
<div class="section-header">
    <h2>👥 إدارة العملاء</h2>
    <button class="btn btn-primary" onclick="openModal('modal-add-client')">➕ إضافة عميل</button>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="text" id="filter-search" class="form-control" placeholder="بحث بالاسم / الهاتف / الكود" style="min-width:220px">
        <input type="date" id="filter-from" class="form-control">
        <input type="date" id="filter-to" class="form-control">
        <button class="btn btn-primary" onclick="loadClients(1)">🔍 بحث</button>
        <button class="btn btn-secondary" onclick="resetFilters()">↺ إعادة</button>
    </div>
</div>

{{-- Table --}}
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="table-loading"><div class="spin" style="width:30px;height:30px;border-width:3px"></div></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>الاسم</th><th>الكود</th><th>الهاتف</th><th>هاتف 2</th>
                    <th>العناوين</th><th>الطلبات</th><th>إجمالي الإنفاق</th><th>آخر طلب</th><th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="clients-body">
                <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="pagination-wrap" style="padding:16px"></div>
</div>

{{-- Add Client Modal --}}
<div class="modal-overlay" id="modal-add-client">
    <div class="modal">
        <div class="modal-header"><h3>➕ إضافة عميل جديد</h3><button class="btn-close" onclick="closeModal('modal-add-client')">✕</button></div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الهاتف *</label><input id="add-phone" type="text" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">هاتف 2</label><input id="add-phone2" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الكود (يتولد تلقائياً)</label><input id="add-code" type="text" class="form-control" placeholder="0001"></div>
            </div>
            <div class="form-group"><label class="form-label">العنوان الأول *</label><input id="add-address" type="text" class="form-control"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-client')">إلغاء</button>
            <button class="btn btn-primary" onclick="addClient()">حفظ</button>
        </div>
    </div>
</div>

{{-- View Client Modal --}}
<div class="modal-overlay" id="modal-view-client">
    <div class="modal modal-xl">
        <div class="modal-header"><h3>👁 بيانات العميل</h3><button class="btn-close" onclick="closeModal('modal-view-client')">✕</button></div>
        <div class="modal-body" id="view-client-body"><div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div></div>
    </div>
</div>

{{-- Edit Client Modal --}}
<div class="modal-overlay" id="modal-edit-client">
    <div class="modal modal-lg">
        <div class="modal-header"><h3>✏️ تعديل بيانات العميل</h3><button class="btn-close" onclick="closeModal('modal-edit-client')">✕</button></div>
        <div class="modal-body" id="edit-client-body"><div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-client')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveEditClient()">حفظ التعديلات</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var currentPage = 1;
var editClientId = null;
var editAddresses = [];

function getFilters() {
    return {
        search: document.getElementById('filter-search').value,
        from:   document.getElementById('filter-from').value,
        to:     document.getElementById('filter-to').value,
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
        const body = document.getElementById('clients-body');
        if (!data.data.length) {
            body.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">لا عملاء</td></tr>';
            document.getElementById('pagination-wrap').innerHTML = '';
            return;
        }
        body.innerHTML = data.data.map(c => `<tr>
            <td><strong>${c.name}</strong></td>
            <td><code style="color:var(--yellow)">${c.code}</code></td>
            <td>${c.phone}</td>
            <td>${c.phone2 ?? '—'}</td>
            <td>${c.addresses_count ?? 0}</td>
            <td>${c.orders_count}</td>
            <td>${parseFloat(c.orders_sum_total||0).toFixed(2)} ج</td>
            <td style="font-size:12px;color:var(--text-muted)">${c.orders?.[0] ? formatDate(c.orders[0].created_at) : '—'}</td>
            <td>
                <div style="display:flex;gap:6px">
                    <button class="btn btn-sm btn-info" onclick="viewClient(${c.id})">👁</button>
                    <button class="btn btn-sm btn-secondary" onclick="editClient(${c.id})">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteClient(${c.id}, '${c.name}', ${c.orders_count})">🗑</button>
                </div>
            </td>
        </tr>`).join('');
        renderPagination(data.last_page, data.current_page);
    } catch(e) { console.error(e); }
    finally { document.getElementById('table-loading').classList.remove('show'); }
}

function renderPagination(lastPage, current) {
    if (lastPage <= 1) { document.getElementById('pagination-wrap').innerHTML = ''; return; }
    let html = '<div class="pagination">';
    html += `<a class="${current===1?'disabled':''}" onclick="loadClients(${current-1})">‹</a>`;
    for (let i=1;i<=lastPage;i++) {
        if (i===1||i===lastPage||Math.abs(i-current)<=2) html += `<a class="${i===current?'active':''}" onclick="loadClients(${i})">${i}</a>`;
        else if (Math.abs(i-current)===3) html += '<span>…</span>';
    }
    html += `<a class="${current===lastPage?'disabled':''}" onclick="loadClients(${current+1})">›</a></div>`;
    document.getElementById('pagination-wrap').innerHTML = html;
}

async function addClient() {
    const payload = {
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
    } catch(e) {
        const errors = e.response?.data?.errors;
        if (errors) showError(Object.values(errors).flat().join(' | '));
        else showError('حدث خطأ');
    }
}

async function viewClient(id) {
    openModal('modal-view-client');
    document.getElementById('view-client-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>';
    try {
        const { data } = await axios.get(`/admin/clients/${id}`);
        const c = data.client;
        document.getElementById('view-client-body').innerHTML = `
            <div class="form-row" style="margin-bottom:16px">
                <div>
                    <div class="info-row"><span class="info-label">الاسم</span><span>${c.name}</span></div>
                    <div class="info-row"><span class="info-label">الكود</span><code style="color:var(--yellow)">${c.code}</code></div>
                    <div class="info-row"><span class="info-label">الهاتف</span><span>${c.phone}</span></div>
                    <div class="info-row"><span class="info-label">هاتف 2</span><span>${c.phone2 ?? '—'}</span></div>
                    <div class="info-row"><span class="info-label">منذ</span><span>${formatDate(c.created_at)}</span></div>
                </div>
                <div>
                    <div class="info-row"><span class="info-label">الطلبات</span><span>${c.orders_count}</span></div>
                    <div class="info-row"><span class="info-label">إجمالي الإنفاق</span><strong>${parseFloat(c.total_spent).toFixed(2)} ج</strong></div>
                </div>
            </div>
            <div class="card-title" style="margin-bottom:8px">العناوين</div>
            <div style="margin-bottom:16px">${c.addresses.map(a => `
                <div style="padding:8px;background:var(--bg);border-radius:8px;margin-bottom:6px;font-size:13px;display:flex;justify-content:space-between">
                    <span>${a.address}</span>
                    ${a.is_default ? '<span class="badge badge-green">افتراضي</span>' : ''}
                </div>`).join('') || '<div style="color:var(--text-muted)">لا عناوين</div>'}
            </div>
            <div class="card-title" style="margin-bottom:8px">آخر 5 طلبات</div>
            <div class="table-wrap">
                <table><thead><tr><th>رقم الطلب</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                <tbody>${c.orders.map(o => `<tr>
                    <td style="color:var(--yellow)">${o.order_number}</td>
                    <td>${parseFloat(o.total).toFixed(2)} ج</td>
                    <td>${statusBadge(o.status)}</td>
                    <td style="font-size:12px">${formatDate(o.created_at)}</td>
                </tr>`).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>'}
                </tbody></table>
            </div>
        `;
    } catch(e) { document.getElementById('view-client-body').innerHTML = '<div style="text-align:center;color:var(--red)">حدث خطأ</div>'; }
}

async function editClient(id) {
    editClientId = id;
    openModal('modal-edit-client');
    document.getElementById('edit-client-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>';
    try {
        const { data } = await axios.get(`/admin/clients/${id}`);
        const c = data.client;
        editAddresses = c.addresses.map(a => ({ ...a, _delete: false }));
        renderEditForm(c);
    } catch(e) { document.getElementById('edit-client-body').innerHTML = '<div style="text-align:center;color:var(--red)">حدث خطأ</div>'; }
}

function renderEditForm(c) {
    document.getElementById('edit-client-body').innerHTML = `
        <div class="form-row">
            <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-name" type="text" class="form-control" value="${c.name}"></div>
            <div class="form-group"><label class="form-label">الكود (مقفول)</label><input type="text" class="form-control" value="${c.code}" disabled style="opacity:0.5"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">الهاتف *</label><input id="edit-phone" type="text" class="form-control" value="${c.phone}"></div>
            <div class="form-group"><label class="form-label">هاتف 2</label><input id="edit-phone2" type="text" class="form-control" value="${c.phone2 ?? ''}"></div>
        </div>
        <hr class="divider">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
            <span class="card-title" style="margin:0">العناوين</span>
            <button class="btn btn-sm btn-secondary" onclick="addAddressRow()">➕ إضافة</button>
        </div>
        <div id="addresses-list"></div>
    `;
    renderAddressList();
}

function renderAddressList() {
    const list = document.getElementById('addresses-list');
    list.innerHTML = editAddresses.filter(a => !a._delete).map((a, i) => `
        <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
            <input type="text" class="form-control" value="${a.address}" oninput="editAddresses[${editAddresses.indexOf(a)}].address = this.value">
            <label class="toggle" title="افتراضي">
                <input type="checkbox" ${a.is_default ? 'checked' : ''} onchange="setDefault(${editAddresses.indexOf(a)})">
                <span class="toggle-slider"></span>
            </label>
            <button class="btn btn-sm btn-danger" onclick="removeAddress(${editAddresses.indexOf(a)})">🗑</button>
        </div>`).join('') || '<div style="color:var(--text-muted);font-size:13px">لا عناوين</div>';
}

function addAddressRow() { editAddresses.push({ id: null, address: '', is_default: false, _delete: false }); renderAddressList(); }
function removeAddress(idx) { if (editAddresses[idx].id) editAddresses[idx]._delete = true; else editAddresses.splice(idx, 1); renderAddressList(); }
function setDefault(idx) { editAddresses.forEach((a, i) => a.is_default = (i === idx)); renderAddressList(); }

async function saveEditClient() {
    const payload = {
        name:      document.getElementById('edit-name').value,
        phone:     document.getElementById('edit-phone').value,
        phone2:    document.getElementById('edit-phone2').value,
        addresses: editAddresses,
    };
    try {
        const { data } = await axios.put(`/admin/clients/${editClientId}`, payload);
        showSuccess(data.message);
        closeModal('modal-edit-client');
        loadClients(currentPage);
    } catch(e) {
        const errors = e.response?.data?.errors;
        showError(errors ? Object.values(errors).flat().join(' | ') : 'حدث خطأ');
    }
}

async function deleteClient(id, name, ordersCount) {
    if (ordersCount > 0) { showError(`لا يمكن حذف العميل "${name}" لديه ${ordersCount} طلب`); return; }
    const ok = await confirmAction('حذف العميل', `هل تريد حذف "${name}"؟`, 'حذف');
    if (!ok) return;
    try {
        const { data } = await axios.delete(`/admin/clients/${id}`);
        showSuccess(data.message);
        loadClients(currentPage);
    } catch(e) { showError(e.response?.data?.message ?? 'حدث خطأ'); }
}

loadClients(1);
</script>
@endpush
