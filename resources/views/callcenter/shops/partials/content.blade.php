{{-- Callcenter Shops SPA partial --}}
<div class="section-header">
    <h2>المتاجر النشطة</h2>
</div>
<div class="card" style="padding:12px 16px;margin-bottom:14px">
    <div class="filter-bar">
        <input type="text" id="f-search" class="form-control" placeholder="بحث بالاسم أو الكود أو رقم الهاتف"
            style="min-width:280px" onkeydown="if(event.key==='Enter') loadShops(1)">
        <button class="btn btn-primary" onclick="loadShops(1)">بحث</button>
        <button class="btn btn-success" onclick="openModal('modal-add-shop')" style="margin-right:auto">إضافة
            متجر</button>
        <button class="btn btn-secondary" onclick="openModal('modal-add-category')">إضافة فئة</button>
    </div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading">
        <div class="spin"></div>
    </div>
    <div class="table-wrap" id="shops-table-wrap" style="display:none">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">الكود</th>
                    <th style="text-align: center;">الاسم</th>
                    <th style="text-align: center;">الهاتف</th>
                    <th style="text-align: center;">العنوان</th>
                    <th style="text-align: center;">فئة المتجر</th>
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="shops-body"></tbody>
        </table>
    </div>
    <div id="shops-prompt" style="text-align:center;padding:40px;color:var(--text-muted);font-size:14px">
        ابدأ بالبحث عن متجر بالاسم أو الكود أو رقم الهاتف
    </div>
    <div id="pg-wrap" style="padding:14px"></div>
</div>

{{-- View Shop Modal --}}
<div class="modal-overlay" id="modal-view-shop-cc">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <h3><span id="cc-view-shop-name">—</span></h3>
            <button class="btn-close" onclick="closeModal('modal-view-shop-cc')">✕</button>
        </div>
        <div class="modal-body" id="cc-view-modal-body">
            <div style="display:flex;align-items:center;justify-content:center;padding:30px">
                <div class="spin"></div>
            </div>
        </div>
    </div>
</div>

{{-- Add Shop Modal --}}
<div class="modal-overlay" id="modal-add-shop">
    <div class="modal">
        <div class="modal-header">
            <h3>إضافة متجر جديد</h3><button class="btn-close" onclick="closeModal('modal-add-shop')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label">اسم المتجر *</label><input type="text" id="s-name"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">كود المتجر</label><input type="text" id="s-code"
                        class="form-control" placeholder="اختياري"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">الهاتف</label><input type="text" id="s-phone"
                        class="form-control"></div>
                <div class="form-group"><label class="form-label">الفئة *</label>
                    <select id="s-category" class="form-select">
                        <option value="">اختر الفئة...</option>
                        @foreach($categories as $cat) <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group"><label class="form-label">العنوان</label><input type="text" id="s-address"
                    class="form-control"></div>
            <div class="form-group"><label class="form-label">ملاحظات</label><textarea id="s-notes" class="form-control"
                    rows="2"></textarea></div>
            <div style="display:flex;justify-content:flex-end;margin-top:15px"><button class="btn btn-primary"
                    onclick="saveShop()">✅ حفظ المتجر</button></div>
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
            <div class="form-group"><label class="form-label">اسم الفئة *</label><input type="text" id="cat-name"
                    class="form-control" placeholder="مثلاً: مطاعم"></div>
            <div style="display:flex;justify-content:flex-end;margin-top:15px"><button class="btn btn-primary"
                    onclick="saveCategory()">✅ حفظ الفئة</button></div>
        </div>
    </div>
</div>

<script>
    async function loadShops(page = 1) {
        const search = document.getElementById('f-search').value.trim();
        if (!search) {
            document.getElementById('shops-table-wrap').style.display = 'none';
            document.getElementById('shops-prompt').style.display = 'block';
            document.getElementById('shops-prompt').textContent = 'ابدأ بالبحث عن متجر بالاسم أو الكود أو رقم الهاتف';
            document.getElementById('pg-wrap').innerHTML = '';
            return;
        }
        document.getElementById('tbl-loading').classList.add('show');
        try {
            const { data } = await axios.get('{{ route("callcenter.shops.index") }}', {
                params: { search, page }
            });
            document.getElementById('shops-prompt').style.display = 'none';
            var body = document.getElementById('shops-body');
            if (!data.data.length) {
                document.getElementById('shops-table-wrap').style.display = 'none';
                document.getElementById('shops-prompt').style.display = 'block';
                document.getElementById('shops-prompt').textContent = 'لا توجد متاجر مطابقة للبحث';
                document.getElementById('pg-wrap').innerHTML = '';
                return;
            }
            document.getElementById('shops-table-wrap').style.display = '';
            body.innerHTML = data.data.map(s => `<tr>
                <td style="text-align: center;"><code style="color:var(--yellow)">${s.code ?? '—'}</code></td>
                <td style="text-align: center;"><strong>${s.name}</strong></td>
                <td style="text-align: center;">${s.phone ?? '—'}</td>
                <td style="text-align: center;">${s.address ?? '—'}</td>
                <td style="text-align: center;">${s.category?.name ?? '—'}</td>
                <td style="text-align: center;">
                    <button class="btn btn-sm btn-secondary" onclick="viewShopCC(${s.id},'${s.name.replace(/'/g, "\\'")}')">عرض</button>
                </td>
            </tr>`).join('');
            document.getElementById('pg-wrap').innerHTML = renderPagination(data.last_page, data.current_page, 'loadShops');
        } catch (e) { console.error(e); } finally { document.getElementById('tbl-loading').classList.remove('show'); }
    }

    async function viewShopCC(id, name) {
        document.getElementById('cc-view-shop-name').textContent = name;
        document.getElementById('cc-view-modal-body').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;padding:30px"><div class="spin"></div></div>';
        openModal('modal-view-shop-cc');
        try {
            const { data } = await axios.get(`/callcenter/shops/${id}`);
            const s = data.shop;
            const phones = [s.phone, s.phone2, s.phone3, s.phone4].filter(Boolean);
            const phonesHtml = phones.length
                ? phones.map(p => `<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:6px;padding:4px 10px;font-size:13px;">📞 ${p}</span>`).join('')
                : '<span style="color:var(--text-muted)">لا يوجد هاتف</span>';

            document.getElementById('cc-view-modal-body').innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
                    <div style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:10px;padding:14px;text-align:center">
                        <div style="font-size:22px;font-weight:700;color:#3b82f6">${s.orders_count ?? 0}</div>
                        <div style="font-size:12px;color:var(--text-muted)">عدد الطلبات (30 يوم)</div>
                    </div>
                    <div style="background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.15);border-radius:10px;padding:14px;text-align:center">
                        <div style="font-size:22px;font-weight:700;color:var(--success)">${parseFloat(s.total_purchases || 0).toFixed(2)} ج</div>
                        <div style="font-size:12px;color:var(--text-muted)">إجمالي المشتريات (30 يوم)</div>
                    </div>
                </div>
                <div style="margin-bottom:14px">
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:6px">أرقام الهواتف</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px">${phonesHtml}</div>
                </div>
                <div style="margin-bottom:14px">
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">العنوان</div>
                    <div style="font-size:13px">${s.address || '—'}</div>
                </div>
                ${s.notes ? `<div><div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">ملاحظات</div><div style="font-size:13px;background:var(--bg-light);border-radius:6px;padding:8px 12px">${s.notes}</div></div>` : ''}`;
        } catch (e) {
            console.error(e);
            document.getElementById('cc-view-modal-body').innerHTML = '<p style="color:var(--red);text-align:center;padding:20px">حدث خطأ في التحميل</p>';
        }
    }

    async function saveShop() {
        const name = document.getElementById('s-name').value;
        const cat = document.getElementById('s-category').value;
        if (!name || !cat) return showError('يرجى إدخال الاسم والفئة');
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
        } catch (e) { showError(e.response?.data?.message || 'حدث خطأ'); }
    }

    async function saveCategory() {
        const name = document.getElementById('cat-name').value;
        if (!name) return showError('يرجى إدخال اسم الفئة');
        try {
            const { data } = await axios.post('{{ route("callcenter.shop-categories.store") }}', { name });
            showSuccess(data.message);
            closeModal('modal-add-category');
            // Refresh categories dropdowns
            const opt = `<option value="${data.category.id}">${data.category.name}</option>`;
            document.getElementById('s-category').innerHTML += opt;
            document.getElementById('f-category').innerHTML += opt;
        } catch (e) { showError(e.response?.data?.message || 'حدث خطأ'); }
    }

    // لا تحميل تلقائي عند فتح الصفحة
</script>