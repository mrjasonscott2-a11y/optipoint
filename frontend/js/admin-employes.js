/**
 * OptiPoint - Gestion des Employés (CRUD complet via API)
 */

let allEmployes = [];
let currentPageEmp = 1;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(1)) return;

    await Promise.all([loadEmployes(), loadManagerOptions()]);

    // Boutons
    document.getElementById('btn-add-employe').addEventListener('click', () => openEmployeModal());
    document.getElementById('btn-save-employe').addEventListener('click', saveEmploye);
    document.getElementById('btn-export').addEventListener('click', exportCSV);

    // Filtres
    document.getElementById('search-input').addEventListener('input', applyFilters);
    document.getElementById('filter-role').addEventListener('change', applyFilters);
    document.getElementById('filter-service').addEventListener('change', applyFilters);
    document.getElementById('filter-statut').addEventListener('change', applyFilters);

    // Recherche clear
    const clearBtn = document.querySelector('.search-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.getElementById('search-input').value = '';
            applyFilters();
        });
    }
});

/* === Chargement des managers/responsables pour le select de rattachement === */
async function loadManagerOptions() {
    try {
        // Managers (role 3) et Responsables (role 2) peuvent encadrer des collaborateurs
        const [managers, responsables] = await Promise.all([
            apiCall('managers'),
            apiCall('users', { query: '&role_id=2' })
        ]);
        const options = responsables.map(r => ({ id: r.id, nom: r.nom, role: 'Responsable' }))
            .concat(managers.map(m => ({ id: m.id, nom: m.nom, role: 'Manager' })));
        const select = document.getElementById('form-manager');
        if (select) {
            select.innerHTML = '<option value="">— Aucun —</option>' +
                options.map(o => `<option value="${o.id}">${escapeHtml(o.nom)} (${o.role})</option>`).join('');
        }
    } catch (err) {
        console.error('Chargement managers:', err);
    }
}

/* === CHARGEMENT === */
async function loadEmployes() {
    try {
        const data = await apiCall('users');
        allEmployes = data || [];
        updateStats();
        applyFilters();
    } catch (err) {
        showToast('Erreur chargement employés: ' + err.message, 'danger');
    }
}

function updateStats() {
    setText('stat-total', allEmployes.length);
    setText('stat-actifs', allEmployes.filter(e => e.statut === 'actif').length);
    setText('stat-inactifs', allEmployes.filter(e => e.statut === 'inactif').length);
    setText('stat-suspendus', allEmployes.filter(e => e.statut === 'suspendu').length);
}

/* === FILTRES === */
function applyFilters() {
    const search = document.getElementById('search-input').value.toLowerCase().trim();
    const role = document.getElementById('filter-role').value;
    const service = document.getElementById('filter-service').value;
    const statut = document.getElementById('filter-statut').value;

    let filtered = allEmployes.filter(e => {
        if (search && !(`${e.nom} ${e.matricule} ${e.email}`.toLowerCase().includes(search))) return false;
        if (role && e.role_id != role) return false;
        if (service && e.service !== service) return false;
        if (statut && e.statut !== statut) return false;
        return true;
    });

    renderTable(filtered);
}

/* === TABLEAU === */
function renderTable(data) {
    const tbody = document.getElementById('employes-tbody');
    const pageData = paginate(data, currentPageEmp, 10);

    if (pageData.items.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="8">
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>Aucun employé trouvé</h3>
                    <p>Modifiez vos critères de recherche ou ajoutez un nouvel employé.</p>
                </div>
            </td></tr>`;
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = pageData.items.map(e => `
        <tr>
            <td><strong>${escapeHtml(e.matricule)}</strong></td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="employee-avatar" style="width:32px;height:32px;font-size:0.8rem;">${getInitials(e.nom)}</div>
                    ${escapeHtml(e.nom)}
                </div>
            </td>
            <td>${escapeHtml(e.email || '-')}</td>
            <td><span class="dept-badge ${getDeptClass(e.service)}">${escapeHtml(e.service)}</span></td>
            <td>${getRoleLabel(e.role_id)}</td>
            <td><span class="horaire-badge"><i class="fas fa-clock"></i> ${escapeHtml(e.horaire || '-')}</span></td>
            <td><span class="status-badge ${e.statut}">${e.statut}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn action-btn-view" data-tooltip="Voir" onclick="viewEmploye(${e.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn action-btn-edit" data-tooltip="Modifier" onclick="editEmploye(${e.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn action-btn-delete" data-tooltip="Supprimer" onclick="deleteEmploye(${e.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination(document.getElementById('pagination'), pageData, (page) => {
        currentPageEmp = page;
        renderTable(data);
    });
}

/* === MODAL AJOUT/MODIFICATION === */
function openEmployeModal(employe = null) {
    const modal = document.getElementById('employe-modal');
    const title = document.getElementById('modal-title');
    const form = document.getElementById('employe-form');
    form.reset();
    document.getElementById('employe-id').value = '';

    if (employe) {
        title.textContent = 'Modifier ' + employe.nom;
        document.getElementById('employe-id').value = employe.id;
        document.getElementById('form-matricule').value = employe.matricule;
        document.getElementById('form-matricule').disabled = true;
        document.getElementById('form-nom').value = employe.nom;
        document.getElementById('form-email').value = employe.email;
        document.getElementById('form-telephone').value = employe.telephone || '';
        document.getElementById('form-service').value = employe.service;
        document.getElementById('form-role').value = employe.role_id;
        document.getElementById('form-horaire').value = employe.horaire || '08:00-17:00';
        document.getElementById('form-date-embauche').value = employe.date_embauche || '';
        document.getElementById('form-manager').value = employe.manager_id || '';
        document.getElementById('form-statut').value = employe.statut;
        document.getElementById('form-password').value = '';
        document.getElementById('form-password').required = false;
        document.getElementById('password-label').textContent = 'Nouveau mot de passe (laisser vide pour conserver)';
    } else {
        title.textContent = 'Nouvel employé';
        document.getElementById('form-matricule').disabled = false;
        document.getElementById('form-password').required = true;
        document.getElementById('password-label').textContent = 'Mot de passe *';
        document.getElementById('form-date-embauche').value = new Date().toISOString().split('T')[0];
    }

    modal.classList.add('show');
}

async function editEmploye(id) {
    const e = allEmployes.find(x => x.id === id);
    if (!e) return;
    openEmployeModal(e);
}

async function saveEmploye() {
    const id = document.getElementById('employe-id').value;
    const data = {
        matricule: document.getElementById('form-matricule').value.trim(),
        nom: document.getElementById('form-nom').value.trim(),
        email: document.getElementById('form-email').value.trim(),
        telephone: document.getElementById('form-telephone').value.trim(),
        service: document.getElementById('form-service').value,
        role_id: parseInt(document.getElementById('form-role').value),
        horaire: document.getElementById('form-horaire').value,
        date_embauche: document.getElementById('form-date-embauche').value,
        statut: document.getElementById('form-statut').value,
        manager_id: document.getElementById('form-manager').value || null
    };
    const password = document.getElementById('form-password').value;
    if (password) data.password = password;

    // Validation
    if (!data.matricule || !data.nom || !data.email || !data.service) {
        showToast('Veuillez remplir tous les champs obligatoires', 'warning');
        return;
    }

    try {
        if (id) {
            data.id = parseInt(id);
            await apiCall('users', { method: 'PUT', body: data });
            showToast('Employé modifié avec succès', 'success');
        } else {
            await apiCall('users', { method: 'POST', body: data });
            showToast('Employé créé avec succès', 'success');
        }
        closeModal('employe-modal');
        await loadEmployes();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

/* === VOIR DÉTAIL === */
async function viewEmploye(id) {
    const e = allEmployes.find(x => x.id === id);
    if (!e) return;

    const content = document.getElementById('view-content');
    content.innerHTML = `
        <div style="text-align:center;margin-bottom:20px;">
            <div class="employee-avatar" style="width:80px;height:80px;font-size:2rem;margin:0 auto 12px;">${getInitials(e.nom)}</div>
            <h3 style="margin-bottom:4px;">${escapeHtml(e.nom)}</h3>
            <span class="status-badge ${e.statut}">${e.statut}</span>
        </div>
        <div class="employee-details" style="grid-template-columns:1fr;gap:12px;">
            <div class="employee-detail"><span class="label">Matricule</span><span class="value">${escapeHtml(e.matricule)}</span></div>
            <div class="employee-detail"><span class="label">Email</span><span class="value">${escapeHtml(e.email || '-')}</span></div>
            <div class="employee-detail"><span class="label">Téléphone</span><span class="value">${escapeHtml(e.telephone || '-')}</span></div>
            <div class="employee-detail"><span class="label">Service</span><span class="value">${escapeHtml(e.service)}</span></div>
            <div class="employee-detail"><span class="label">Rôle</span><span class="value">${getRoleLabel(e.role_id)}</span></div>
            <div class="employee-detail"><span class="label">Horaire</span><span class="value">${escapeHtml(e.horaire || '-')}</span></div>
            <div class="employee-detail"><span class="label">Date d'embauche</span><span class="value">${formatDate(e.date_embauche)}</span></div>
        </div>`;

    openModal('view-modal');
}

/* === SUPPRESSION === */
async function deleteEmploye(id) {
    const e = allEmployes.find(x => x.id === id);
    if (!e) return;
    if (!confirm(`Êtes-vous sûr de vouloir supprimer ${e.nom} ?\nCette action est irréversible.`)) return;

    try {
        await apiCall('users', { method: 'DELETE', body: { id } });
        showToast('Employé supprimé avec succès', 'success');
        await loadEmployes();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

/* === EXPORT CSV === */
async function exportCSV() {
    try {
        showToast('Génération du CSV...', 'info');
        const data = await apiCall('export', { query: '&type=users' });
        if (data.csv) {
            const blob = new Blob(["\uFEFF" + data.csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename || 'utilisateurs.csv';
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

window.viewEmploye = viewEmploye;
window.editEmploye = editEmploye;
window.deleteEmploye = deleteEmploye;
