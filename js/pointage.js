/**
 * OptiPoint - Page Pointage Employé
 */

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(4)) return;

    const user = getCurrentUser();
    if (user) {
        const avatar = document.getElementById('user-avatar');
        if (avatar) avatar.textContent = getInitials(user.nom);
        setText('user-name', user.nom);
        setText('user-role', user.service || 'Collaborateur');
    }

    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-arrivee').addEventListener('click', () => doPointer('arrivee'));
    document.getElementById('btn-depart').addEventListener('click', () => doPointer('depart'));

    initGeolocation();
    updateClock();
    setInterval(updateClock, 1000);

    await Promise.all([loadTodayPointage(), checkGeoStatus()]);
});

function updateClock() {
    const now = new Date();
    setText('pointage-clock', now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
    setText('pointage-date', now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
}

function checkGeoStatus() {
    const status = document.getElementById('geo-status');
    const coords = document.getElementById('geo-coords');
    const address = document.getElementById('geo-address');

    // Surveille les coordonnées (mises à jour par initGeolocation dans app.js)
    setTimeout(() => {
        if (window.userCoords) {
            status.className = 'alert alert-success';
            status.innerHTML = '<i class="fas fa-check-circle"></i><div>Position détectée avec succès</div>';
            coords.textContent = window.userCoords.lat.toFixed(6) + ', ' + window.userCoords.lon.toFixed(6);
            if (window.userLocation) {
                address.innerHTML = '<i class="fas fa-map-pin"></i> ' + escapeHtml(window.userLocation);
            }
        } else {
            status.className = 'alert alert-warning';
            status.innerHTML = '<i class="fas fa-exclamation-triangle"></i><div>Géolocalisation non disponible. Veuillez autoriser l'accès à votre position.</div>';
        }
    }, 3000);
}

async function doPointer(type) {
    const mode = document.getElementById('work-mode').value;
    const btn = document.getElementById(type === 'arrivee' ? 'btn-arrivee' : 'btn-depart');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

    try {
        await pointer(type, mode);
        showToast(type === 'arrivee' ? '✅ Arrivée enregistrée avec succès !' : '✅ Départ enregistré avec succès !', 'success');
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
