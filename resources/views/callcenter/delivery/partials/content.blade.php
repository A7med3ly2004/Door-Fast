{{-- Callcenter Delivery SPA partial --}}
<div class="section-header"><h2>🚴 إدارة المناديب</h2></div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>المندوب</th>
                    <th>الهاتف</th>
                    <th>حالة الوردية</th>
                    <th>الملغي</th>
                    <th>المحصّل</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="delivery-body">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Settlement Modal --}}
<div class="modal-overlay" id="modal-settle">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>💰 تسوية — <span id="settle-name"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-settle')">✕</button>
        </div>
        <div class="modal-body" id="settle-body"></div>
    </div>
</div>

<script>
(function () {
    // ─────────────────────────────────────────────────────────────
    // Render table rows
    // ─────────────────────────────────────────────────────────────
    function renderDeliveries(data) {
        var body = document.getElementById('delivery-body');
        if (!data || !data.length) {
            body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">لا يوجد مناديب نشطون</td></tr>';
            return;
        }

        body.innerHTML = data.map(d => {
            const isOn   = !!d.is_on_shift;
            const btnBg  = isOn ? 'rgba(34,197,94,.15)' : 'rgba(220,38,38,.12)';
            const btnClr = isOn ? 'var(--success)'       : 'var(--red)';
            const btnTxt = isOn ? '✓ في الوردية'         : '✗ خارج الوردية';

            const badge = d.role === 'reserve_delivery'
                ? '<span class="badge" style="background:var(--yellow);color:#000;font-size:10px;padding:2px 6px;border-radius:4px;vertical-align:middle;margin-right:4px;">احتياطي</span>'
                : '';

            return `
                <tr>
                    <td><strong>${escHtml(d.name)}</strong> ${badge}</td>
                    <td>${d.phone ?? '—'}</td>
                    <td>
                        <button
                            onclick="toggleShift(${d.id}, this)"
                            data-active="${isOn ? '1' : '0'}"
                            style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;background:${btnBg};color:${btnClr};">
                            ${btnTxt}
                        </button>
                    </td>
                    <td style="color:var(--red)">${d.cancelled_today ?? 0}</td>
                    <td><strong style="color:var(--yellow)">${parseFloat(d.revenue_today ?? 0).toFixed(2)} ج</strong></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="showSettlement(${d.id}, '${escAttr(d.name)}')">💰 تسوية</button>
                    </td>
                </tr>`;
        }).join('');
    }

    // ─────────────────────────────────────────────────────────────
    // Toggle shift — PATCH /callcenter/delivery/{id}/toggle
    // ─────────────────────────────────────────────────────────────
    window.toggleShift = async function(id, btn) {
        const wasActive = btn.dataset.active === '1';
        applyShiftBtn(btn, !wasActive);   // optimistic update
        btn.disabled = true;
        try {
            const { data } = await axios.patch(`/callcenter/delivery/${id}/toggle`);
            if (typeof showSuccess === 'function') showSuccess(data.message);
            await reloadDeliveries();     // refresh the whole table from server
        } catch(e) {
            applyShiftBtn(btn, wasActive); // revert
            const msg = e.response?.data?.message ?? 'حدث خطأ أثناء تغيير حالة الوردية';
            if (typeof showError === 'function') showError(msg);
        } finally {
            btn.disabled = false;
        }
    };

    function applyShiftBtn(btn, active) {
        btn.dataset.active = active ? '1' : '0';
        if (active) {
            btn.style.background = 'rgba(34,197,94,.15)';
            btn.style.color      = 'var(--success)';
            btn.textContent      = '✓ في الوردية';
        } else {
            btn.style.background = 'rgba(220,38,38,.12)';
            btn.style.color      = 'var(--red)';
            btn.textContent      = '✗ خارج الوردية';
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Reload — uses /delivery/all to show ALL delivery users
    // (not just those currently on shift)
    // ─────────────────────────────────────────────────────────────
    window.reloadDeliveries = async function() {
        const overlay = document.getElementById('tbl-loading');
        if (overlay) overlay.classList.add('show');
        try {
            const res = await axios.get('{{ route("callcenter.delivery.all") }}');
            renderDeliveries(res.data);
        } catch(e) {
            console.warn('reloadDeliveries failed', e);
        } finally {
            if (overlay) overlay.classList.remove('show');
        }
    };

    // ─────────────────────────────────────────────────────────────
    // Settlement
    // ─────────────────────────────────────────────────────────────
    window.showSettlement = async function(id, name) {
        document.getElementById('settle-name').textContent = name;
        openModal('modal-settle');
        document.getElementById('settle-body').innerHTML =
            '<div style="text-align:center;padding:40px"><div class="spin"></div></div>';
        try {
            const { data } = await axios.get(`/callcenter/delivery/${id}/settlement`);
            const { orders, summary } = data;
            document.getElementById('settle-body').innerHTML = `
                <div class="kpi-grid" style="margin-bottom:16px">
                    <div class="kpi-card green">
                        <div class="kpi-label">طلبات مُوصَّلة</div>
                        <div class="kpi-value">${summary.count}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">إجمالي القيم</div>
                        <div class="kpi-value">${parseFloat(summary.total_values).toFixed(2)}</div>
                        <div class="kpi-sub">جنيه</div>
                    </div>
                    <div class="kpi-card blue">
                        <div class="kpi-label">إجمالي رسوم التوصيل</div>
                        <div class="kpi-value">${parseFloat(summary.total_fees).toFixed(2)}</div>
                        <div class="kpi-sub">جنيه</div>
                    </div>
                </div>
                <div class="kpi-grid" style="margin-bottom:16px">
                    <div class="kpi-card" style="background:var(--card-bg);border:1px dashed var(--yellow)">
                        <div class="kpi-label" style="color:var(--yellow)">إجمالي العهدة (مطلوب تسويته)</div>
                        <div class="kpi-value">${parseFloat(summary.unsettled_value).toFixed(2)}</div>
                        <div class="kpi-sub">جنيه</div>
                    </div>
                    <div class="kpi-card" style="background:var(--card-bg);border:1px dashed var(--yellow)">
                        <div class="kpi-label" style="color:var(--yellow)">رسوم التوصيل (مطلوب تسويتها)</div>
                        <div class="kpi-value">${parseFloat(summary.unsettled_fees).toFixed(2)}</div>
                        <div class="kpi-sub">جنيه</div>
                    </div>
                </div>
                <div style="text-align:center;padding-bottom:20px;">
                    <button class="btn btn-primary"
                        onclick="doSettlement(${id})"
                        ${(summary.unsettled_value <= 0 && summary.unsettled_fees <= 0) ? 'disabled' : ''}>
                        ✅ تم التسوية (تصفير العهدة للمندوب)
                    </button>
                </div>
                ${orders.length
                    ? `<div class="table-wrap"><table>
                            <thead><tr><th>رقم الطلب</th><th>العميل</th><th>إجمالي الطلب</th><th>رسوم التوصيل</th><th>وقت التوصيل</th></tr></thead>
                            <tbody>${orders.map(o => `
                                <tr>
                                    <td><strong style="color:var(--yellow)">${o.order_number}</strong></td>
                                    <td>${escHtml(o.client)}</td>
                                    <td>${parseFloat(o.total).toFixed(2)} ج</td>
                                    <td>${parseFloat(o.delivery_fee).toFixed(2)} ج</td>
                                    <td style="font-size:11px;color:var(--text-muted)">${o.delivered_at ? formatDate(o.delivered_at) : '—'}</td>
                                </tr>`).join('')}
                            </tbody>
                       </table></div>`
                    : '<div style="text-align:center;color:var(--text-muted);padding:24px">لا توجد طلبات موصّلة غير مسوّاة</div>'
                }`;
        } catch(e) {
            document.getElementById('settle-body').innerHTML =
                '<div style="color:var(--red);text-align:center;padding:20px">حدث خطأ أثناء تحميل بيانات التسوية</div>';
        }
    };

    window.doSettlement = async function(id) {
        if (!confirm('هل أنت متأكد من تصفير عهدة المندوب وبدء حساب جديد له؟')) return;
        try {
            const { data } = await axios.post(`/callcenter/delivery/${id}/settlement`);
            if (typeof showSuccess === 'function') showSuccess(data.message);
            closeModal('modal-settle');
            reloadDeliveries();
        } catch(e) {
            const msg = e.response?.data?.message ?? 'حدث خطأ أثناء التسوية';
            if (typeof showError === 'function') showError(msg);
        }
    };

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────
    function escHtml(str) {
        if (str == null) return '—';
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escAttr(str) {
        if (str == null) return '';
        return String(str).replace(/'/g, "\\'");
    }
    function formatDate(str) {
        if (!str) return '—';
        const d = new Date(str);
        return d.toLocaleDateString('ar-EG', {
            year:'numeric', month:'short', day:'numeric',
            hour:'2-digit', minute:'2-digit'
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────
    var initialData = @json($deliveries ?? []);
    if (initialData && initialData.length) {
        renderDeliveries(initialData);
    } else {
        reloadDeliveries();
    }

    // Poll every 30 s
    if (typeof addPolling === 'function') {
        addPolling(reloadDeliveries, 30000);
    } else {
        setInterval(reloadDeliveries, 30000);
    }

})();
</script>
