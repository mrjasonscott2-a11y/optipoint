/**
 * OptiPoint - Gestion du Planning (admin)
 */

let allPlannings = [];
let allUsers = [];
let currentPagePl = 1;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(1)) return;

    // Initialiser les dates (semaine en cours)
    const today = new Date();
    const monday = new Date(today);
    monday.setDate(today.getDate() - today.getDay() + 1);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    document.getElementById('filter-debut').value = monday.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = sunday.toISOString().split('T')[0];

    await Promise.all([loadUsers(), loadPlannings()]);

    document.getElementById('btn-add').addEventListener('click', () => openModal());
    document.getElementById('btn-save').addEventListener('click', savePlanning);
    document.getElementById('btn-filter').addEventListener('click', loadPlannings);
});

async function loadUsers() {
    try {
        allUsers = await apiCall('users');
    } catch (err) {
        console.error('Erreur users:', err);
    }
}

async function loadPlannings() {
    const debut = document.getElementById('filter-debut').value;
    const fin = document.getElementById('filter-fin').value;
    let query = '';
    if (debut) query += '&date_debut=' + debut;
    if (fin) query += '&date_fin=' + fin;

    try {
        const data = await apiCall('planning', { query });
        allPlannings = data || [];
        renderTable();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

function renderTable() {
    const tbody = document.getElementById('plannings-tbody');
    const pageData = paginate(allPlannings, currentPagePl, 12);

    if (pageData.items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7">
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Aucun planning</h3>
                <p>Aucune affectation sur cette période.</p>
            </div>
        </td></tr>`;
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    const typeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client', mission: 'Mission', absence: 'Absence' };
    const typeColors = { bureau: 'badge-primary', teletravail: 'badge-purple', client: 'badge-info', mission: 'badge-warning', absence: 'badge-danger' };

    tbody.innerHTML = pageData.items.map(p => `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="employee-avatar" style="width:32px;height:32px;font-size:0.75rem">${getInitials(p.employe_nom)}</div>
                    ${escapeHtml(p.employe_nom)}
                </div>
            </td>
            <td><strong>${formatDate(p.date_debut)}</strong> → ${formatDate(p.date_fin)}</td>
            <td>${p.heure_debut ? formatTime(p.heure_debut) + ' - ' + formatTime(p.heure_fin) : 'Journée'}</td>
            <td>${escapeHtml(p.description || '-')}</td>
            <td class="text-muted">${escapeHtml(p.lieu || '-')}</td>
            <td><span class="badge ${typeColors[p.type_activite] || 'badge-secondary'}">${typeLabels[p.type_activite] || p.type_activite}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn action-btn-edit" data-tooltip="Modifier" onclick="editPlanning(${p.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn action-btn-delete" data-tooltip="Supprimer" onclick="deletePlanning(${p.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination(document.getElementById('pagination'), pageData, (page) => {
        currentPagePl = page;
        renderTable();
    });
}

function openModal(planning = null) {
    const form = document.getElementById('planning-form');
    form.reset();
    document.getElementById('form-id').value = '';

    // Remplir le select users
    const select = document.getElementById('form-utilisateur');
    select.innerHTML = '<option value="">Sélectionner...</option>' +
        allUsers.map(u => `<option value="${u.id}">${escapeHtml(u.nom)} (${escapeHtml(u.matricule)})</option>`).join('');

    if (planning) {
        document.getElementById('modal-title').textContent = 'Modifier affectation';
        document.getElementById('form-id').value = planning.id;
        document.getElementById('form-utilisateur').value = planning.utilisateur_id;
        document.getElementById('form-date-debut').value = planning.date_debut;
        document.getElementById('form-date-fin').value = planning.date_fin;
        document.getElementById('form-heure-debut').value = planning.heure_debut ? formatTime(planning.heure_debut) : '08:00';
        document.getElementById('form-heure-fin').value = planning.heure_fin ? formatTime(planning.heure_fin) : '17:00';
        document.getElementById('form-type').value = planning.type_activite || 'bureau';
        document.getElementById('form-lieu').value = planning.lieu || '';
        document.getElementById('form-description').value = planning.description || '';
    } else {
        document.getElementById('modal-title').textContent = 'Nouvelle affectation';
    }

    document.getElementById('planning-modal').classList.add('show');
}

function editPlanning(id) {
    const p = allPlannings.find(x => x.id === id);
    if (p) openModal(p);
}

async function savePlanning() {
    const id = document.getElementById('form-id').value;
    const data = {
        utilisateur_id: parseInt(document.getElementById('form-utilisateur').value),
        date_debut: document.getElementById('form-date-debut').value,
        date_fin: document.getElementById('form-date-fin').value,
        heure_debut: document.getElementById('form-heure-debut').value || null,
        heure_fin: document.getElementById('form-heure-fin').value || null,
        type_activite: document.getElementById('form-type').value,
        lieu: document.getElementById('form-lieu').value,
        description: document.getElementById('form-description').value
    };

    if (!data.utilisateur_id || !data.date_debut || !data.date_fin || !data.description) {
        showToast('Veuillez remplir les champs obligatoires', 'warning');
        return;
    }

    try {
        if (id) {
            data.id = parseInt(id);
            await apiCall('planning', { method: 'PUT', body: data });
            showToast('Planning modifié', 'success');
        } else {
            await apiCall('planning', { method: 'POST', body: data });
            showToast('Planning créé', 'success');
        }
        closeModal('planning-modal');
        await loadPlannings();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

async function deletePlanning(id) {
    if (!confirm('Supprimer cette affectation ?')) return;
    try {
        await apiCall('planning', { method: 'DELETE', body: { id } });
        showToast('Planning supprimé', 'success');
        await loadPlannings();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

window.editPlanning = editPlanning;
window.deletePlanning = deletePlanning;
