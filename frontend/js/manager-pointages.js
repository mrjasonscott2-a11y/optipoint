/**
 * OptiPoint - Manager: Pointages équipe
 */

let allPointages = [];
let currentPage = 1;
const perPage = 15;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(3)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-filter').addEventListener('click', () => { currentPage = 1; loadPointages(); });
    document.getElementById('filter-date').value = new Date().toISOString().split('T')[0];
    await loadPointages();
});

async function loadPointages() {
    const date = document.getElementById('filter-date').value;
    const type = document.getElementById('filter-type').value;
    const mode = document.getElementById('filter-mode').value;
    let query = '';
    if (date) query += '&date=' + date;
    if (type) query += '&type=' + type;
    if (mode) query += '&mode_travail=' + mode;
    try {
        const data = await apiCall('pointage', { query });
        allPointages = Array.isArray(data) ? data : (data.pointages || []);
        updateStats();
        renderTable();
    } catch (err) { showToast('Erreur: ' + err.message, 'danger'); }
}

function updateStats() {
    setText('stat-normaux', allPointages.filter(p => p.statut === 'normal').length);
    setText('stat-retards', allPointages.filter(p => p.statut === 'retard').length);
    setText('stat-bureau', allPointages.filter(p => p.mode_travail === 'bureau').length);
    setText('stat-teletravail', allPointages.filter(p => p.mode_travail === 'teletravail').length);
}

function renderTable() {
    const { items, totalPages } = paginate(allPointages, currentPage, perPage);
    const tbody = document.getElementById('pointages-tbody');
    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-clipboard"></i><h3>Aucun pointage</h3></div></td></tr>`;
        document.getElementById('pagination-container').innerHTML = '';
        return;
    }
    const modeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client' };
    tbody.innerHTML = items.map(p => `
        <tr>
            <td>${escapeHtml(p.employe_nom || '-')}</td>
            <td><span class="chip ${p.type === 'arrivee' ? 'chip-success' : 'chip-danger'}">${p.type === 'arrivee' ? 'Arrivée' : 'Départ'}</span></td>
            <td>${formatDate(p.date_pointage)}</td>
            <td>${formatTime(p.heure)}</td>
            <td><span class="chip">${modeLabels[p.mode_travail] || p.mode_travail || '-'}</span></td>
            <td><span class="status-badge ${p.statut === 'normal' ? 'actif' : 'inactif'}">${p.statut === 'normal' ? 'Normal' : 'Retard'}</span></td>
            <td><small style="color:var(--text-muted)">${escapeHtml(p.localisation || '-')}</small></td>
        </tr>
    `).join('');
    renderPagination(document.getElementById('pagination-container'), { currentPage, totalPages, totalItems: allPointages.length }, (p) => { currentPage = p; renderTable(); });
}
