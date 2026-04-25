{{-- Callcenter Delivery SPA partial --}}
<div class="section-header"><h2>إدارة المناديب</h2></div>
<div class="card" style="padding:0;position:relative">
    <div class="loading-overlay" id="tbl-loading"><div class="spin"></div></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">المندوب</th>
                    <th style="text-align: center;">الهاتف</th>
                    <th style="text-align: center;">حالة الوردية</th>
                    <th style="text-align: center;">الرصيد الحالي</th>
                    <th style="text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody id="delivery-body">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── User Statement Modal ──────────────────────────────────────── --}}
<div class="modal-overlay" id="modal-user-statement">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3>كشف حساب — <span id="stmt-user-name"></span></h3>
            <button class="btn-close" onclick="closeModal('modal-user-statement')">✕</button>
        </div>
        <div class="modal-body" id="stmt-modal-body">
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>
                جاري التحميل...
            </div>
        </div>
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
                    <td style="text-align: center;"><strong>${escHtml(d.name)}</strong> ${badge}</td>
                    <td style="text-align: center;">${d.phone ?? '—'}</td>
                    <td style="text-align: center;">
                        <button
                            onclick="toggleShift(${d.id}, this)"
                            data-active="${isOn ? '1' : '0'}"
                            style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;border:none;cursor:pointer;font-family:'Cairo',sans-serif;font-size:12px;font-weight:700;transition:all .2s ease;background:${btnBg};color:${btnClr};">
                            ${btnTxt}
                        </button>
                    </td>
                    <td style="text-align: center;"><strong style="color:var(--yellow)">${parseFloat(d.current_balance ?? 0).toFixed(2)} ج</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-info" style="font-size: 12px; padding: 4px 10px;" onclick="event.stopPropagation();openUserStatement(${d.id}, '${escAttr(d.name)}')" title="كشف حساب">كشف حساب</button>
                    </td>
                </tr>`;
        }).join('');
    }

    // ─────────────────────────────────────────────────────────────
    // User statement modal
    // ─────────────────────────────────────────────────────────────
    window.openUserStatement = async function (userId, name) {
        document.getElementById('stmt-user-name').textContent = name;
        document.getElementById('stmt-modal-body').innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <div class="spin" style="width:30px;height:30px;border-width:3px;margin:0 auto 12px;"></div>
                جاري التحميل...
            </div>`;
        openModal('modal-user-statement');

        try {
            const res = await axios.get(`/callcenter/delivery/${userId}/statement`);
            renderStatement(res.data);
        } catch (e) {
            document.getElementById('stmt-modal-body').innerHTML =
                '<div style="color:var(--red);text-align:center;padding:30px;">حدث خطأ أثناء تحميل كشف الحساب</div>';
            console.error('Statement fetch error', e);
        }
    };

    function renderStatement(data) {
        const s = data.summary;
        const txs = data.transactions;

        let transactionsHtml = '';
        if (txs.length === 0) {
            transactionsHtml = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">لا توجد عمليات</td></tr>`;
        } else {
            transactionsHtml = txs.map(tx => `
                <tr>
                    <td style="color:var(--text-muted);font-size:12px; text-align:center;">${tx.id}</td>
                    <td style="text-align:center;">${formatDate(tx.transaction_date)}</td>
                    <td style="font-size:12px; text-align:right;">${escHtml(tx.description)}</td>
                    <td style="color:var(--success);font-weight:700; text-align:center;">${tx.debit || '—'}</td>
                    <td style="color:var(--red);font-weight:700; text-align:center;">${tx.credit || '—'}</td>
                    <td style="font-weight:700;color:var(--yellow); text-align:center;">${tx.balance_after}</td>
                </tr>
            `).join('');
        }

        document.getElementById('stmt-modal-body').innerHTML = `
            <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
                <div class="kpi-card green">
                    <div class="kpi-label">إجمالي المدين</div>
                    <div class="kpi-value" style="font-size:20px;color:var(--success);">${s.total_debit}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">إجمالي الدائن</div>
                    <div class="kpi-value" style="font-size:20px;color:var(--red);">${s.total_credit}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
                <div class="kpi-card yellow">
                    <div class="kpi-label">الرصيد الحالي</div>
                    <div class="kpi-value" style="font-size:20px;color:var(--yellow);">${s.current_balance}</div>
                    <div class="kpi-sub">ج.م</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">رقم العملية</th>
                            <th style="text-align:center;">التاريخ</th>
                            <th style="text-align:right;">التعريف / الملاحظة</th>
                            <th style="text-align:center;">مدين</th>
                            <th style="text-align:center;">دائن</th>
                            <th style="text-align:center;">الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>${transactionsHtml}</tbody>
                </table>
            </div>
        `;
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
