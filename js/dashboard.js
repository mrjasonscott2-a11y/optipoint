/**
 * OptiPoint - Dashboard Employé
 */

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(4)) return;

    const user = getCurrentUser();
    if (user) {
        setText('welcome-title', 'Bonjour, ' + user.nom.split(' ')[0]);
        const avatar = document.getElementById('user-avatar');
        if (avatar) avatar.textContent = getInitials(user.nom);
        setText('user-name', user.nom);
        setText('user-role', user.service || 'Collaborateur');
    }

    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-arrivee').addEventListener('click', () => doPointer('arrivee'));
    document.getElementById('btn-depart').addEventListener('click', () => doPointer('depart'));

    initGeolocation();

    // Horloge temps réel
    updateClock();
    setInterval(updateClock, 1000);

    await Promise.all([loadStats(), loadTodayPointage(), loadRecentPointages(), loadMyConges()]);
});

function updateClock() {
    const now = new Date();
    setText('current-time-big', now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
    setText('current-date-display', now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
}

/* === STATS === */
async function loadStats() {
    try {
        const data = await apiCall('stats');
        setText('stat-heures-semaine', data.heures_semaine || 0);
        setText('stat-jours-semaine', data.jours_semaine || 0);
        setText('stat-pointages-aujourdhui', data.today_pointages || 0);
    } catch (err) { console.error('Stats:', err); }
}

/* === POINTAGE DU JOUR === */
async function loadTodayPointage() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const data = await apiCall('pointage', { query: '&date=' + today });
        const pointages = Array.isArray(data) ? data : (data.pointages || []);
        const hasArrivee = pointages.some(p => p.type === 'arrivee');
        const hasDepart = pointages.some(p => p.type === 'depart');

        document.getElementById('btn-arrivee').disabled = hasArrivee;
        document.getElementById('btn-depart').disabled = !hasArrivee || hasDepart;

        if (hasArrivee && !hasDepart) {
            showToast("Vous avez pointé votre arrivée. N'oubliez pas de pointer votre départ.", 'info');
        }
    } catch (err) { console.error('Today pointage:', err); }
}

async function doPointer(type) {
    const mode = document.getElementById('work-mode').value;
    const btn = document.getElementById(type === 'arrivee' ? 'btn-arrivee' : 'btn-depart');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

    try {
        await pointer(type, mode);
        showToast(type === 'arrivee' ? 'Arrivée enregistrée !' : 'Départ enregistré !', 'success');
        await loadTodayPointage();
        await loadStats();
        await loadRecentPointages();
    } catch (err) {
        showToast('Erreur pointage: ' + err.message, 'danger');
        btn.disabled = false;
    } finally {
        btn.innerHTML = type === 'arrivee'
            ? '<i class="fas fa-sign-in-alt"></i> Pointer mon arrivée'
            : '<i class="fas fa-sign-out-alt"></i> Pointer mon départ';
    }
}

/* === HISTORIQUE RÉCENT === */
async function loadRecentPointages() {
    try {
        const data = await apiCall('pointage', { query: '&limit=10' });
        const pointages = Array.isArray(data) ? data : (data.pointages || []);
        const tbody = document.getElementById('recent-pointages');
        if (pointages.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:20px;">Aucun pointage récent</td></tr>`;
            return;
        }
        const modeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client' };
        tbody.innerHTML = pointages.slice(0, 8).map(p => `
            <tr>
                <td>${formatDate(p.date_pointage)}</td>
                <td><span class="chip ${p.type === 'arrivee' ? 'chip-success' : 'chip-danger'}">${p.type === 'arrivee' ? 'Arrivée' : 'Départ'}</span></td>
                <td>${formatTime(p.heure)}</td>
                <td><span class="chip">${modeLabels[p.mode_travail] || p.mode_travail || '-'}</span></td>
                <td><span class="status-badge ${p.statut === 'normal' ? 'actif' : 'inactif'}">${p.statut === 'normal' ? 'Normal' : 'Retard'}</span></td>
            </tr>
        `).join('');
    } catch (err) { console.error('Recent:', err); }
}

/* === MES CONGÉS === */
async function loadMyConges() {
    try {
        const conges = await apiCall('conges');
        setText('stat-conges', Array.isArray(conges) ? conges.length : 0);
    } catch (err) { console.error('Conges:', err); }
}
