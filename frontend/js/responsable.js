/**
 * OptiPoint - Dashboard Responsable
 */

let presenceChart, servicesChart;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(2)) return;

    document.getElementById('logout-btn').addEventListener('click', logout);

    await Promise.all([
        loadStats(),
        loadActivities(),
        loadPendingConges()
    ]);
});

/* ============================================
   STATISTIQUES
   ============================================ */
async function loadStats() {
    try {
        const data = await apiCall('stats');
        setText('stat-total', data.total_collaborateurs || data.total_employes || 0);
        const presents = data.presents_aujourdhui || data.pointages_aujourdhui || 0;
        setText('stat-presents', data.taux_presence !== undefined ? presents + ' (' + data.taux_presence + '%)' : presents);
        setText('stat-retards', data.retards_aujourdhui || 0);
        setText('stat-conges', data.conges_en_attente || 0);

        if (data.last_7_days) renderPresenceChart(data.last_7_days);
        if (data.stats_service) renderServicesChart(data.stats_service);
    } catch (err) {
        console.error('Erreur stats:', err);
    }
}

function renderPresenceChart(data) {
    const ctx = document.getElementById('chart-presence');
    if (!ctx) return;
    const labels = data.map(d => formatDate(d.jour));
    const values = data.map(d => d.presents);
    presenceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Présents',
                data: values,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#22c55e'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: {}
            }
        }
    });
}

function renderServicesChart(data) {
    const ctx = document.getElementById('chart-services');
    if (!ctx) return;
    const labels = data.map(d => d.service);
    const values = data.map(d => d.effectif);
    const colors = ['#3b82f6', '#8b5cf6', '#f59e0b', '#22c55e', '#ef4444', '#06b6d4'];
    servicesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } }
            }
        }
    });
}

/* ============================================
   ACTIVITÉS RÉCENTES (LOGS)
   ============================================ */
async function loadActivities() {
    try {
        const logs = await apiCall('logs', { query: '&limit=10' });
        const container = document.getElementById('activities-timeline');
        if (!logs || logs.length === 0) {
            container.innerHTML = `<div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Aucune activité</h3>
            </div>`;
            return;
        }
        container.innerHTML = logs.slice(0, 8).map(log => {
            const date = new Date(log.created_at);
            return `<div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-title">${escapeHtml(log.action)}</div>
                    <div class="timeline-text">${escapeHtml(log.details || '')}</div>
                    <div class="timeline-time">${date.toLocaleDateString('fr-FR')} à ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
            </div>`;
        }).join('');
    } catch (err) {
        document.getElementById('activities-timeline').innerHTML = `
            <div class="empty-state"><i class="fas fa-lock"></i><h3>Accès réservé</h3><p>Journal visible par les administrateurs uniquement.</p></div>`;
    }
}

/* ============================================
   CONGÉS EN ATTENTE
   ============================================ */
async function loadPendingConges() {
    try {
        const conges = await apiCall('conges', { query: '&statut=en_attente' });
        const container = document.getElementById('pending-conges-list');
        if (!conges || conges.length === 0) {
            container.innerHTML = `<div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>Tout est à jour</h3>
                <p>Aucune demande de congé en attente.</p>
            </div>`;
            return;
        }
        const typeLabels = { conge_annuel: 'Congé annuel', conge_maladie: 'Maladie', teletravail: 'Télétravail', conge_exceptionnel: 'Exceptionnel' };
        container.innerHTML = conges.slice(0, 5).map(c => `
            <div class="validation-card">
                <div class="employee-avatar" style="min-width:40px;height:40px;font-size:0.75rem;">${getInitials(c.employe_nom)}</div>
                <div style="flex:1;min-width:0;">
                    <strong>${escapeHtml(c.employe_nom)}</strong>
                    <div style="font-size:0.8rem;color:var(--text-secondary);">${typeLabels[c.type_demande] || c.type_demande} · ${formatDate(c.date_debut)} → ${formatDate(c.date_fin)}</div>
                    ${c.motif ? `<div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">${escapeHtml(c.motif)}</div>` : ''}
                </div>
                <div class="actions">
                    <button class="btn btn-sm btn-success" onclick="validerConge(${c.id},'approuve')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="validerConge(${c.id},'refuse')"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `).join('');
    } catch (err) {
        console.error('Erreur conges:', err);
    }
}

async function validerConge(id, statut) {
    const commentaire = statut === 'approuve' ? '' : prompt('Raison du refus :');
    if (statut === 'refuse' && commentaire === null) return;
    try {
        await apiCall('conges', {
            method: 'PUT',
            body: { id, statut, commentaire: commentaire || '' }
        });
        showToast(statut === 'approuve' ? 'Congé approuvé' : 'Congé refusé', statut === 'approuve' ? 'success' : 'warning');
        await loadPendingConges();
        await loadStats();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

window.validerConge = validerConge;
