/**
 * OptiPoint - Dashboard Admin
 */

let presenceChart, servicesChart;

document.addEventListener('DOMContentLoaded', async function() {
    // Vérifier auth admin
    const user = requireAuth(1);
    if (!user) return;

    // Initialiser les graphiques (vides au départ)
    initCharts();

    // Charger les données en parallèle
    await Promise.all([
        loadStats(),
        loadActivities(),
        loadRecentEmployes(),
        loadPendingConges(),
        loadNotifications()
    ]);

    // Notifications dropdown
    setupNotifications();
});

/* === STATISTIQUES === */
async function loadStats() {
    try {
        const stats = await apiCall('stats');
        setText('stat-total-employes', stats.total_employes || 0);
        setText('stat-pointages-jour', stats.pointages_aujourdhui || 0);
        setText('stat-retards-jour', stats.retards_aujourdhui || 0);
        setText('stat-absents-jour', stats.absents_aujourdhui || 0);

        window._statsData = stats;
        renderChartsData();
    } catch (err) {
        console.error('Erreur stats:', err);
        showToast('Erreur chargement des statistiques', 'danger');
    }
}

/* === ACTIVITÉS RÉCENTES === */
async function loadActivities() {
    try {
        const logs = await apiCall('logs', { query: '&limit=8' });
        const container = document.getElementById('activities-list');

        if (!logs || logs.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 30px 20px;">
                    <i class="fas fa-inbox"></i>
                    <p>Aucune activité récente</p>
                </div>`;
            return;
        }

        const iconMap = {
            'Connexion': { icon: 'sign-in-alt', color: 'primary' },
            'Création utilisateur': { icon: 'user-plus', color: 'success' },
            'Modification utilisateur': { icon: 'user-edit', color: 'info' },
            'Suppression utilisateur': { icon: 'user-times', color: 'danger' },
            'Création équipe': { icon: 'users', color: 'purple' },
            'Modification paramètres': { icon: 'cog', color: 'warning' }
        };

        container.innerHTML = logs.map(log => {
            const m = iconMap[log.action] || { icon: 'circle', color: 'info' };
            return `
                <div class="activity-item">
                    <div class="activity-icon kpi-icon ${m.color}" style="width:36px;height:36px;font-size:0.9rem;">
                        <i class="fas fa-${m.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${escapeHtml(log.action)}</div>
                        <div class="activity-details">${escapeHtml(log.details || '')}</div>
                        <div class="activity-time">
                            <i class="fas fa-user-shield"></i> ${escapeHtml(log.admin_nom || 'Admin')} ·
                            ${formatRelativeTime(log.created_at)}
                        </div>
                    </div>
                </div>`;
        }).join('');
    } catch (err) {
        console.error('Erreur activités:', err);
    }
}

function formatRelativeTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const diff = Date.now() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (minutes < 1) return "À l'instant";
    if (minutes < 60) return `Il y a ${minutes} min`;
    if (hours < 24) return `Il y a ${hours}h`;
    if (days < 7) return `Il y a ${days}j`;
    return formatDate(dateStr);
}

/* === EMPLOYÉS RÉCENTS === */
async function loadRecentEmployes() {
    try {
        const users = await apiCall('users', { query: '&role_id=4' });
        const tbody = document.getElementById('recent-employes-body');
        const recent = (users || []).slice(-5).reverse();

        if (recent.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Aucun employé</td></tr>`;
            return;
        }

        tbody.innerHTML = recent.map(u => `
            <tr>
                <td><strong>${escapeHtml(u.matricule)}</strong></td>
                <td>${escapeHtml(u.nom)}</td>
                <td><span class="dept-badge ${getDeptClass(u.service)}">${escapeHtml(u.service)}</span></td>
                <td>${getRoleLabel(u.role_id)}</td>
                <td><span class="status-badge ${u.statut}">${u.statut}</span></td>
            </tr>
        `).join('');
    } catch (err) {
        console.error('Erreur employés:', err);
    }
}

/* === DEMANDES EN ATTENTE === */
async function loadPendingConges() {
    try {
        const conges = await apiCall('conges', { query: '&statut=en_attente' });
        const container = document.getElementById('pending-list');
        const count = (conges || []).length;
        setText('conges-count', count);

        if (count === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 30px 20px;">
                    <i class="fas fa-check-circle"></i>
                    <p>Aucune demande en attente</p>
                </div>`;
            return;
        }

        const typeLabels = {
            'conge_annuel': 'Congé annuel',
            'conge_maladie': 'Congé maladie',
            'teletravail': 'Télétravail',
            'mission': 'Mission',
            'autre': 'Autre'
        };

        container.innerHTML = conges.slice(0, 5).map(c => `
            <div class="validation-card">
                <div class="employee-avatar" style="background:var(--warning)">${getInitials(c.employe_nom)}</div>
                <div style="flex:1">
                    <div class="employee-name">${escapeHtml(c.employe_nom)}</div>
                    <div class="text-muted" style="font-size:0.78rem">
                        ${typeLabels[c.type_demande] || c.type_demande} ·
                        ${formatDate(c.date_debut)} → ${formatDate(c.date_fin)}
                    </div>
                </div>
            </div>
        `).join('');
    } catch (err) {
        console.error('Erreur congés:', err);
    }
}

/* === NOTIFICATIONS === */
async function loadNotifications() {
    try {
        const alertes = await apiCall('alertes', { query: '&non_lue=1' });
        const list = document.getElementById('notification-list');
        const notifCount = document.getElementById('notif-count');

        if (!alertes || alertes.length === 0) {
            notifCount.style.display = 'none';
            list.innerHTML = `
                <div class="empty-state" style="padding: 40px 20px;">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification</p>
                </div>`;
            return;
        }

        notifCount.style.display = 'block';

        const colorMap = {
            faible: 'info', moyen: 'warning', eleve: 'warning', critique: 'danger',
            retard: 'warning', absence: 'danger', productivite: 'info', securite: 'danger'
        };

        list.innerHTML = alertes.slice(0, 10).map(a => {
            const color = colorMap[a.niveau] || 'info';
            return `
                <div class="notification-item unread" data-id="${a.id}">
                    <div class="notification-icon kpi-icon ${color}" style="width:32px;height:32px;font-size:0.85rem">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${escapeHtml(a.titre)}</div>
                        <div class="notification-text">${escapeHtml(a.description || '')}</div>
                        <div class="notification-time">${formatRelativeTime(a.created_at)}</div>
                    </div>
                </div>`;
        }).join('');

        list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', async () => {
                const id = item.dataset.id;
                try {
                    await apiCall('alertes', { method: 'PUT', body: { id } });
                    item.classList.remove('unread');
                    loadNotifications();
                } catch (e) {}
            });
        });
    } catch (err) {
        console.error('Erreur notifications:', err);
    }
}

function setupNotifications() {
    const btn = document.getElementById('notifications-btn');
    const dropdown = document.getElementById('notifications-dropdown');

    btn.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    const markAllBtn = document.getElementById('mark-all-read');
    markAllBtn.addEventListener('click', async () => {
        const items = document.querySelectorAll('.notification-item.unread');
        for (const item of items) {
            try {
                await apiCall('alertes', { method: 'PUT', body: { id: item.dataset.id } });
            } catch (e) {}
        }
        loadNotifications();
    });
}

/* === GRAPHIQUES === */
function initCharts() {
    const ctx1 = document.getElementById('chart-presences');
    const ctx2 = document.getElementById('chart-services');

    if (ctx1) {
        presenceChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Présents',
                    data: [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: chartOptions()
        });
    }

    if (ctx2) {
        servicesChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
}

function renderChartsData() {
    const stats = window._statsData;
    if (!stats) return;

    if (presenceChart && stats.last_7_days) {
        const labels = stats.last_7_days.map(d => {
            const date = new Date(d.jour);
            return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
        });
        const values = stats.last_7_days.map(d => parseInt(d.presents));
        presenceChart.data.labels = labels;
        presenceChart.data.datasets[0].data = values;
        presenceChart.update();
    }

    if (servicesChart && stats.stats_service) {
        servicesChart.data.labels = stats.stats_service.map(s => s.service);
        servicesChart.data.datasets[0].data = stats.stats_service.map(s => parseInt(s.effectif));
        servicesChart.update();
    }
}

function chartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    };
}
