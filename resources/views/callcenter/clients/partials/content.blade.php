{{-- Callcenter Clients SPA partial --}}
<div class="section-header"><h2>قائمة العملاء</h2></div>
<div class="card" style="padding:12px 16px;margin-bottom:14px">
    <div class="filter-bar">
        <input type="text" id="f-search" class="form-control" placeholder="الاسم / الهاتف / الكود" style="min-width:220px">
        <button class="btn btn-primary" onclick="loadClients(1)">بحث</button>
        <button class="btn btn-success" onclick="showAddClientModal()">عميل جديد</button>
    </div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th style="text-align:center;">الاسم</th><th style="text-align:center;">الكود</th><th style="text-align:center;">الهاتف</th><th style="text-align:center;">هاتف 2</th><th style="text-align:center;">العناوين</th><th style="text-align:center;">الطلبات</th><th style="text-align:center;">آخر طلب</th><th style="text-align:center;">الاجراءات</th></tr></thead>
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
            <h3>إضافة عميل جديد</h3>
            <button class="btn-close" onclick="closeModal('modal-add-client')">✕</button>
        </div>
        <form id="form-add-client" onsubmit="saveClient(event)">
            <div class="modal-body" style="padding:24px; background:var(--bg); border-radius:0 0 12px 12px;">
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:20px;">
                    <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        البيانات الأساسية
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:13px;">الاسم الكامل <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="اسم العميل" style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px;">
                    </div>
                    <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size:13px;">رقم الهاتف <span style="color:var(--red)">*</span></label>
                            <input type="text" name="phone" id="new-phone" class="form-control" required placeholder="رقم الموبايل" style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px; direction:ltr; text-align:right;">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:13px;">رقم هاتف إضافي</label>
                            <input type="text" name="phone2" class="form-control" placeholder="اختياري" style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px; direction:ltr; text-align:right;">
                        </div>
                    </div>
                </div>
                
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:12px; padding:20px;">
                    <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        عنوان التوصيل
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:13px;">العنوان بالتفصيل <span style="color:var(--red)">*</span></label>
                        <input type="text" name="first_address" class="form-control" required placeholder="مثال: مدينة نصر - الحي السابع - ش عباس العقاد" style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px;">
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 24px; border-top:1px solid var(--border); background:var(--bg); border-radius:0 0 12px 12px; gap:12px; display:flex; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-client')" style="padding:10px 24px; border-radius:8px;">إلغاء</button>
                <button type="submit" class="btn btn-success" id="btn-save-client" style="padding:10px 24px; border-radius:8px; font-weight:600;">حفظ البيانات</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Client Modal --}}
<div class="modal-overlay" id="modal-edit-client">
    <div class="modal">
        <div class="modal-header">
            <h3>تعديل بيانات العميل</h3>
            <button class="btn-close" onclick="closeModal('modal-edit-client')">✕</button>
        </div>
        <form id="form-edit-client" onsubmit="updateClient(event)">
            <input type="hidden" name="id" id="edit-client-id">
            <div class="modal-body" style="padding:24px; background:var(--bg); border-radius:0 0 12px 12px;">
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:20px;">
                    <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        البيانات الأساسية
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:13px;">الاسم الكامل <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-control" required style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px;">
                    </div>
                    <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size:13px;">رقم الهاتف <span style="color:var(--red)">*</span></label>
                            <input type="text" name="phone" id="edit-phone" class="form-control" required style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px; direction:ltr; text-align:right;">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:13px;">رقم هاتف إضافي</label>
                            <input type="text" name="phone2" id="edit-phone2" class="form-control" style="background:var(--bg); border:1px solid var(--border); padding:10px 14px; border-radius:8px; direction:ltr; text-align:right;">
                        </div>
                    </div>
                </div>
                
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:12px; padding:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <div style="font-size:14px; font-weight:700; color:var(--text-muted); display:flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            العناوين المُسجلة
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addAddressRow()" style="display:flex; align-items:center; gap:4px; border-radius:6px; padding:6px 10px;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            إضافة عنوان
                        </button>
                    </div>
                    <div id="edit-addresses-list" style="display:flex; flex-direction:column; gap:12px;">
                        <!-- Dynamic address rows -->
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 24px; border-top:1px solid var(--border); background:var(--bg); border-radius:0 0 12px 12px; gap:12px; display:flex; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-client')" style="padding:10px 24px; border-radius:8px;">إلغاء</button>
                <button type="submit" class="btn btn-primary" id="btn-update-client" style="padding:10px 24px; border-radius:8px; font-weight:600;">حفظ التعديلات</button>
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
        body.innerHTML = data.data.map(c => `<tr style="cursor:pointer" onclick="viewClient(${c.id})"><td style="text-align:center;"><strong>${c.name}</strong></td><td style="text-align:center;"><span class="badge badge-gray">${c.code}</span></td><td style="text-align:center;">${c.phone}</td><td style="text-align:center;">${c.phone2 ?? '—'}</td><td style="text-align:center;">${c.addresses_count}</td><td style="text-align:center;">${c.orders_count}</td><td style="text-align:center;font-size:11px;color:var(--text-muted)">${c.last_order_at ? formatDate(c.last_order_at) : '—'}</td><td style="text-align:center;"><div style="display:flex;gap:5px;justify-content:center;"><button class="btn btn-sm btn-info" onclick="event.stopPropagation();viewClient(${c.id})">عرض</button><button class="btn btn-sm btn-primary" onclick="event.stopPropagation();editClient(${c.id})">تعديل</button></div></td></tr>`).join('');
        document.getElementById('pg-wrap').innerHTML = renderPagination(data.last_page, data.current_page, 'loadClients');
    } catch(e) { console.error(e); } finally { document.getElementById('tbl-loading').classList.remove('show'); }
}
async function viewClient(id) {
    openModal('modal-view'); 
    document.getElementById('view-body').innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;padding:40px;color:var(--text-muted);"><div class="spin" style="margin-bottom:16px;"></div><div>جاري تحميل بيانات العميل...</div></div>';
    try {
        const { data } = await axios.get(`/callcenter/clients/${id}`); const c = data.client;
        
        let html = `<div style="display:flex; flex-direction:column; gap:16px; margin-bottom: 20px;">`;
        
        html += `<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px;">`;
        
        html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                البيانات الأساسية
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الاسم</span>
                    <span style="font-weight:600;">${c.name}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الكود</span>
                    <span style="font-weight:600; color:var(--yellow);">${c.code}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الهاتف الأساسي</span>
                    <span style="font-weight:600; direction:ltr;">${c.phone}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted); font-size:13px;">الهاتف الإضافي</span>
                    <span style="font-weight:600; direction:ltr;">${c.phone2 ?? '—'}</span>
                </div>
            </div>
        </div>`;
        
        html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                الإحصائيات
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">إجمالي الطلبات</span>
                    <span style="font-weight:700; font-size:16px;">${c.orders_count}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted); font-size:13px;">تاريخ التسجيل</span>
                    <span style="font-weight:600;">${formatDate(c.created_at)}</span>
                </div>
            </div>
        </div>`;
        html += `</div>`;
        
        html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                عناوين التوصيل (${c.addresses.length}/5)
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                ${c.addresses.map(a => `<div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; background:rgba(255,255,255,0.02); border:1px solid var(--border);">
                    <div style="color:${a.is_default ? 'var(--yellow)' : 'var(--text-muted)'}">
                        <svg width="20" height="20" fill="${a.is_default ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    </div>
                    <div style="flex:1; font-size:13px; font-weight:500;">
                        ${a.address}
                    </div>
                    ${a.is_default ? '<span style="font-size:11px; padding:2px 8px; border-radius:12px; background:rgba(245,158,11,0.1); color:var(--yellow); font-weight:700;">الأساسي</span>' : ''}
                </div>`).join('') || '<div style="color:var(--text-muted); font-size:13px; text-align:center; padding:10px;">لا توجد عناوين مسجلة لهذا العميل</div>'}
            </div>
        </div>`;

        html += `<div style="background:var(--bg); border-radius:12px; border:1px solid var(--border); overflow:hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="padding:12px 16px; background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); font-size:14px; font-weight:700; color:var(--text-muted); display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                آخر 5 طلبات
            </div>
            <div class="table-wrap" style="margin:0; border:none; border-radius:0;">
                <table style="margin:0; width:100%; border-collapse:collapse;">
                    <thead style="background:transparent;">
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="padding:10px 16px; text-align:center;">رقم الطلب</th>
                            <th style="padding:10px 16px; text-align:center;">الحالة</th>
                            <th style="padding:10px 16px; text-align:center;">الإجمالي</th>
                            <th style="padding:10px 16px; text-align:center;">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${c.orders.map(o => `<tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:12px 16px; text-align:center; font-weight:700; color:var(--yellow);">${o.order_number}</td>
                            <td style="padding:12px 16px; text-align:center;">${statusBadge(o.status)}</td>
                            <td style="padding:12px 16px; text-align:center; font-weight:600;">${parseFloat(o.total).toFixed(2)} ج</td>
                            <td style="padding:12px 16px; text-align:center; font-size:12px; color:var(--text-muted);">${formatDate(o.created_at)}</td>
                        </tr>`).join('') || '<tr><td colspan="4" style="text-align:center; padding:30px; color:var(--text-muted);">لا توجد طلبات سابقة</td></tr>'}
                    </tbody>
                </table>
            </div>
        </div>`;
        
        html += `</div>`;

        document.getElementById('view-body').innerHTML = html;
    } catch(e) { 
        document.getElementById('view-body').innerHTML = `<div style="padding:40px; text-align:center;">
            <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:rgba(255,0,0, 0.1); color:var(--red); margin-bottom:16px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 style="margin-bottom:8px;">عذراً، حدث خطأ</h3>
            <p style="color:var(--text-muted); font-size:14px;">لم نتمكن من جلب بيانات العميل. يرجى المحاولة مرة أخرى.</p>
        </div>`;
    }
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
    row.className = 'address-row';
    row.style = 'display:flex; gap:12px; align-items:center; padding:12px; background:var(--bg); border:1px solid var(--border); border-radius:8px; transition:all 0.2s;';
    
    const idHidden = addr ? `<input type="hidden" name="addresses[${idx}][id]" value="${addr.id}">` : '';
    
    row.innerHTML = `
        ${idHidden}
        <div style="flex:1;">
            <input type="text" name="addresses[${idx}][address]" class="form-control" value="${addr ? addr.address : ''}" placeholder="أدخل العنوان بالتفصيل..." required style="background:rgba(255,255,255,0.02); border:1px solid var(--border); padding:10px 14px; border-radius:6px; width:100%;">
        </div>
        <div style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.02); border:1px solid var(--border); padding:8px 12px; border-radius:6px; cursor:pointer;" onclick="this.querySelector('input').click()">
            <input type="radio" name="default_addr_index" value="${idx}" ${addr && addr.is_default ? 'checked' : ''} style="margin:0; cursor:pointer;">
            <span style="font-size:12px; color:var(--text-muted); font-weight:600; cursor:pointer;">الأساسي</span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" onclick="removeAddressRow(this, ${addr ? addr.id : 'null'})" style="padding:8px; border-radius:6px; display:flex; align-items:center; justify-content:center;" title="حذف العنوان">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
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
    const defaultIdx = formData.get('default_addr_index');
    if (defaultIdx !== null) {
        formData.append(`addresses[${defaultIdx}][is_default]`, '1');
    }
    formData.append('_method', 'PUT');

    btn.disabled = true;
    btn.innerHTML = '<div class="spin" style="width:14px;height:14px;border-width:2px"></div> جاري الحفظ...';

    try {
        const res = await axios.post(`/callcenter/clients/${id}`, formData);
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

