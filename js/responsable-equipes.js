/**
 * OptiPoint - Responsable: Visualisation des équipes
 */

let allEquipes = [];

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(2)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);
    setupGlobalModals();
    await loadEquipes();
});

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

function renderGrid() {
    const container = document.getElementById('equipes-grid');
    if (allEquipes.length === 0) {
        container.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><i class="fas fa-sitemap"></i><h3>Aucune équipe</h3></div>`;
        return;
    }
    container.innerHTML = allEquipes.map(eq => `
        <div class="team-card">
            <div class="team-card-header">
                <div class="team-icon"><i class="fas fa-users-cog"></i></div>
                <div>
                    <h3>${escapeHtml(eq.nom)}</h3>
                    <span class="dept-badge">${escapeHtml(eq.departement || 'Non défini')}</span>
                </div>
            </div>
            ${eq.description ? `<p style="font-size:0.85rem;color:var(--text-secondary);margin:8px 0;">${escapeHtml(eq.description)}</p>` : ''}
            <div style="margin-top:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:0.8rem;color:var(--text-muted);">Membres (${(eq.membres || []).length})</span>
                </div>
                <div style="display:flex;gap:-4px;margin-bottom:12px;">
                    ${(eq.membres || []).slice(0, 5).map(m => `<div class="employee-avatar" style="width:32px;height:32px;font-size:0.65rem;margin-right:-4px;border:2px solid var(--bg-card);" title="${escapeHtml(m.nom)}">${getInitials(m.nom)}</div>`).join('')}
                    ${(eq.membres || []).length > 5 ? `<div class="employee-avatar" style="width:32px;height:32px;font-size:0.65rem;margin-right:-4px;border:2px solid var(--bg-card);background:var(--text-muted);">+${(eq.membres || []).length - 5}</div>` : ''}
                </div>
                <button class="btn btn-sm btn-outline" style="width:100%;" onclick="viewEquipe(${eq.id})">
                    <i class="fas fa-eye"></i> Voir détails
                </button>
            </div>
        </div>
    `).join('');
}

function viewEquipe(id) {
    const eq = allEquipes.find(e => e.id === id);
    if (!eq) return;
    document.getElementById('detail-modal-title').textContent = eq.nom;

    const membres = eq.membres || [];
    document.getElementById('detail-content').innerHTML = `
        ${eq.description ? `<p style="margin-bottom:16px;color:var(--text-secondary);">${escapeHtml(eq.description)}</p>` : ''}
        <div style="margin-bottom:12px;"><strong>Département:</strong> <span class="dept-badge">${escapeHtml(eq.departement || '-')}</span></div>
        <h4 style="margin:16px 0 8px;">Membres (${membres.length})</h4>
        ${membres.length === 0 ? '<p style="color:var(--text-muted);">Aucun membre.</p>' :
        membres.map(m => `
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
                <div class="employee-avatar" style="width:36px;height:36px;font-size:0.7rem;">${getInitials(m.nom)}</div>
                <div style="flex:1;">
                    <strong>${escapeHtml(m.nom)}</strong>
                    <div style="font-size:0.8rem;color:var(--text-muted);">${escapeHtml(m.matricule)} · ${escapeHtml(m.service || '')}</div>
                </div>
                ${m.role_equipe ? `<span class="chip">${escapeHtml(m.role_equipe)}</span>` : ''}
            </div>
        `).join('')}`;
    openModal('detail-modal');
}

window.viewEquipe = viewEquipe;
