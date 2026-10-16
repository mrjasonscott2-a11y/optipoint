/**
 * OptiPoint - Gestion des équipes (Admin + Responsable)
 * CRUD complet : création, modification, suppression, affectation des membres
 */

let allEquipes = [];
let allManagers = [];
let allEmployes = [];
let currentEquipeId = null;

document.addEventListener('DOMContentLoaded', async function() {
    // Rôle requis selon la page (admin ou responsable)
    const isAdminPage = window.location.pathname.includes('admin');
    const requiredRole = isAdminPage ? 1 : 2;
    if (!requireAuth(requiredRole)) return;

    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-add-equipe').addEventListener('click', () => openEquipeModal());
    document.getElementById('form-equipe').addEventListener('submit', saveEquipe);
    document.getElementById('btn-add-membre').addEventListener('click', addMembre);
    setupGlobalModals();

    await Promise.all([loadEquipes(), loadManagers(), loadEmployes()]);
});

/* ============================================
   CHARGEMENT
   ============================================ */
async function loadEquipes() {
    try {
        allEquipes = await apiCall('equipes');
        let totalMembres = 0;
        allEquipes.forEach(eq => { totalMembres += (eq.membres || []).length; });
        setText('stat-equipes', allEquipes.length);
        setText('stat-membres', totalMembres);
        renderGrid();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

async function loadManagers() {
    try {
        // Managers (role 3) + Responsables (role 2) peuvent diriger une équipe
        const [managers, responsables] = await Promise.all([
            apiCall('managers'),
            apiCall('users', { query: '&role_id=2' })
        ]);
        allManagers = responsables.map(r => ({ ...r, fonction: 'Responsable' }))
            .concat(managers.map(m => ({ ...m, fonction: 'Manager' })));
        const select = document.getElementById('equipe-manager');
        select.innerHTML = '<option value="">— Aucun —</option>' +
            allManagers.map(m => `<option value="${m.id}">${escapeHtml(m.nom)} (${m.fonction})</option>`).join('');
    } catch (err) { console.error('Managers:', err); }
}

async function loadEmployes() {
    try {
        // Tous les utilisateurs non-admin visibles (employés + managers)
        const users = await apiCall('users');
        allEmployes = users;
    } catch (err) { console.error('Users:', err); }
}

/* ============================================
   AFFICHAGE
   ============================================ */
function renderGrid() {
    const grid = document.getElementById('equipes-grid');
    if (!allEquipes.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">
            <i class="fas fa-sitemap"></i>
            <h3>Aucune équipe</h3>
            <p>Créez votre première équipe avec le bouton « Nouvelle équipe ».</p>
        </div>`;
        return;
    }

    grid.innerHTML = allEquipes.map(eq => {
        const manager = allManagers.find(m => m.id == eq.manager_id);
        const membres = eq.membres || [];
        return `<div class="team-card">
            <div class="team-card-header">
                <div class="team-icon"><i class="fas fa-users-cog"></i></div>
                <div style="flex:1;min-width:0;">
                    <h3>${escapeHtml(eq.nom)}</h3>
                    <span class="dept-badge">${escapeHtml(eq.departement || 'Non défini')}</span>
                </div>
            </div>
            ${eq.description ? `<p style="font-size:0.85rem;color:var(--text-secondary);margin:8px 0;">${escapeHtml(eq.description)}</p>` : ''}
            <div style="margin:12px 0;font-size:0.85rem;">
                <div><i class="fas fa-user-tie" style="color:var(--primary);width:18px;"></i> Manager : <strong>${manager ? escapeHtml(manager.nom) : 'Non affecté'}</strong></div>
                <div style="margin-top:4px;"><i class="fas fa-users" style="color:var(--success);width:18px;"></i> ${membres.length} membre(s)</div>
            </div>
            <div style="display:flex;margin-bottom:12px;">
                ${membres.slice(0, 5).map(m => `<div class="employee-avatar" style="width:32px;height:32px;font-size:0.65rem;margin-right:-4px;border:2px solid var(--bg-card);" title="${escapeHtml(m.nom)}">${getInitials(m.nom)}</div>`).join('')}
                ${membres.length > 5 ? `<div class="employee-avatar" style="width:32px;height:32px;font-size:0.65rem;margin-right:-4px;border:2px solid var(--bg-card);background:var(--text-muted);">+${membres.length - 5}</div>` : ''}
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-sm btn-primary" style="flex:1;" onclick="openMembresModal(${eq.id})">
                    <i class="fas fa-user-plus"></i> Membres
                </button>
                <button class="btn btn-sm btn-outline" onclick="openEquipeModal(${eq.id})" data-tooltip="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteEquipe(${eq.id})" data-tooltip="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

/* ============================================
   CRUD ÉQUIPE
   ============================================ */
function openEquipeModal(equipeId = null) {
    const eq = equipeId ? allEquipes.find(e => e.id === equipeId) : null;
    document.getElementById('equipe-modal-title').textContent = eq ? 'Modifier l\'équipe' : 'Nouvelle équipe';
    document.getElementById('equipe-id').value = eq ? eq.id : '';
    document.getElementById('equipe-nom').value = eq ? eq.nom : '';
    document.getElementById('equipe-description').value = eq ? (eq.description || '') : '';
    document.getElementById('equipe-departement').value = eq ? (eq.departement || '') : '';
    document.getElementById('equipe-manager').value = eq && eq.manager_id ? eq.manager_id : '';
    openModal('equipe-modal');
}

async function saveEquipe(e) {
    e.preventDefault();
    const id = document.getElementById('equipe-id').value;
    const data = {
        nom: document.getElementById('equipe-nom').value.trim(),
        description: document.getElementById('equipe-description').value.trim(),
        departement: document.getElementById('equipe-departement').value.trim(),
        manager_id: document.getElementById('equipe-manager').value || null
    };
    if (!data.nom) { showToast('Le nom est requis', 'warning'); return; }

    try {
        if (id) {
            data.id = parseInt(id);
            await apiCall('equipes', { method: 'PUT', body: data });
            showToast('Équipe modifiée', 'success');
        } else {
            await apiCall('equipes', { method: 'POST', body: data });
            showToast('Équipe créée', 'success');
        }
        closeModal('equipe-modal');
        await loadEquipes();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

async function deleteEquipe(id) {
    const eq = allEquipes.find(e => e.id === id);
    if (!confirm(`Supprimer l'équipe « ${eq ? eq.nom : ''} » ?\nLes affectations des membres seront également supprimées.`)) return;
    try {
        await apiCall('equipes', { method: 'DELETE', body: { id } });
        showToast('Équipe supprimée', 'success');
        await loadEquipes();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

/* ============================================
   GESTION DES MEMBRES
   ============================================ */
function openMembresModal(equipeId) {
    currentEquipeId = equipeId;
    const eq = allEquipes.find(e => e.id === equipeId);
    if (!eq) return;
    document.getElementById('membres-modal-title').textContent = 'Membres — ' + eq.nom;
    renderMembresList(eq);
    renderMembreSelect(eq);
    openModal('membres-modal');
}

function renderMembresList(eq) {
    const membres = eq.membres || [];
    const container = document.getElementById('membres-list');
    if (!membres.length) {
        container.innerHTML = `<div class="empty-state" style="padding:20px;"><i class="fas fa-users"></i><h3>Aucun membre</h3><p>Ajoutez des employés ci-dessus.</p></div>`;
        return;
    }
    container.innerHTML = membres.map(m => `
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
            <div class="employee-avatar" style="width:36px;height:36px;font-size:0.7rem;">${getInitials(m.nom)}</div>
            <div style="flex:1;min-width:0;">
                <strong>${escapeHtml(m.nom)}</strong>
                <div style="font-size:0.8rem;color:var(--text-muted);">${escapeHtml(m.matricule)} · ${escapeHtml(m.service || '')}</div>
            </div>
            ${m.role_equipe === 'lead' ? '<span class="chip chip-primary">Lead</span>' : ''}
            <button class="btn btn-sm btn-danger" onclick="removeMembre(${m.id})" data-tooltip="Retirer">
                <i class="fas fa-user-minus"></i>
            </button>
        </div>
    `).join('');
}

function renderMembreSelect(eq) {
    const membresIds = (eq.membres || []).map(m => m.id);
    // Tous les utilisateurs non-admin disponibles (excluant le manager de l'équipe)
    const disponibles = allEmployes.filter(u => u.role_id !== 1 && !membresIds.includes(u.id) && u.id != eq.manager_id);
    const select = document.getElementById('membre-select');
    select.innerHTML = disponibles.length
        ? disponibles.map(u => `<option value="${u.id}">${escapeHtml(u.nom)} — ${getRoleLabel(u.role_id)} (${escapeHtml(u.service || '')})</option>`).join('')
        : '<option value="">Aucun employé disponible</option>';
}

async function addMembre() {
    const utilisateurId = parseInt(document.getElementById('membre-select').value);
    if (!utilisateurId) { showToast('Sélectionnez un employé', 'warning'); return; }
    try {
        await apiCall('equipe_membres', { method: 'POST', body: { equipe_id: currentEquipeId, utilisateur_id: utilisateurId } });
        showToast('Membre ajouté', 'success');
        await loadEquipes();
        const eq = allEquipes.find(e => e.id === currentEquipeId);
        renderMembresList(eq);
        renderMembreSelect(eq);
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

async function removeMembre(utilisateurId) {
    if (!confirm('Retirer ce membre de l\'équipe ?')) return;
    try {
        await apiCall('equipe_membres', { method: 'DELETE', body: { equipe_id: currentEquipeId, utilisateur_id: utilisateurId } });
        showToast('Membre retiré', 'success');
        await loadEquipes();
        const eq = allEquipes.find(e => e.id === currentEquipeId);
        renderMembresList(eq);
        renderMembreSelect(eq);
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

window.openEquipeModal = openEquipeModal;
window.deleteEquipe = deleteEquipe;
window.openMembresModal = openMembresModal;
window.removeMembre = removeMembre;
