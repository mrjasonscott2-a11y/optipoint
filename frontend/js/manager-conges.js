/**
 * OptiPoint - Manager: Validation des congés équipe
 */

let allConges = [];

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(3)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-filter').addEventListener('click', loadConges);
    await loadConges();
});

async function loadConges() {
    const statut = document.getElementById('filter-statut').value;
    let query = '';
    if (statut) query += '&statut=' + statut;
    try {
        allConges = await apiCall('conges', { query });
        updateStats();
        renderList();
    } catch (err) { showToast('Erreur: ' + err.message, 'danger'); }
}

function updateStats() {
    setText('stat-total', allConges.length);
    setText('stat-attente', allConges.filter(c => c.statut === 'en_attente').length);
    setText('stat-approuves', allConges.filter(c => c.statut === 'approuve').length);
    setText('stat-refuses', allConges.filter(c => c.statut === 'refuse').length);
}

function renderList() {
    const container = document.getElementById('conges-list');
    if (allConges.length === 0) {
        container.innerHTML = `<div class="empty-state"><i class="fas fa-umbrella-beach"></i><h3>Aucun congé</h3></div>`;
        return;
    }
    const typeLabels = { conge_annuel: 'Congé annuel', conge_maladie: 'Maladie', teletravail: 'Télétravail', conge_exceptionnel: 'Exceptionnel' };
    const typeColors = { conge_annuel: 'primary', conge_maladie: 'danger', teletravail: 'purple', conge_exceptionnel: 'warning' };
    const statutColors = { en_attente: 'warning', approuve: 'success', refuse: 'danger' };

    container.innerHTML = allConges.map(c => `
        <div class="validation-card" style="flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:200px;">
                <div class="employee-avatar" style="min-width:44px;height:44px;font-size:0.8rem;">${getInitials(c.employe_nom)}</div>
                <div><strong>${escapeHtml(c.employe_nom)}</strong><div style="font-size:0.8rem;color:var(--text-muted);">${escapeHtml(c.matricule)}</div></div>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <span class="chip chip-${typeColors[c.type_demande] || 'primary'}">${typeLabels[c.type_demande] || c.type_demande}</span>
                    <span class="status-badge ${statutColors[c.statut] || ''}">${c.statut.replace('_', ' ')}</span>
                </div>
                <div style="font-size:0.85rem;color:var(--text-secondary);"><i class="fas fa-calendar"></i> ${formatDate(c.date_debut)} → ${formatDate(c.date_fin)}</div>
                ${c.motif ? `<div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;"><i class="fas fa-comment"></i> ${escapeHtml(c.motif)}</div>` : ''}
            </div>
            ${c.statut === 'en_attente' ? `
            <div class="actions">
                <button class="btn btn-sm btn-success" onclick="validerConge(${c.id},'approuve')"><i class="fas fa-check"></i> Approuver</button>
                <button class="btn btn-sm btn-danger" onclick="validerConge(${c.id},'refuse')"><i class="fas fa-times"></i> Refuser</button>
            </div>` : ''}
        </div>
    `).join('');
}

async function validerConge(id, statut) {
    let commentaire = '';
    if (statut === 'refuse') {
        commentaire = prompt('Raison du refus :');
        if (commentaire === null) return;
    }
    try {
        await apiCall('conges', { method: 'PUT', body: { id, statut, commentaire } });
        showToast(statut === 'approuve' ? 'Congé approuvé' : 'Congé refusé', statut === 'approuve' ? 'success' : 'warning');
        await loadConges();
    } catch (err) { showToast('Erreur: ' + err.message, 'danger'); }
}

window.validerConge = validerConge;
