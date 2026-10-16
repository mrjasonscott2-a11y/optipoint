/**
 * OptiPoint - Manager: Mon équipe (lecture seule)
 */

let allEmployes = [];
let currentPage = 1;
const perPage = 10;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(3)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('search-input').addEventListener('input', () => { currentPage = 1; renderTable(); });
    document.getElementById('filter-service').addEventListener('change', () => { currentPage = 1; renderTable(); });
    setupGlobalModals();
    await loadEmployes();
});

async function loadEmployes() {
    try {
        allEmployes = await apiCall('users');
        setText('stat-total', allEmployes.length);
        setText('stat-actifs', allEmployes.filter(e => e.statut === 'actif').length);
        setText('stat-inactifs', allEmployes.filter(e => e.statut !== 'actif').length);
        renderTable();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

function getFiltered() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const service = document.getElementById('filter-service').value;
    return allEmployes.filter(e => {
        if (search && !e.nom.toLowerCase().includes(search) && !e.matricule.toLowerCase().includes(search)) return false;
        if (service && e.service !== service) return false;
        return true;
    });
}

function renderTable() {
    const filtered = getFiltered();
    const { items, totalPages } = paginate(filtered, currentPage, perPage);
    const tbody = document.getElementById('employes-tbody');
    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fas fa-users"></i><h3>Aucun collaborateur</h3></div></td></tr>`;
        document.getElementById('pagination-container').innerHTML = '';
        return;
    }
    tbody.innerHTML = items.map(e => `
        <tr style="cursor:pointer" onclick="viewEmploye(${e.id})">
            <td><div style="display:flex;align-items:center;gap:10px;"><div class="employee-avatar">${getInitials(e.nom)}</div><div><strong>${escapeHtml(e.nom)}</strong><br><small style="color:var(--text-muted)">${escapeHtml(e.matricule)}</small></div></div></td>
            <td><span class="dept-badge ${getDeptClass(e.service)}">${escapeHtml(e.service)}</span></td>
            <td>${escapeHtml(e.email || '-')}</td>
            <td><span class="status-badge ${e.statut}">${e.statut}</span></td>
            <td>${getRoleLabel(e.role_id)}</td>
        </tr>
    `).join('');
    renderPagination(document.getElementById('pagination-container'), { currentPage, totalPages, totalItems: filtered.length }, (p) => { currentPage = p; renderTable(); });
}

function viewEmploye(id) {
    const e = allEmployes.find(x => x.id === id);
    if (!e) return;
    document.getElementById('view-modal-title').textContent = e.nom;
    document.getElementById('view-content').innerHTML = `
        <div style="text-align:center;margin-bottom:20px;">
            <div class="employee-avatar" style="width:80px;height:80px;font-size:2rem;margin:0 auto 12px;">${getInitials(e.nom)}</div>
            <h3>${escapeHtml(e.nom)}</h3>
            <span class="status-badge ${e.statut}">${e.statut}</span>
        </div>
        <div class="employee-details" style="grid-template-columns:1fr;gap:12px;">
            <div class="employee-detail"><span class="label">Matricule</span><span class="value">${escapeHtml(e.matricule)}</span></div>
            <div class="employee-detail"><span class="label">Email</span><span class="value">${escapeHtml(e.email || '-')}</span></div>
            <div class="employee-detail"><span class="label">Téléphone</span><span class="value">${escapeHtml(e.telephone || '-')}</span></div>
            <div class="employee-detail"><span class="label">Service</span><span class="value">${escapeHtml(e.service)}</span></div>
            <div class="employee-detail"><span class="label">Rôle</span><span class="value">${getRoleLabel(e.role_id)}</span></div>
            <div class="employee-detail"><span class="label">Horaire</span><span class="value">${escapeHtml(e.horaire || '-')}</span></div>
        </div>`;
    openModal('view-modal');
}

window.viewEmploye = viewEmploye;
