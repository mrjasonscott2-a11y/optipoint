/**
 * OptiPoint - Mon Planning (employé)
 */

let allPlannings = [];

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(4)) return;

    const user = getCurrentUser();
    if (user) {
        const avatar = document.getElementById('user-avatar');
        if (avatar) avatar.textContent = getInitials(user.nom);
        setText('user-name', user.nom);
        setText('user-role', user.service || 'Collaborateur');
    }

    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-filter').addEventListener('click', loadPlannings);

    // Période par défaut: 1 mois autour d'aujourd'hui
    const today = new Date();
    const debut = new Date(today); debut.setDate(today.getDate() - 7);
    const fin = new Date(today); fin.setDate(today.getDate() + 30);
    document.getElementById('filter-debut').value = debut.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = fin.toISOString().split('T')[0];

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
    const tbody = document.getElementById('planning-tbody');
    if (allPlannings.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-calendar-alt"></i><h3>Aucun planning</h3><p>Aucun planning sur cette période.</p></div></td></tr>`;
        return;
    }
    const typeLabels = { standard: 'Standard', teletravail: 'Télétravail', client: 'Client', exceptionnel: 'Exceptionnel' };
    tbody.innerHTML = allPlannings.map(p => `
        <tr>
            <td>${formatDate(p.date_debut)}</td>
            <td>${formatDate(p.date_fin)}</td>
            <td><span class="chip">${p.heures || '-'}h</span></td>
            <td>${escapeHtml(p.description || '-')}</td>
            <td><span class="chip">${typeLabels[p.type] || p.type || '-'}</span></td>
            <td><small style="color:var(--text-muted)">${escapeHtml(p.localisation || '-')}</small></td>
        </tr>
    `).join('');
}
