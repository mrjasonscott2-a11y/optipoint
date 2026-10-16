/**
 * OptiPoint - Manager: Planning équipe (lecture seule)
 */

let allPlannings = [];
let currentPage = 1;
const perPage = 15;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(3)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-filter').addEventListener('click', () => { currentPage = 1; loadPlannings(); });

    const today = new Date();
    const monday = new Date(today);
    monday.setDate(today.getDate() - ((today.getDay() + 6) % 7));
    const friday = new Date(monday);
    friday.setDate(monday.getDate() + 4);
    document.getElementById('filter-debut').value = monday.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = friday.toISOString().split('T')[0];

    await loadPlannings();
});

async function loadPlannings() {
    const debut = document.getElementById('filter-debut').value;
    const fin = document.getElementById('filter-fin').value;
    let query = '';
    if (debut) query += '&date_debut=' + debut;
    if (fin) query += '&date_fin=' + fin;
    try {
        const data = await apiCall('planning', { query });
        allPlannings = Array.isArray(data) ? data : (data.plannings || []);
        renderTable();
    } catch (err) { showToast('Erreur: ' + err.message, 'danger'); }
}

function renderTable() {
    const { items, totalPages } = paginate(allPlannings, currentPage, perPage);
    const tbody = document.getElementById('planning-tbody');
    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-calendar-alt"></i><h3>Aucun planning</h3></div></td></tr>`;
        document.getElementById('pagination-container').innerHTML = '';
        return;
    }
    const typeLabels = { standard: 'Standard', teletravail: 'Télétravail', client: 'Client', exceptionnel: 'Exceptionnel' };
    tbody.innerHTML = items.map(p => `
        <tr>
            <td><div style="display:flex;align-items:center;gap:8px;"><div class="employee-avatar" style="width:32px;height:32px;font-size:0.65rem;">${getInitials(p.employe_nom || '?')}</div><span>${escapeHtml(p.employe_nom || '-')}</span></div></td>
            <td>${formatDate(p.date_debut)}</td>
            <td>${formatDate(p.date_fin)}</td>
            <td><span class="chip">${p.heures || '-'}h</span></td>
            <td>${escapeHtml(p.description || '-')}</td>
            <td><span class="chip">${typeLabels[p.type] || p.type || '-'}</span></td>
        </tr>
    `).join('');
    renderPagination(document.getElementById('pagination-container'), { currentPage, totalPages, totalItems: allPlannings.length }, (p) => { currentPage = p; renderTable(); });
}
