/**
 * OptiPoint - Dashboard Manager
 */

let presenceChart, modesChart;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(3)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);

    await Promise.all([loadStats(), loadPendingConges(), loadRecentPointages()]);
});

async function loadStats() {
    try {
        const data = await apiCall('stats');
        setText('stat-equipe', data.total_collaborateurs || 0);
        setText('stat-presents', data.presents_aujourdhui || 0);
        setText('stat-retards', data.retards_aujourdhui || 0);
        setText('stat-conges', data.conges_en_attente || 0);

        if (data.last_7_days) renderPresenceChart(data.last_7_days);
        if (data.teletravail_aujourdhui !== undefined) {
            renderModesChart(data.presents_aujourdhui || 0, data.teletravail_aujourdhui || 0);
        }
    } catch (err) {
        console.error('Erreur stats:', err);
    }
}

function renderPresenceChart(data) {
    const ctx = document.getElementById('chart-presence');
    if (!ctx) return;
    presenceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => formatDate(d.jour)),
            datasets: [{ label: 'Présents', data: data.map(d => d.presents), borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#22c55e' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
}

function renderModesChart(presents, teletravail) {
    const ctx = document.getElementById('chart-modes');
    if (!ctx) return;
    const bureau = Math.max(0, presents - teletravail);
    modesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Bureau', 'Télétravail'],
            datasets: [{ data: [bureau, teletravail], backgroundColor: ['#3b82f6', '#8b5cf6'], borderWidth: 2, borderColor: '#fff' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } } }
    });
}

async function loadPendingConges() {
    try {
        const conges = await apiCall('conges', { query: '&statut=en_attente' });
        const container = document.getElementById('pending-conges-list');
        if (!conges || conges.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-check-circle"></i><h3>Tout est à jour</h3></div>`;
            return;
        }
        const typeLabels = { conge_annuel: 'Congé annuel', conge_maladie: 'Maladie', teletravail: 'Télétravail', conge_exceptionnel: 'Exceptionnel' };
        container.innerHTML = conges.slice(0, 5).map(c => `
            <div class="validation-card">
                <div class="employee-avatar" style="min-width:40px;height:40px;font-size:0.75rem;">${getInitials(c.employe_nom)}</div>
                <div style="flex:1;min-width:0;">
                    <strong>${escapeHtml(c.employe_nom)}</strong>
                    <div style="font-size:0.8rem;color:var(--text-secondary);">${typeLabels[c.type_demande] || c.type_demande} · ${formatDate(c.date_debut)} → ${formatDate(c.date_fin)}</div>
                </div>
                <div class="actions">
                    <button class="btn btn-sm btn-success" onclick="validerConge(${c.id},'approuve')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="validerConge(${c.id},'refuse')"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `).join('');
    } catch (err) { console.error('Erreur conges:', err); }
}

async function loadRecentPointages() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const data = await apiCall('pointage', { query: '&date=' + today });
        const pointages = Array.isArray(data) ? data : (data.pointages || []);
        const tbody = document.getElementById('recent-pointages-tbody');
        if (pointages.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">Aucun pointage aujourd'hui</td></tr>`;
            return;
        }
        tbody.innerHTML = pointages.slice(0, 8).map(p => `
            <tr>
                <td>${escapeHtml(p.employe_nom || '-')}</td>
                <td><span class="chip ${p.type === 'arrivee' ? 'chip-success' : 'chip-danger'}">${p.type === 'arrivee' ? 'Arrivée' : 'Départ'}</span></td>
                <td>${formatTime(p.heure)}</td>
                <td><span class="status-badge ${p.statut === 'normal' ? 'actif' : 'inactif'}">${p.statut === 'normal' ? 'Normal' : 'Retard'}</span></td>
            </tr>
        `).join('');
    } catch (err) { console.error('Erreur pointages:', err); }
}

async function validerConge(id, statut) {
    const commentaire = statut === 'approuve' ? '' : prompt('Raison du refus :');
    if (statut === 'refuse' && commentaire === null) return;
    try {
        await apiCall('conges', { method: 'PUT', body: { id, statut, commentaire: commentaire || '' } });
        showToast(statut === 'approuve' ? 'Congé approuvé' : 'Congé refusé', statut === 'approuve' ? 'success' : 'warning');
        await loadPendingConges();
    } catch (err) { showToast('Erreur: ' + err.message, 'danger'); }
}

window.validerConge = validerConge;
