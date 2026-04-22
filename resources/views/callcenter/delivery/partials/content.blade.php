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
                    <td>—</td>
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
