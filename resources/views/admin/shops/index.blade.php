@extends('layouts.admin')

@section('page-title', 'المتاجر')

@section('content')
<div class="section-header">
    <h2>إدارة المتاجر</h2>
    <div style="display:flex;gap:10px">
        <button class="btn btn-secondary" onclick="openModal('modal-add-category')">إضافة فئة</button>
        <button class="btn btn-primary" onclick="openModal('modal-add-shop')">إضافة متجر</button>
        <button class="btn btn-success" onclick="exportShopsExcel()" style="background:#217346;color:#fff;">تصدير Excel</button>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="filter-bar" style="align-items: flex-end;">
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">بحث</label>
            <input type="text" id="filter-search" class="form-control" placeholder="بحث بالاسم أو الكود أو رقم الهاتف" style="min-width:280px" onkeydown="if(event.key==='Enter') loadShops(1)">
        </div>
        <div>
            <label class="form-label" style="font-size: 12px; margin-bottom: 2px;">الفئة</label>
            <div class="relative group" style="min-width:180px; z-index: 50;">
                <div class="form-control" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
                    <span id="label-filter-category">كل الفئات</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <input type="hidden" id="filter-category" value="">
                <div class="absolute top-full right-0 w-full opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all bg-white/80 backdrop-blur shadow-lg rounded-md mt-1 overflow-hidden" style="border:1px solid var(--border); background-color: rgba(255, 255, 255, 0.9); max-height:200px; overflow-y:auto;">
                    <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-category', '', 'كل الفئات')">كل الفئات</div>
                    @foreach($categories as $cat)
                        <div class="px-3 py-2 cursor-pointer hover:bg-green-50 hover:text-green-700 text-sm transition-colors text-gray-800" onclick="selectDropdown('filter-category', '{{ $cat->id }}', '{{ $cat->name }}')">{{ $cat->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 5px; margin-bottom: 2px;">
            <button class="btn btn-primary" onclick="loadShops(1)">بحث</button>
            <button class="btn btn-secondary" onclick="resetFilters()">إعادة</button>
        </div>
    </div>
</div>

<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="table-loading"><div class="spin" style="width:30px;height:30px;border-width:3px"></div></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">الكود</th>
                    <th style="text-align: center;">الاسم</th>
                    <th style="text-align: center;">الفئة</th>
                    <th style="text-align: center;">الهاتف</th>
                    <th style="text-align: right;">العنوان</th>
                    <th style="text-align: center;">عدد الطلبات</th>
                    <th style="text-align: center;">اجمالي المشتريات</th>
                    <th style="text-align: center;">الحالة</th>
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="shops-body">
                <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="pagination-wrap" style="padding:16px"></div>
</div>

{{-- Add Modal --}}
<div class="modal-overlay" id="modal-add-shop">
    <div class="modal">
        <div class="modal-header"><h3>إضافة متجر</h3><button class="btn-close" onclick="closeModal('modal-add-shop')">✕</button></div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الكود</label><input id="add-code" type="text" class="form-control" placeholder="تلقائي..." readonly style="background: var(--bg-light); cursor: not-allowed;"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">الفئة *</label>
                    <select id="add-category" class="form-control">
                        <option value="">اختر الفئة...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">العنوان</label><input id="add-address" type="text" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-shop')">إلغاء</button>
            <button class="btn btn-primary" onclick="addShop()">حفظ</button>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="modal-edit-shop">
    <div class="modal" style="max-width:620px">
        <div class="modal-header"><h3>تعديل متجر</h3><button class="btn-close" onclick="closeModal('modal-edit-shop')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الكود</label><input id="edit-code" type="text" class="form-control" readonly style="background: var(--bg-light); cursor: not-allowed;"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">الفئة *</label>
                    <select id="edit-category" class="form-control">
                        <option value="">اختر الفئة...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">العنوان</label><input id="edit-address" type="text" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الهاتف الأساسي</label><input id="edit-phone" type="text" class="form-control" placeholder="الهاتف الرئيسي"></div>
                <div class="form-group"><label class="form-label">هاتف 2</label><input id="edit-phone2" type="text" class="form-control" placeholder="اختياري"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">هاتف 3</label><input id="edit-phone3" type="text" class="form-control" placeholder="اختياري"></div>
                <div class="form-group"><label class="form-label">هاتف 4</label><input id="edit-phone4" type="text" class="form-control" placeholder="اختياري"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-shop')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveShop()">حفظ التعديلات</button>
        </div>
    </div>
</div>

{{-- View Modal --}}
<div class="modal-overlay" id="modal-view-shop">
    <div class="modal" style="max-width:680px">
        <div class="modal-header">
            <h3><span id="view-shop-name">—</span></h3>
            <button class="btn-close" onclick="closeModal('modal-view-shop')">✕</button>
        </div>
        <div class="modal-body" id="view-modal-body">
            <div style="display:flex;align-items:center;justify-content:center;padding:30px">
                <div class="spin"></div>
            </div>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal-overlay" id="modal-add-category">
    <div class="modal">
        <div class="modal-header"><h3>إضافة فئة جديدة</h3><button class="btn-close" onclick="closeModal('modal-add-category')">✕</button></div>
        <div class="modal-body">
            <div class="form-group"><label class="form-label">اسم الفئة *</label><input id="cat-name" type="text" class="form-control" placeholder="مثال: لحوم، خضروات..."></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-category')">إلغاء</button>
            <button class="btn btn-primary" onclick="addCategory()">حفظ الفئة</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var currentPage = 1;

function resetFilters() { 
    document.getElementById('filter-search').value = ''; 
    document.getElementById('filter-category').value = ''; 
    
    const catLabel = document.getElementById('label-filter-category');
    if (catLabel) catLabel.innerText = 'كل الفئات';
    
    loadShops(1); 
}

async function loadShops(page = 1) {
    currentPage = page;
    document.getElementById('table-loading').classList.add('show');
    try {
        const { data } = await axios.get('{{ route("admin.shops.index") }}', {
            params: { 
                search: document.getElementById('filter-search').value, 
                category_id: document.getElementById('filter-category').value,
                page 
            },
            headers: { 'Accept': 'application/json' }
        });
        const body = document.getElementById('shops-body');
        if (!data.data.length) {
            body.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">لا متاجر</td></tr>';
            return;
        }
        body.innerHTML = data.data.map(s => `<tr id="shop-row-${s.id}">
            <td style="text-align: center;"><code style="color:var(--yellow)">${s.code ?? '—'}</code></td>
            <td style="text-align: center;"><strong>${s.name}</strong></td>
            <td style="text-align: center;"><span class="badge" style="background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main)">${s.category ? s.category.name : '—'}</span></td>
            <td style="text-align: center;">${s.phone ?? '—'}</td>
            <td style="text-align: right;">${s.address ?? '—'}</td>
            <td style="text-align: center;">${s.orders_count ?? 0}</td>
            <td style="text-align: center;">${parseFloat(s.order_items_sum_total||0).toFixed(2)} ج</td>
            <td style="text-align: center;">
                <button id="status-btn-${s.id}" onclick="toggleShop(${s.id}, this)"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:11px;font-weight:700;transition:all .2s ease;
                    ${s.is_active ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);'}"
                    data-active="${s.is_active ? '1' : '0'}">
                    ${s.is_active ? '✓ نشط' : '✗ غير نشط'}
                </button>
            </td>
            <td style="text-align: center;">
                <div style="display:inline-flex;gap:6px;align-items:center;">
                    <button class="btn btn-sm btn-secondary" onclick="viewShop(${s.id},'${s.name.replace(/'/g,"\\'")}')">عرض</button>
                    <button class="btn btn-sm btn-secondary" onclick="openEdit(${s.id},'${s.name.replace(/'/g,"\\'")}','${(s.phone??'').replace(/'/g,"\\'")}','${(s.phone2??'').replace(/'/g,"\\'")}','${(s.phone3??'').replace(/'/g,"\\'")}','${(s.phone4??'').replace(/'/g,"\\'")}','${(s.address??'').replace(/'/g,"\\'")}','${(s.shop_category_id??'')}','${(s.code??'').replace(/'/g,"\\'")}')">تعديل</button>
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
    html += `<a class="${current===1?'disabled':''}" onclick="loadShops(${current-1})">‹</a>`;
    for (let i=1;i<=lastPage;i++) {
        if (i===1||i===lastPage||Math.abs(i-current)<=2) html += `<a class="${i===current?'active':''}" onclick="loadShops(${i})">${i}</a>`;
        else if (Math.abs(i-current)===3) html += '<span>…</span>';
    }
    html += `<a class="${current===lastPage?'disabled':''}" onclick="loadShops(${current+1})">›</a></div>`;
    document.getElementById('pagination-wrap').innerHTML = html;
}

async function addShop() {
    try {
        const { data } = await axios.post('{{ route("admin.shops.store") }}', {
            name: document.getElementById('add-name').value,
            code: document.getElementById('add-code').value,
            address: document.getElementById('add-address').value,
            shop_category_id: document.getElementById('add-category').value,
        });
        showSuccess(data.message); closeModal('modal-add-shop'); loadShops(1);
    } catch(e) { showError(e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' | ') : 'حدث خطأ'); }
}

async function addCategory() {
    const name = document.getElementById('cat-name').value;
    if(!name) return showError('يرجى إدخال اسم الفئة');
    try {
        const { data } = await axios.post('{{ route("admin.shop-categories.store") }}', { name });
        showSuccess('تم إضافة الفئة بنجاح');
        
        // Update dropdowns
        const option = `<option value="${data.category.id}">${data.category.name}</option>`;
        document.getElementById('add-category').insertAdjacentHTML('beforeend', option);
        document.getElementById('edit-category').insertAdjacentHTML('beforeend', option);
        
        document.getElementById('cat-name').value = '';
        closeModal('modal-add-category');
    } catch(e) { showError('حدث خطأ أو الفئة موجودة بالفعل'); }
}

function openEdit(id, name, phone, phone2, phone3, phone4, address, categoryId, code) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-code').value = code;
    document.getElementById('edit-phone').value = phone;
    document.getElementById('edit-phone2').value = phone2;
    document.getElementById('edit-phone3').value = phone3;
    document.getElementById('edit-phone4').value = phone4;
    document.getElementById('edit-address').value = address;
    document.getElementById('edit-category').value = categoryId;
    openModal('modal-edit-shop');
}

async function saveShop() {
    const id = document.getElementById('edit-id').value;
    try {
        const { data } = await axios.put(`/admin/shops/${id}`, {
            name: document.getElementById('edit-name').value,
            code: document.getElementById('edit-code').value,
            phone: document.getElementById('edit-phone').value,
            phone2: document.getElementById('edit-phone2').value,
            phone3: document.getElementById('edit-phone3').value,
            phone4: document.getElementById('edit-phone4').value,
            address: document.getElementById('edit-address').value,
            shop_category_id: document.getElementById('edit-category').value,
        });
        showSuccess(data.message); closeModal('modal-edit-shop'); loadShops(currentPage);
    } catch(e) { showError('حدث خطأ'); }
}

async function viewShop(id, name) {
    document.getElementById('view-shop-name').textContent = name;
    document.getElementById('view-modal-body').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;padding:30px"><div class="spin"></div></div>';
    openModal('modal-view-shop');
    try {
        const { data } = await axios.get(`/admin/shops/${id}`);
        const s = data.shop;
        const phones = [s.phone, s.phone2, s.phone3, s.phone4].filter(Boolean);
        const phonesHtml = phones.length
            ? phones.map(p => `<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:6px;padding:4px 10px;font-size:13px;">${p}</span>`).join('')
            : '<span style="color:var(--text-muted)">—</span>';

        document.getElementById('view-modal-body').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
                <div style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:10px;padding:14px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:#3b82f6">${s.orders_count ?? 0}</div>
                    <div style="font-size:12px;color:var(--text-muted)">عدد الطلبات (30 يوم)</div>
                </div>
                <div style="background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.15);border-radius:10px;padding:14px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:var(--success)">${parseFloat(s.total_purchases||0).toFixed(2)} ج</div>
                    <div style="font-size:12px;color:var(--text-muted)">إجمالي المشتريات (30 يوم)</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                <div><div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">الحالة</div>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;${s.is_active ? 'background:rgba(34,197,94,.15);color:var(--success)' : 'background:rgba(220,38,38,.12);color:var(--red)'}">${s.is_active ? '✓ نشط' : '✗ غير نشط'}</span>
                </div>
                <div><div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">العنوان</div>
                    <div style="font-size:13px">${s.address || '—'}</div>
                </div>
            </div>
            <div style="margin-bottom:14px">
                <div style="font-size:11px;color:var(--text-muted);margin-bottom:6px">أرقام الهواتف</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px">${phonesHtml}</div>
            </div>
            ${s.notes ? `<div style="margin-bottom:14px"><div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">ملاحظات</div><div style="font-size:13px;background:var(--bg-light);border-radius:6px;padding:8px 12px">${s.notes}</div></div>` : ''}`;
    } catch(e) { console.error(e); document.getElementById('view-modal-body').innerHTML = '<p style="color:var(--red);text-align:center;padding:20px">حدث خطأ في التحميل</p>'; }
}

async function toggleShop(id, btn) {
    const isCurrentlyActive = btn.dataset.active === '1';
    const newState = isCurrentlyActive ? 0 : 1;
    applyStatusBtn(btn, newState);
    try {
        const { data } = await axios.patch(`/admin/shops/${id}/toggle`);
        showSuccess(data.message);
    } catch(e) { 
        applyStatusBtn(btn, isCurrentlyActive ? 1 : 0);
        showError('حدث خطأ'); 
    }
}

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


loadShops(1);

async function exportShopsExcel() {
    try {
        const { data } = await axios.get('{{ route("admin.shops.index") }}', {
            params: {
                search:      document.getElementById('filter-search').value,
                category_id: document.getElementById('filter-category').value,
                per_page:    9999
            },
            headers: { 'Accept': 'application/json' }
        });
        const columns = [
            { header: 'الاسم',             key: 'name',                    width: 22 },
            { header: 'الكود',             key: 'code',                    width: 12 },
            { header: 'الفئة',             key: 'category.name',           width: 18 },
            { header: 'الهاتف',            key: 'phone',                   width: 16 },
            { header: 'هاتف 2',            key: 'phone2',                  width: 16 },
            { header: 'هاتف 3',            key: 'phone3',                  width: 16 },
            { header: 'هاتف 4',            key: 'phone4',                  width: 16 },
            { header: 'العنوان',           key: 'address',                 width: 28 },
            { header: 'عدد الطلبات',     key: 'orders_count',            width: 14 },
            { header: 'اجمالي المشتريات', key: 'order_items_sum_total',   width: 18 },
            { header: 'الحالة',            key: 'is_active',               width: 12 },
        ];
        const rows = data.data.map(s => ({
            ...s,
            is_active: s.is_active ? 'نشط' : 'متوقف',
            order_items_sum_total: parseFloat(s.order_items_sum_total || 0).toFixed(2),
        }));
        exportToExcel(rows, columns, 'shops-' + new Date().toISOString().slice(0, 10), 'المتاجر');
        showSuccess('تم التصدير');
    } catch (e) {
        showError('حدث خطأ');
        console.error(e);
    }
}
</script>
@endpush
