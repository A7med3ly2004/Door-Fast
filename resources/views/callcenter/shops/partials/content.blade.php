{{-- Callcenter Shops SPA partial --}}
<div class="section-header"><h2>المتاجر النشطة</h2></div>
<div class="card" style="padding:12px 16px;margin-bottom:14px">
    <div class="filter-bar">
        <input type="text" id="f-search" class="form-control" placeholder="بحث بالاسم أو الكود" style="min-width:200px">
        <select id="f-category" class="form-select" style="min-width:150px">
            <option value="">كل الفئات</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" onclick="loadShops(1)">بحث</button>
        <button class="btn btn-success" onclick="openModal('modal-add-shop')" style="margin-right:auto">إضافة متجر</button>
        <button class="btn btn-secondary" onclick="openModal('modal-add-category')">إضافة فئة</button>
    </div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th style="text-align: center;">الكود</th><th style="text-align: center;">الاسم</th><th style="text-align: center;">الهاتف</th><th style="text-align: center;">العنوان</th><th style="text-align: center;">فئة المتجر</th></tr></thead>
            <tbody id="shops-body"><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</td></tr></tbody>
        </table>
    </div>
    <div id="pg-wrap" style="padding:14px"></div>
</div>

{{-- Add Shop Modal --}}
<div class="modal-overlay" id="modal-add-shop">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة متجر جديد</h3><button class="btn-close" onclick="closeModal('modal-add-shop')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">اسم المتجر *</label><input type="text" id="s-name" class="form-control"></div>
                <div class="form-group"><label class="form-label">كود المتجر</label><input type="text" id="s-code" class="form-control" placeholder="اختياري"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الهاتف</label><input type="text" id="s-phone" class="form-control"></div>
                <div class="form-group"><label class="form-label">الفئة *</label>
                    <select id="s-category" class="form-select">
                        <option value="">اختر الفئة...</option>
                        @foreach($categories as $cat) <option value="{{ $cat->id }}">{{ $cat->name }}</option> @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group"><label class="form-label">العنوان</label><input type="text" id="s-address" class="form-control"></div>
            <div class="form-group"><label class="form-label">ملاحظات</label><textarea id="s-notes" class="form-control" rows="2"></textarea></div>
            <div style="display:flex;justify-content:flex-end;margin-top:15px"><button class="btn btn-primary" onclick="saveShop()">✅ حفظ المتجر</button></div>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal-overlay" id="modal-add-category">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <h3>إضافة فئة جديدة</h3><button class="btn-close" onclick="closeModal('modal-add-category')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-group"><label class="form-label">اسم الفئة *</label><input type="text" id="cat-name" class="form-control" placeholder="مثلاً: مطاعم"></div>
            <div style="display:flex;justify-content:flex-end;margin-top:15px"><button class="btn btn-primary" onclick="saveCategory()">✅ حفظ الفئة</button></div>
        </div>
    </div>
</div>

<script>
async function loadShops(page = 1) {
    document.getElementById('tbl-loading').classList.add('show');
    try {
        const { data } = await axios.get('{{ route("callcenter.shops.index") }}', { 
            params: { 
                search: document.getElementById('f-search').value, 
                category_id: document.getElementById('f-category').value,
                page 
            } 
        });
        var body = document.getElementById('shops-body');
        if (!data.data.length) { body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد متاجر</td></tr>'; document.getElementById('pg-wrap').innerHTML = ''; return; }
        body.innerHTML = data.data.map(s => `<tr><td style="text-align: center;"><code style="color:var(--yellow)">${s.code ?? '—'}</code></td><td style="text-align: center;"><strong>${s.name}</strong></td><td style="text-align: center;">${s.phone ?? '—'}</td><td style="text-align: center;">${s.address ?? '—'}</td><td style="text-align: center;">${s.category?.name ?? '—'}</td></tr>`).join('');
        document.getElementById('pg-wrap').innerHTML = renderPagination(data.last_page, data.current_page, 'loadShops');
    } catch(e) { console.error(e); } finally { document.getElementById('tbl-loading').classList.remove('show'); }
}

async function saveShop() {
    const name = document.getElementById('s-name').value;
    const cat = document.getElementById('s-category').value;
    if(!name || !cat) return showError('يرجى إدخال الاسم والفئة');
    try {
        await axios.post('{{ route("callcenter.shops.store") }}', {
            name,
            code: document.getElementById('s-code').value,
            phone: document.getElementById('s-phone').value,
            address: document.getElementById('s-address').value,
            shop_category_id: cat,
            notes: document.getElementById('s-notes').value
        });
        showSuccess('تم إضافة المتجر بنجاح');
        closeModal('modal-add-shop');
        loadShops(1);
    } catch(e) { showError(e.response?.data?.message || 'حدث خطأ'); }
}

async function saveCategory() {
    const name = document.getElementById('cat-name').value;
    if(!name) return showError('يرجى إدخال اسم الفئة');
    try {
        const { data } = await axios.post('{{ route("callcenter.shop-categories.store") }}', { name });
        showSuccess(data.message);
        closeModal('modal-add-category');
        // Refresh categories dropdowns
        const opt = `<option value="${data.category.id}">${data.category.name}</option>`;
        document.getElementById('s-category').innerHTML += opt;
        document.getElementById('f-category').innerHTML += opt;
    } catch(e) { showError(e.response?.data?.message || 'حدث خطأ'); }
}

loadShops(1);
</script>
