{{-- Admin Shops page as SPA-injectable partial --}}
<div class="section-header">
    <h2>🏪 إدارة المتاجر</h2>
    <div style="display:flex;gap:10px">
        <button class="btn btn-secondary" onclick="openModal('modal-add-category')">📁 إضافة فئة</button>
        <button class="btn btn-primary" onclick="openModal('modal-add-shop')">➕ إضافة متجر</button>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="text" id="filter-search" class="form-control" placeholder="بحث بالاسم أو الكود" style="min-width:220px">
        <select id="filter-category" class="form-control" style="min-width:180px">
            <option value="">كل الفئات</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" onclick="loadShops(1)">🔍 بحث</button>
        <button class="btn btn-secondary" onclick="resetFilters()">↺ إعادة</button>
    </div>
</div>

<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="table-loading"><div class="spin" style="width:30px;height:30px;border-width:3px"></div></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>الكود</th><th>الاسم</th><th>الفئة</th><th>الهاتف</th><th>العنوان</th><th>عدد الطلبات</th><th>إجمالي المبيعات</th><th>الحالة</th><th>إجراءات</th></tr>
            </thead>
            <tbody id="shops-body">
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="pagination-wrap" style="padding:16px"></div>
</div>

<div class="modal-overlay" id="modal-add-shop">
    <div class="modal">
        <div class="modal-header"><h3>➕ إضافة متجر</h3><button class="btn-close" onclick="closeModal('modal-add-shop')">✕</button></div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="add-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الكود</label><input id="add-code" type="text" class="form-control" placeholder="اختياري"></div>
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
                <div class="form-group"><label class="form-label">الهاتف</label><input id="add-phone" type="text" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">العنوان</label><input id="add-address" type="text" class="form-control"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-shop')">إلغاء</button>
            <button class="btn btn-primary" onclick="addShop()">حفظ</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-edit-shop">
    <div class="modal">
        <div class="modal-header"><h3>✏️ تعديل متجر</h3><button class="btn-close" onclick="closeModal('modal-edit-shop')">✕</button></div>
        <div class="modal-body">
            <input type="hidden" id="edit-id">
            <div class="form-row">
                <div class="form-group"><label class="form-label">الاسم *</label><input id="edit-name" type="text" class="form-control"></div>
                <div class="form-group"><label class="form-label">الكود</label><input id="edit-code" type="text" class="form-control"></div>
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
                <div class="form-group"><label class="form-label">الهاتف</label><input id="edit-phone" type="text" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">العنوان</label><input id="edit-address" type="text" class="form-control"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-edit-shop')">إلغاء</button>
            <button class="btn btn-primary" onclick="saveShop()">حفظ التعديلات</button>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal-overlay" id="modal-add-category">
    <div class="modal">
        <div class="modal-header"><h3>📁 إضافة فئة جديدة</h3><button class="btn-close" onclick="closeModal('modal-add-category')">✕</button></div>
        <div class="modal-body">
            <div class="form-group"><label class="form-label">اسم الفئة *</label><input id="cat-name" type="text" class="form-control" placeholder="مثال: لحوم، خضروات..."></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-add-category')">إلغاء</button>
            <button class="btn btn-primary" onclick="addCategory()">حفظ الفئة</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-view-shop">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>👁 تفاصيل المتجر — <span id="view-shop-name"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-view-shop')">✕</button>
        </div>
        <div class="modal-body">
            <div class="filter-bar" style="margin-bottom:16px">
                <input type="date" id="view-from" class="form-control">
                <input type="date" id="view-to" class="form-control">
                <button class="btn btn-primary" onclick="loadShopDetails(document.getElementById('view-shopid').value)">تحديث</button>
            </div>
            <input type="hidden" id="view-shopid">
            <div id="shop-details-body"><div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div></div>
        </div>
    </div>
</div>

<script>
var currentPage = 1;

function resetFilters() { 
    document.getElementById('filter-search').value = ''; 
    document.getElementById('filter-category').value = ''; 
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
        var body = document.getElementById('shops-body');
        if (!data.data.length) {
            body.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px">لا متاجر</td></tr>';
            return;
        }
        body.innerHTML = data.data.map(s => `<tr id="shop-row-${s.id}">
            <td><code style="color:var(--yellow)">${s.code ?? '—'}</code></td>
            <td><strong>${s.name}</strong></td>
            <td><span class="badge" style="background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main)">${s.category ? s.category.name : '—'}</span></td>
            <td>${s.phone ?? '—'}</td>
            <td>${s.address ?? '—'}</td>
            <td>${s.orders_count ?? 0}</td>
            <td>${parseFloat(s.order_items_sum_total||0).toFixed(2)} ج</td>
            <td>
                <button id="status-btn-${s.id}" onclick="toggleShop(${s.id}, this)"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:11px;font-weight:700;transition:all .2s ease;
                    ${s.is_active ? 'background:rgba(34,197,94,.15);color:var(--success);' : 'background:rgba(220,38,38,.12);color:var(--red);'}"
                    data-active="${s.is_active ? '1' : '0'}">
                    ${s.is_active ? '✓ نشط' : '✗ غير نشط'}
                </button>
            </td>
            <td><div style="display:flex;gap:6px">
                <button class="btn btn-sm btn-info" onclick="viewShop(${s.id}, '${s.name}')">👁</button>
                <button class="btn btn-sm btn-secondary" onclick="openEdit(${s.id},'${s.name.replace(/'/g,"\\'")}','${(s.phone??'').replace(/'/g,"\\'")}','${(s.address??'').replace(/'/g,"\\'")}','${s.shop_category_id??''}','${(s.code??'').replace(/'/g,"\\'")}')">✏️</button>
            </div></td>
        </tr>`).join('');
        renderPagination(data.last_page, data.current_page);
    } catch(e) { console.error(e); }
    finally { document.getElementById('table-loading').classList.remove('show'); }
}

function renderPagination(lastPage, current) {
    if (lastPage <= 1) { document.getElementById('pagination-wrap').innerHTML = ''; return; }
    var html = '<div class="pagination">';
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
            phone: document.getElementById('add-phone').value,
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

function openEdit(id, name, phone, address, categoryId, code) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-code').value = code;
    document.getElementById('edit-phone').value = phone;
    document.getElementById('edit-address').value = address;
    document.getElementById('edit-category').value = categoryId;
    openModal('modal-edit-shop');
}

async function saveShop() {
    var id = document.getElementById('edit-id').value;
    try {
        const { data } = await axios.put(`/admin/shops/${id}`, {
            name: document.getElementById('edit-name').value,
            code: document.getElementById('edit-code').value,
            phone: document.getElementById('edit-phone').value,
            address: document.getElementById('edit-address').value,
            shop_category_id: document.getElementById('edit-category').value,
        });
        showSuccess(data.message); closeModal('modal-edit-shop'); loadShops(currentPage);
    } catch(e) { showError('حدث خطأ'); }
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

async function viewShop(id, name) {
    document.getElementById('view-shopid').value = id;
    document.getElementById('view-shop-name').textContent = name;
    openModal('modal-view-shop');
    loadShopDetails(id);
}

async function loadShopDetails(id) {
    document.getElementById('shop-details-body').innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted)">جاري التحميل...</div>';
    var from = document.getElementById('view-from').value;
    var to = document.getElementById('view-to').value;
    try {
        const { data } = await axios.get(`/admin/shops/${id}`, { params: { from, to } });
        var s = data.shop;
        document.getElementById('shop-details-body').innerHTML = `
            <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
                <div class="kpi-card"><div class="kpi-label">عدد الطلبات</div><div class="kpi-value">${s.orders_count}</div></div>
                <div class="kpi-card blue"><div class="kpi-label">إجمالي المشتريات</div><div class="kpi-value">${parseFloat(s.total_purchases).toFixed(0)}</div><div class="kpi-sub">ج.م</div></div>
                <div class="kpi-card green"><div class="kpi-label">الحالة</div><div class="kpi-value" style="font-size:16px">${s.is_active ? '✅ نشط' : '❌ متوقف'}</div></div>
            </div>
            <div class="card-title" style="margin-bottom:8px">أكثر الأصناف طلباً</div>
            <div class="table-wrap">
                <table><thead><tr><th>الصنف</th><th>الكمية</th><th>القيمة</th></tr></thead>
                <tbody>${s.top_items.length ? s.top_items.map(i => `<tr><td>${i.item_name}</td><td>${i.total_qty}</td><td>${parseFloat(i.total_value).toFixed(2)} ج</td></tr>`).join('') : '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">لا بيانات</td></tr>'}
                </tbody></table>
            </div>
        `;
    } catch(e) { document.getElementById('shop-details-body').innerHTML = '<div style="text-align:center;color:var(--red)">حدث خطأ</div>'; }
}

loadShops(1);
</script>
