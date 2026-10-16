/**
 * OptiPoint - Gestion des Pointages (admin)
 */

let allPointages = [];
let currentPagePt = 1;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(1)) return;

    // Initialiser les dates par défaut (mois en cours)
    const today = new Date();
    const first = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('filter-date-debut').value = first.toISOString().split('T')[0];
    document.getElementById('filter-date-fin').value = today.toISOString().split('T')[0];

    await loadPointages();

    document.getElementById('btn-filter').addEventListener('click', loadPointages);
    document.getElementById('btn-export').addEventListener('click', exportCSV);
    document.getElementById('search-input').addEventListener('input', applyFilters);
});

async function loadPointages() {
    const debut = document.getElementById('filter-date-debut').value;
    const fin = document.getElementById('filter-date-fin').value;
    const type = document.getElementById('filter-type').value;
    const mode = document.getElementById('filter-mode').value;

    let query = '';
    if (debut) query += '&date_debut=' + debut;
    if (fin) query += '&date_fin=' + fin;
    if (type) query += '&type=' + type;
    if (mode) query += '&mode=' + mode;

    try {
        const data = await apiCall('pointage', { query });
        allPointages = data || [];
        updateStats();
        applyFilters();
    } catch (err) {
        showToast('Erreur chargement pointages: ' + err.message, 'danger');
    }
}

function updateStats() {
    setText('stat-total', allPointages.length);
    setText('stat-arrivees', allPointages.filter(p => p.type === 'arrivee').length);
    setText('stat-departs', allPointages.filter(p => p.type === 'depart').length);
    setText('stat-retards', allPointages.filter(p => p.statut === 'retard').length);
}

function applyFilters() {
    const search = document.getElementById('search-input').value.toLowerCase().trim();
    const filtered = allPointages.filter(p =>
        !search || `${p.employe_nom} ${p.matricule} ${p.service}`.toLowerCase().includes(search)
    );
    renderTable(filtered);
}

function renderTable(data) {
    const tbody = document.getElementById('pointages-tbody');
    const pageData = paginate(data, currentPagePt, 15);

    if (pageData.items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Aucun pointage</h3>
                <p>Aucun pointage ne correspond à vos critères.</p>
            </div>
        </td></tr>`;
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    const modeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client' };
    const modeIcons = { bureau: 'building', teletravail: 'home', client: 'briefcase' };

    tbody.innerHTML = pageData.items.map(p => `
        <tr>
            <td>${formatDate(p.date_pointage)}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="employee-avatar" style="width:32px;height:32px;font-size:0.75rem">${getInitials(p.employe_nom)}</div>
                    <div>
                        <div style="font-weight:500">${escapeHtml(p.employe_nom)}</div>
                        <div class="text-muted" style="font-size:0.72rem">${escapeHtml(p.matricule)} · ${escapeHtml(p.service)}</div>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge ${p.type === 'arrivee' ? 'badge-success' : 'badge-danger'}">
                    <i class="fas fa-${p.type === 'arrivee' ? 'sign-in-alt' : 'sign-out-alt'}"></i>
                    ${p.type === 'arrivee' ? 'Arrivée' : 'Départ'}
                </span>
            </td>
            <td><strong>${formatTime(p.heure)}</strong></td>
            <td><span class="chip"><i class="fas fa-${modeIcons[p.mode_travail] || 'building'}"></i> ${modeLabels[p.mode_travail] || p.mode_travail}</span></td>
            <td class="text-muted" style="font-size:0.78rem">${escapeHtml(p.localisation || '-')}</td>
            <td><span class="status-badge ${p.statut}">${p.statut}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn action-btn-delete" data-tooltip="Supprimer" onclick="deletePointage(${p.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination(document.getElementById('pagination'), pageData, (page) => {
        currentPagePt = page;
        renderTable(data);
    });
}

async function deletePointage(id) {
    if (!confirm('Supprimer ce pointage ?')) return;
    try {
        await apiCall('pointage', { method: 'DELETE', body: { id } });
        showToast('Pointage supprimé', 'success');
        await loadPointages();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

async function exportCSV() {
    const debut = document.getElementById('filter-date-debut').value;
    const fin = document.getElementById('filter-date-fin').value;
    try {
        showToast('Génération du CSV...', 'info');
        const data = await apiCall('export', { query: '&type=pointages&debut=' + debut + '&date_fin=' + fin });
        if (data.csv) {
            const blob = new Blob(["\uFEFF" + data.csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename || 'pointages.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Export terminé', 'success');
        }
    } catch (err) {
        showToast('Erreur export: ' + err.message, 'danger');
    }
}

window.deletePointage = deletePointage;
