/**
 * OptiPoint - Page Pointage (tous les rôles)
 */

document.addEventListener('DOMContentLoaded', async function() {
    const user = requireAuth(4); // Tous les rôles peuvent pointer
    if (!user) return;

    buildNavForRole(parseInt(user.role_id));

    // Fil d'Ariane adapté au rôle
    const crumb = document.querySelector('.breadcrumb span:first-child');
    if (crumb) crumb.textContent = getRoleLabel(parseInt(user.role_id)) || 'Espace';

    const avatar = document.getElementById('user-avatar');
    if (avatar) avatar.textContent = getInitials(user.nom);
    setText('user-name', user.nom);
    setText('user-role', user.service || getRoleLabel(user.role_id));

    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-arrivee').addEventListener('click', () => doPointer('arrivee'));
    document.getElementById('btn-depart').addEventListener('click', () => doPointer('depart'));

    updateClock();
    setInterval(updateClock, 1000);

    await loadTodayPointage();
});

/**
 * Construit la navigation latérale selon le rôle de l'utilisateur
 */
function buildNavForRole(roleId) {
    const navs = {
        1: [ // Admin
            ['admin.html', 'fa-tachometer-alt', 'Tableau de bord'],
            ['pointage.html', 'fa-fingerprint', 'Pointer', true],
            ['admin-employes.html', 'fa-users', 'Employés'],
            ['admin-pointages.html', 'fa-clipboard-list', 'Pointages'],
            ['admin-planning.html', 'fa-calendar-alt', 'Planning'],
            ['admin-rapports.html', 'fa-chart-bar', 'Rapports'],
            ['admin-parametres.html', 'fa-cog', 'Paramètres']
        ],
        2: [ // Responsable
            ['dashboard-responsable.html', 'fa-tachometer-alt', 'Tableau de bord'],
            ['pointage.html', 'fa-fingerprint', 'Pointer', true],
            ['responsable-employes.html', 'fa-users', 'Mes employés'],
            ['responsable-pointages.html', 'fa-clipboard-list', 'Pointages'],
            ['responsable-conges.html', 'fa-umbrella-beach', 'Congés'],
            ['responsable-equipes.html', 'fa-sitemap', 'Équipes'],
            ['responsable-planning.html', 'fa-calendar-alt', 'Planning'],
            ['responsable-rapports.html', 'fa-chart-pie', 'Rapports']
        ],
        3: [ // Manager
            ['dashboard-manager.html', 'fa-tachometer-alt', 'Tableau de bord'],
            ['pointage.html', 'fa-fingerprint', 'Pointer', true],
            ['manager-employes.html', 'fa-users', 'Mon équipe'],
            ['manager-pointages.html', 'fa-clipboard-list', 'Pointages'],
            ['manager-conges.html', 'fa-umbrella-beach', 'Congés'],
            ['manager-planning.html', 'fa-calendar-alt', 'Planning'],
            ['manager-rapports.html', 'fa-chart-bar', 'Rapports']
        ],
        4: [ // Employé
            ['dashboard.html', 'fa-tachometer-alt', 'Accueil'],
            ['pointage.html', 'fa-fingerprint', 'Pointer', true],
            ['planning.html', 'fa-calendar-alt', 'Mon planning'],
            ['rapport.html', 'fa-chart-bar', 'Mes rapports']
        ]
    };
    const items = navs[roleId] || navs[4];
    const nav = document.getElementById('sidebar-nav');
    if (!nav) return;
    nav.innerHTML = '<div class="nav-section">' + (getRoleLabel(roleId) || 'Espace') + '</div>' +
        items.map(([href, icon, label, active]) =>
            '<a href="' + href + '" class="nav-item' + (active ? ' active' : '') + '"><i class="fas ' + icon + '"></i> ' + label + '</a>'
        ).join('');
}

function updateClock() {
    const now = new Date();
    setText('pointage-clock', now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
    setText('pointage-date', now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
}

async function doPointer(type) {
    const mode = document.getElementById('work-mode').value;
    const btn = document.getElementById(type === 'arrivee' ? 'btn-arrivee' : 'btn-depart');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

    try {
        const result = await pointer(type, mode);
        showToast((type === 'arrivee' ? '✅ Arrivée' : '✅ Départ') + ' enregistré à ' + result.heure +
            (result.statut === 'retard' ? ' (retard)' : ''), 'success');
        await loadTodayPointage();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
        btn.disabled = false;
    } finally {
        btn.innerHTML = original;
    }
}

async function loadTodayPointage() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const data = await apiCall('pointage', { query: '&date=' + today });
        const pointages = Array.isArray(data) ? data : (data.pointages || []);
        const hasArrivee = pointages.some(p => p.type === 'arrivee');
        const hasDepart = pointages.some(p => p.type === 'depart');

        document.getElementById('btn-arrivee').disabled = hasArrivee;
        document.getElementById('btn-depart').disabled = !hasArrivee || hasDepart;

        const tbody = document.getElementById('today-pointages-tbody');
        if (pointages.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:20px;">Aucun pointage aujourd'hui</td></tr>`;
            return;
        }
        const modeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client' };
        tbody.innerHTML = pointages.map(p => `
            <tr>
                <td><span class="chip ${p.type === 'arrivee' ? 'chip-success' : 'chip-danger'}">${p.type === 'arrivee' ? 'Arrivée' : 'Départ'}</span></td>
                <td><strong>${formatTime(p.heure)}</strong></td>
                <td><span class="chip">${modeLabels[p.mode_travail] || p.mode_travail || '-'}</span></td>
                <td><span class="status-badge ${p.statut === 'normal' ? 'actif' : 'inactif'}">${p.statut === 'normal' ? 'Normal' : 'Retard'}</span></td>
                <td><small style="color:var(--text-muted)">${escapeHtml(p.localisation || '-')}</small></td>
            </tr>
        `).join('');
    } catch (err) { console.error('Today:', err); }
}
