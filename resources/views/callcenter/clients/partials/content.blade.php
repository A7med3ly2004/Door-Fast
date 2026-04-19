{{-- Callcenter Clients SPA partial --}}
<div class="section-header"><h2>👥 قائمة العملاء</h2></div>
<div class="card" style="padding:12px 16px;margin-bottom:14px">
    <div class="filter-bar">
        <input type="text" id="f-search" class="form-control" placeholder="الاسم / الهاتف / الكود" style="min-width:220px">
        <button class="btn btn-primary" onclick="loadClients(1)">🔍 بحث</button>
        <button class="btn btn-success" onclick="showAddClientModal()">➕ عميل جديد</button>
    </div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>الاسم</th><th>الكود</th><th>الهاتف</th><th>هاتف 2</th><th>العناوين</th><th>الطلبات</th><th>آخر طلب</th><th></th></tr></thead>
            <tbody id="clients-body"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">يرجى البحث عن عميل (الاسم، الهاتف، أو الكود)</td></tr></tbody>
        </table>
    </div>
    <div id="pg-wrap" style="padding:14px"></div>
</div>

<div class="modal-overlay" id="modal-view">
    <div class="modal modal-lg"><div class="modal-header"><h3>👤 بيانات العميل</h3><button class="btn-close" onclick="closeModal('modal-view')">✕</button></div><div class="modal-body" id="view-body"></div></div>
</div>

{{-- Add Client Modal --}}
<div class="modal-overlay" id="modal-add-client">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ إضافة عميل جديد</h3>
            <button class="btn-close" onclick="closeModal('modal-add-client')">✕</button>
        </div>
        <form id="form-add-client" onsubmit="saveClient(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="name" class="form-control" required placeholder="اسم العميل">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" name="phone" id="new-phone" class="form-control" required placeholder="رقم الموبايل">
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم هاتف إضافي</label>
                        <input type="text" name="phone2" class="form-control" placeholder="اختياري">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">العنوان بالتفصيل</label>
                    <input type="text" name="first_address" class="form-control" required placeholder="مثال: مدينة نصر - الحي السابع - ش عباس العقاد">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-client')">إلغاء</button>
                <button type="submit" class="btn btn-success" id="btn-save-client">💾 حفظ البيانات</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Client Modal --}}
<div class="modal-overlay" id="modal-edit-client">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ تعديل بيانات العميل</h3>
            <button class="btn-close" onclick="closeModal('modal-edit-client')">✕</button>
        </div>
        <form id="form-edit-client" onsubmit="updateClient(event)">
            <input type="hidden" name="id" id="edit-client-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" name="phone" id="edit-phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم هاتف إضافي</label>
                        <input type="text" name="phone2" id="edit-phone2" class="form-control">
                    </div>
                </div>
                
                <div class="divider"></div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h4 style="font-size:14px">📍 العناوين</h4>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="addAddressRow()">➕ إضافة عنوان</button>
                </div>
                <div id="edit-addresses-list">
                    <!-- Dynamic address rows -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-client')">إلغاء</button>
                <button type="submit" class="btn btn-primary" id="btn-update-client">💾 حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>
<script>
async function loadClients(page = 1) {
    document.getElementById('tbl-loading').classList.add('show');
    try {
        const { data } = await axios.get('{{ route("callcenter.clients.index") }}', { params: { search: document.getElementById('f-search').value, page } });
        var body = document.getElementById('clients-body');
        if (!data.data.length) { body.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">لا يوجد نتائج للبحث</td></tr>'; document.getElementById('pg-wrap').innerHTML = ''; return; }
        body.innerHTML = data.data.map(c => `<tr style="cursor:pointer" onclick="viewClient(${c.id})"><td><strong>${c.name}</strong></td><td><span class="badge badge-gray">${c.code}</span></td><td>${c.phone}</td><td>${c.phone2 ?? '—'}</td><td>${c.addresses_count}</td><td>${c.orders_count}</td><td style="font-size:11px;color:var(--text-muted)">${c.last_order_at ? formatDate(c.last_order_at) : '—'}</td><td><div style="display:flex;gap:5px"><button class="btn btn-sm btn-info" onclick="event.stopPropagation();viewClient(${c.id})">👁 عرض</button><button class="btn btn-sm btn-primary" onclick="event.stopPropagation();editClient(${c.id})">✏️ تعديل</button></div></td></tr>`).join('');
        document.getElementById('pg-wrap').innerHTML = renderPagination(data.last_page, data.current_page, 'loadClients');
    } catch(e) { console.error(e); } finally { document.getElementById('tbl-loading').classList.remove('show'); }
}
async function viewClient(id) {
    openModal('modal-view'); document.getElementById('view-body').innerHTML = '<div style="text-align:center;padding:40px"><div class="spin"></div></div>';
    try {
        const { data } = await axios.get(`/callcenter/clients/${id}`); const c = data.client;
        document.getElementById('view-body').innerHTML = `<div class="grid-2" style="margin-bottom:14px"><div><div class="info-row"><span class="info-label">الاسم</span><span>${c.name}</span></div><div class="info-row"><span class="info-label">الكود</span><span class="badge badge-gray">${c.code}</span></div><div class="info-row"><span class="info-label">الهاتف</span><span>${c.phone}</span></div><div class="info-row"><span class="info-label">هاتف 2</span><span>${c.phone2 ?? '—'}</span></div></div><div><div class="info-row"><span class="info-label">إجمالي الطلبات</span><span>${c.orders_count}</span></div><div class="info-row"><span class="info-label">تاريخ التسجيل</span><span>${formatDate(c.created_at)}</span></div></div></div><div style="font-weight:700;margin-bottom:8px">📍 العناوين (${c.addresses.length}/5)</div><div style="margin-bottom:14px">${c.addresses.map(a => `<div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border)">${a.is_default ? '<span class="badge badge-yellow">افتراضي</span>' : ''}<span style="font-size:13px">${a.address}</span></div>`).join('') || '<div style="color:var(--text-muted);font-size:13px">لا توجد عناوين</div>'}</div><div style="font-weight:700;margin-bottom:8px">📋 آخر 5 طلبات</div><div class="table-wrap"><table><thead><tr><th>رقم الطلب</th><th>الحالة</th><th>الإجمالي</th><th>التاريخ</th></tr></thead><tbody>${c.orders.map(o => `<tr><td><strong>${o.order_number}</strong></td><td>${statusBadge(o.status)}</td><td>${parseFloat(o.total).toFixed(2)} ج</td><td>${formatDate(o.created_at)}</td></tr>`).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">لا طلبات</td></tr>'}</tbody></table></div>`;
    } catch(e) { document.getElementById('view-body').innerHTML = '<div style="color:var(--red)">حدث خطأ</div>'; }
}

function showAddClientModal() {
    document.getElementById('form-add-client').reset();
    openModal('modal-add-client');
}

async function saveClient(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-client');
    const form = document.getElementById('form-add-client');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    btn.disabled = true;
    btn.innerHTML = '<div class="spin" style="width:14px;height:14px;border-width:2px"></div> جاري الحفظ...';

    try {
        const res = await axios.post('{{ route("callcenter.clients.store") }}', data);
        if (res.data.success) {
            showSuccess(res.data.message);
            closeModal('modal-add-client');
            document.getElementById('f-search').value = data.phone;
            loadClients(1);
        }
    } catch (err) {
        if (err.response && err.response.data.errors) {
            const errors = Object.values(err.response.data.errors).flat().join('<br>');
            showError(errors);
        } else {
            showError('حدث خطأ غير متوقع');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = '💾 حفظ البيانات';
    }
}

async function editClient(id) {
    openModal('modal-edit-client');
    const form = document.getElementById('form-edit-client');
    form.reset();
    try {
        const { data } = await axios.get(`/callcenter/clients/${id}`);
        const c = data.client;
        document.getElementById('edit-client-id').value = c.id;
        document.getElementById('edit-name').value = c.name;
        document.getElementById('edit-phone').value = c.phone;
        document.getElementById('edit-phone2').value = c.phone2 || '';
        
        const list = document.getElementById('edit-addresses-list');
        list.innerHTML = '';
        c.addresses.forEach((addr, idx) => {
            appendAddressRow(list, addr, idx);
        });
    } catch(e) { 
        showError('حدث خطأ في جلب بيانات العميل');
        closeModal('modal-edit-client');
    }
}

function appendAddressRow(container, addr = null, index = null) {
    const idx = index !== null ? index : container.children.length;
    const row = document.createElement('div');
    row.className = 'form-group';
    row.style = 'display:flex;gap:8px;align-items:center;padding:8px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;margin-bottom:8px';
    
    const idHidden = addr ? `<input type="hidden" name="addresses[${idx}][id]" value="${addr.id}">` : '';
    
    row.innerHTML = `
        ${idHidden}
        <div style="flex:1">
            <input type="text" name="addresses[${idx}][address]" class="form-control" value="${addr ? addr.address : ''}" placeholder="العنوان" required>
        </div>
        <div style="width:100px;display:flex;flex-direction:column;align-items:center;gap:4px">
            <label style="font-size:10px">افتراضي</label>
            <input type="radio" name="default_addr_index" value="${idx}" ${addr && addr.is_default ? 'checked' : ''}>
        </div>
        <button type="button" class="btn btn-sm btn-danger" onclick="removeAddressRow(this, ${addr ? addr.id : 'null'})">✕</button>
    `;
    container.appendChild(row);
}

function addAddressRow() {
    const list = document.getElementById('edit-addresses-list');
    if (list.children.length >= 5) {
        showWarning('أقصى عدد للعناوين هو 5');
        return;
    }
    appendAddressRow(list);
}

function removeAddressRow(btn, id) {
    const row = btn.parentElement;
    if (id) {
        // Mark for deletion
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `addresses[${Array.from(row.parentElement.children).indexOf(row)}][_delete]`;
        input.value = '1';
        row.style.display = 'none';
        row.appendChild(input);
    } else {
        row.remove();
    }
}

async function updateClient(e) {
    e.preventDefault();
    const id = document.getElementById('edit-client-id').value;
    const btn = document.getElementById('btn-update-client');
    const form = document.getElementById('form-edit-client');
    
    // Prepare data
    const formData = new FormData(form);
    const data = {};
    
    // Manual parsing for radio buttons and nested addresses
    formData.forEach((value, key) => {
        if (key === 'default_addr_index') {
            const idx = value;
            data[`addresses[${idx}][is_default]`] = 1;
        } else {
            data[key] = value;
        }
    });

    btn.disabled = true;
    btn.innerHTML = '<div class="spin" style="width:14px;height:14px;border-width:2px"></div> جاري الحفظ...';

    try {
        const res = await axios.put(`/callcenter/clients/${id}`, data);
        if (res.data.success) {
            showSuccess(res.data.message);
            closeModal('modal-edit-client');
            loadClients(document.querySelector('.pagination .active')?.innerText || 1);
        }
    } catch (err) {
        if (err.response && err.response.data.errors) {
            const errors = Object.values(err.response.data.errors).flat().join('<br>');
            showError(errors);
        } else {
            showError('حدث خطأ غير متوقع');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = '💾 حفظ التعديلات';
    }
}
</script>

