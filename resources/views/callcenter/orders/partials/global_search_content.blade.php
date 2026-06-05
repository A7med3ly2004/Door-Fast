<div class="section-header">
    <h2>بحث الطلبات الشامل</h2>
</div>
<div class="card" style="margin-bottom:16px">
    <div class="filter-bar">
        <input type="text" id="f-g-search" class="form-control" placeholder="رقم الطلب / العميل / الهاتف"
            style="min-width:300px" value="{{ request('q') }}">
        <button class="btn btn-primary" onclick="loadGlobalList()">بحث</button>
    </div>
</div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading-g">
        <div class="spin"></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center;">رقم الطلب</th>
                    <th style="text-align:center;">التاريخ</th>
                    <th style="text-align:center;">العميل</th>
                    <th style="text-align:center;">الهاتف</th>
                    <th style="text-align:center;">تم انشائه</th>
                    <th style="text-align:center;">المندوب</th>
                    <th style="text-align:center;">المتاجر</th>
                    <th style="text-align:center;">قيمة التوصيل</th>
                    <th style="text-align:center;">الإجمالي</th>
                    <th style="text-align:center;">الحالة</th>
                    <th style="text-align:center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="global-orders-body">
                <tr>
                    <td colspan="11" style="text-align:center;padding:40px;color:var(--text-muted)">أدخل كود للبحث...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modal-view">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <h3>تفاصيل الطلب — <span id="view-num"></span></h3><a id="modal-pdf-btn" href="#" target="_blank"
                    class="btn btn-sm btn-secondary" onclick="if(this.href==='#'){event.preventDefault();}"
                    style="background-color: #c92f2f;">إنشاء PDF</a>
            </div><button class="btn-close" onclick="closeModal('modal-view')">✕</button>
        </div>
        <div class="modal-body" id="view-body"></div>
    </div>
</div>

<script>
    async function loadGlobalList() {
        var search = document.getElementById('f-g-search').value.trim();
        if (!search) return;

        document.getElementById('tbl-loading-g').classList.add('show');
        try {
            const { data } = await axios.get('{{ route("callcenter.orders.global-search") }}', { params: { search } });
            var body = document.getElementById('global-orders-body');
            if (!data.length) { body.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px;color:var(--text-muted)">لا توجد نتائج</td></tr>'; return; }

            body.innerHTML = data.map(o => {
                const roleLabel = o.created_by_role === 'admin'
                    ? `<span style="font-size:10px;background:rgba(168,85,247,0.15);color:#a855f7;padding:2px 6px;border-radius:6px;margin-right:4px;">مدير</span>`
                    : `<span style="font-size:10px;background:rgba(var(--primary-rgb, 59,130,246),0.15);color:var(--primary);padding:2px 6px;border-radius:6px;margin-right:4px;">كول سنتر</span>`;
                return `<tr>
                    <td style="text-align:center;"><strong style="color:var(--yellow)">${o.order_number}</strong></td>
                    <td style="text-align:center;font-size:11px;color:var(--text-muted)">${formatDate(o.created_at)}</td>
                    <td style="text-align:center;">${o.client_name}</td>
                    <td style="text-align:center;">${o.client_phone}</td>
                    <td style="text-align:center;">${o.created_by_name}</td>
                    <td style="text-align:center;">${o.delivery_name}</td>
                    <td style="text-align:center;">${o.shops_count}</td>
                    <td style="text-align:center;">${parseFloat(o.delivery_fee).toFixed(2)} ج</td>
                    <td style="text-align:center;">${parseFloat(o.total).toFixed(2)} ج</td>
                    <td style="text-align:center;">${statusBadge(o.status)}</td>
                    <td style="text-align:center;"><button class="btn btn-sm btn-info" onclick="viewOrder(${o.id})">عرض</button></td>
                </tr>`;
            }).join('');
        } catch (e) { console.error(e); } finally { document.getElementById('tbl-loading-g').classList.remove('show'); }
    }

    async function viewOrder(id) {
        openModal('modal-view');
        document.getElementById('view-body').innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;padding:40px;color:var(--text-muted);"><div class="spin" style="margin-bottom:16px;"></div><div>جاري تحميل التفاصيل...</div></div>';
        try {
            const { data } = await axios.get(`/callcenter/orders/global-search/${id}`); const o = data.order;
            document.getElementById('view-num').textContent = o.order_number;
            document.getElementById('modal-pdf-btn').href = '/callcenter/orders/' + o.id + '/pdf';
            const itemsTotal = o.items.reduce((sum, item) => sum + parseFloat(item.total), 0);

            let html = `<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px; margin-bottom: 20px;">`;
            html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                بيانات العميل والتوصيل
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">العميل</span>
                    <span style="font-weight:600;">${o.client?.name ?? '—'} <span style="color:var(--text-muted); font-size:12px;">(${o.client?.code ?? ''})</span></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الهاتف</span>
                    <span style="font-weight:600; direction:ltr;">${o.client?.phone ?? '—'}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">العنوان</span>
                    <span style="font-weight:600; text-align:left;">${o.client_address ?? '—'}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted); font-size:13px;">المندوب</span>
                    <span style="font-weight:600; color:var(--yellow);">${o.delivery?.name ?? '—'}</span>
                </div>
            </div>
        </div>`;
            html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    الملخص المالي
                </div>
                <div>${statusBadge(o.status)}</div>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">رسوم التوصيل</span>
                    <span style="font-weight:600;">${parseFloat(o.delivery_fee).toFixed(2)} ج</span>
                </div>
                ${parseFloat(o.discount) > 0 ? `<div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted); font-size:13px;">الخصم</span>
                    <span style="font-weight:600; color:var(--red);">${parseFloat(o.discount).toFixed(2)} ${o.discount_type === 'percent' ? '%' : 'ج'}</span>
                </div>` : ''}
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                    <span style="font-size:14px; font-weight:700;">الإجمالي النهائي</span>
                    <strong style="color:var(--yellow); font-size:18px;">${parseFloat(o.total).toFixed(2)} ج</strong>
                </div>
            </div>
        </div></div>`;

            if (o.send_to_phone) {
                let clientName = o.send_to_name || '—';
                if (o.recipient_client && o.recipient_client.code) {
                    clientName += ` (${o.recipient_client.code})`;
                }
                let phones = o.send_to_phone;
                if (o.send_to_phone2) {
                    phones += ` / ${o.send_to_phone2}`;
                }

                html += `<div style="background:rgba(255,255,255,0.02); border:1px dashed var(--yellow); border-radius:10px; padding:16px; margin-bottom:16px;">
                <div style="font-size:14px; font-weight:700; color:var(--yellow); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                    إرسال إلى عميل آخر
                </div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="padding-bottom:8px; border-bottom:1px dashed rgba(255,255,255,0.1);">
                        <span style="color:var(--text-muted); font-size:13px;">الاسم</span>
                        <span style="font-weight:600;">${clientName}</span>
                    </div>
                    <div style="padding-bottom:8px; border-bottom:1px dashed rgba(255,255,255,0.1);">
                        <span style="color:var(--text-muted); font-size:13px;">الهاتف</span>
                        <span style="font-weight:600; direction:ltr;">${phones}</span>
                    </div>
                    <div>
                        <span style="color:var(--text-muted); font-size:13px;">العنوان</span>
                        <span style="font-weight:600; text-align:left;">${o.send_to_address || '—'}</span>
                    </div>
                </div>
            </div>`;
            }

            if (o.notes) {
                html += `<div style="display:flex; align-items:flex-start; gap:12px; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:20px;">
                <div style="color:var(--text-muted); margin-top:2px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <div>
                    <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:4px;">ملاحظات الطلب</div>
                    <div style="font-size:14px; line-height:1.5;">${o.notes}</div>
                </div>
            </div>`;
            }

            html += `<div style="background:var(--bg); border-radius:12px; border:1px solid var(--border); overflow:hidden; margin-bottom:20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="padding:12px 16px; background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                الأصناف (${o.items.length})
            </div>
            <div class="table-wrap" style="margin:0; border:none; border-radius:0;">
                <table style="margin:0; width:100%; border-collapse:collapse;">
                    <thead style="background:transparent;">
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="padding:10px 16px; text-align:right;">الصنف</th>
                            <th style="padding:10px 16px; text-align:right;">المتجر</th>
                            <th style="padding:10px 16px; text-align:center;">الكمية</th>
                            <th style="padding:10px 16px; text-align:center;">السعر</th>
                            <th style="padding:10px 16px; text-align:left;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${o.items.map(i => `<tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:12px 16px; font-weight:600;">${i.item_name}</td>
                            <td style="padding:12px 16px; color:var(--text-muted); font-size:13px;">${i.shop}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                <span style="background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:12px; font-size:12px; border:1px solid var(--border);">${i.quantity}</span>
                            </td>
                            <td style="padding:12px 16px; text-align:center;">${parseFloat(i.unit_price).toFixed(2)} ج</td>
                            <td style="padding:12px 16px; text-align:left; font-weight:700; color:var(--yellow);">${parseFloat(i.total).toFixed(2)} ج</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;


            if (o.logs && o.logs.length) {
                html += `<div style="background:var(--bg); border-radius:12px; padding:16px; border:1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        حالة الطلب
                    </div>
                    <div style="display:flex; flex-direction:column; gap:16px; position:relative;">
                        <div style="position:absolute; right:15px; top:10px; bottom:10px; width:2px; background:var(--border); z-index:1;"></div>
                        ${o.logs.map((l, index) => `<div style="display:flex; align-items:flex-start; gap:16px; position:relative; z-index:2;">
                            <div style="width:32px; height:32px; border-radius:50%; background:${index === 0 ? 'var(--yellow)' : 'var(--bg)'}; border:2px solid ${index === 0 ? 'var(--yellow)' : 'var(--border)'}; display:flex; align-items:center; justify-content:center; color:${index === 0 ? '#000' : 'var(--text-muted)'}; flex-shrink:0; margin-top:2px;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${index === 0 ? 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' : 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'}"></path></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px; font-weight:700; color:${index === 0 ? 'var(--text)' : 'var(--text-muted)'};">${l.action} <span style="font-weight:400; color:var(--text-muted); margin-right:4px;">— ${l.user}</span></div>
                                ${l.notes ? `<div style="font-size:12px; color:var(--text-muted); margin-top:5px; padding:6px 10px; line-height:1.5;">${l.notes}</div>` : ''}
                                <div style="font-size:12px; color:var(--text-muted); margin-top:4px; direction:ltr; text-align:right;">${formatDate(l.created_at)}</div>
                            </div>
                        </div>`).join('')}
                    </div>
                </div>`;
            }

            document.getElementById('view-body').innerHTML = html;
        } catch (e) {
            document.getElementById('view-body').innerHTML = `<div style="padding:40px; text-align:center;">
            <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:rgba(255,0,0, 0.1); color:var(--red); margin-bottom:16px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 style="margin-bottom:8px;">عذراً، حدث خطأ</h3>
            <p style="color:var(--text-muted); font-size:14px;">لم نتمكن من جلب بيانات الطلب. يرجى المحاولة مرة أخرى.</p>
        </div>`;
        }
    }

    if (document.getElementById('f-g-search').value.trim() !== '') {
        loadGlobalList();
    }
</script>