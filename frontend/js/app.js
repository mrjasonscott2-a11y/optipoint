/**
 * OptiPoint - Application principale
 * Fonctions communes utilisées sur toutes les pages
 */

// Stockage global
let currentUser = null;
let userLocation = null;
let userCoords = null;

/**
 * Base absolue de l'application (ex: http://localhost/optipoint/)
 * Immunise les fetch et les redirections contre toute corruption d'URL relative
 */
function appBase() {
    const p = window.location.pathname;
    const dir = p.substring(0, p.lastIndexOf('/') + 1);
    return window.location.protocol + '//' + window.location.host + dir;
}

document.addEventListener('DOMContentLoaded', function() {
    // Authentification (sauf page login)
    if (!isAuthPage()) {
        currentUser = getCurrentUser();
        if (!currentUser) {
            window.location.replace(appBase() + 'login.html');
            return;
        }
    }

    // Horloge en temps réel
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Date
    updateDate();

    // Sidebar (mobile)
    setupSidebar();

    // Géolocalisation (si supportée et nécessaire)
    if (!isAuthPage()) {
        initGeolocation();
    }

    // Déconnexion : binding central. logout() a une garde anti-double-exécution,
    // le binding propre à certaines pages ne pose donc aucun problème.
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn && !logoutBtn.dataset.bound) {
        logoutBtn.dataset.bound = '1';
        logoutBtn.addEventListener('click', logout);
    }

    // Initialiser les modals globaux
    setupGlobalModals();
});

/* ============================================
   AUTHENTIFICATION
   ============================================ */

function isAuthPage() {
    const page = window.location.pathname.split('/').pop().toLowerCase();
    return page === 'login.html' || page === 'register.html' || page === '';
}

function getCurrentUser() {
    return JSON.parse(sessionStorage.getItem('optipoint_user') || 'null');
}

function requireAuth(requiredRoleId) {
    const user = getCurrentUser();
    if (!user) {
        window.location.replace(appBase() + 'login.html');
        return null;
    }
    const roleId = parseInt(user.role_id);
    if (requiredRoleId && roleId > requiredRoleId) {
        redirectBasedOnRole(user);
        return null;
    }
    return user;
}

function redirectBasedOnRole(user) {
    const roleId = parseInt(user.role_id);
    let page;
    switch (roleId) {
        case 1: page = 'admin.html'; break;
        case 2: page = 'dashboard-responsable.html'; break;
        case 3: page = 'dashboard-manager.html'; break;
        case 4:
        default: page = 'dashboard.html'; break;
    }
    window.location.replace(appBase() + page);
}

function confirmLogout() {
    if (!confirm('Voulez-vous vraiment vous déconnecter ?')) return;
    logout();
}

let _logoutInProgress = false;
function logout() {
    if (_logoutInProgress) return;
    _logoutInProgress = true;
    fetch(appBase() + 'api.php?action=logout', { method: 'POST', credentials: 'same-origin' })
    .catch(() => {})
    .finally(() => {
        sessionStorage.removeItem('optipoint_user');
        window.location.replace(appBase() + 'login.html');
    });
}

/* ============================================
   API HELPER
   ============================================ */

/**
 * Appel API générique
 * @param {string} action - Nom de l'action
 * @param {object} options - { method, body }
 */
async function apiCall(action, options = {}) {
    const method = options.method || 'GET';
    const config = {
        method,
        headers: {}
    };
    if (method !== 'GET' && options.body) {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(options.body);
    }
    try {
        const res = await fetch(appBase() + '../backend/api.php?action=' + action + (options.query || ''), config);
        const data = await res.json();
        if (!res.ok && !data.success) {
            throw new Error(data.error || 'Erreur serveur (' + res.status + ')');
        }
        return data;
    } catch (err) {
        console.error('API Error [' + action + ']:', err.message);
        throw err;
    }
}

/* ============================================
   HORLOGE / DATE
   ============================================ */

function updateDateTime() {
    const now = new Date();
    const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const timeShort = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

    setText('current-time', time);
    setText('pointage-time-display', timeShort);
    setText('current-time-short', timeShort);
}

function updateDate() {
    const now = new Date();
    const dateLong = now.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const dateShort = now.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });

    setText('current-date', dateLong);
    setText('pointage-date', dateShort);
    setText('header-date', dateShort);
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

/* ============================================
   SIDEBAR
   ============================================ */

function setupSidebar() {
    // Marquer la page active
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Toggle mobile
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
    }

    // Mettre à jour les infos utilisateur dans la sidebar
    updateSidebarUser();
}

function updateSidebarUser() {
    if (!currentUser) return;
    setText('user-name', currentUser.nom);
    setText('user-role', getRoleLabel(currentUser.role_id));
    setText('username-display', currentUser.nom);

    // Avatar
    const avatar = document.getElementById('user-avatar');
    if (avatar) {
        const initials = getInitials(currentUser.nom);
        avatar.textContent = initials;
    }
}

function getRoleLabel(roleId) {
    const roles = { 1: 'Administrateur', 2: 'Responsable', 3: 'Manager', 4: 'Employé' };
    return roles[roleId] || 'Utilisateur';
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

/* ============================================
   GÉOLOCALISATION
   ============================================ */

function initGeolocation() {
    if (!navigator.geolocation) {
        userLocation = 'Géolocalisation non supportée par votre navigateur';
        updateLocationDisplay(userLocation);
        return;
    }

    updateLocationDisplay('Détection de votre position...');

    navigator.geolocation.getCurrentPosition(
        async position => {
            userCoords = {
                lat: position.coords.latitude,
                lon: position.coords.longitude
            };
            localStorage.setItem('last_coords', JSON.stringify(userCoords));
            // Afficher immédiatement les coordonnées, puis chercher l'adresse
            userLocation = formatCoords(userCoords);
            updateLocationDisplay(userLocation);
            await reverseGeocode();
        },
        error => {
            console.warn('Géolocalisation:', error.message);
            const messages = {
                1: 'Accès à la position refusé. Autorisez la géolocalisation dans votre navigateur.',
                2: 'Position indisponible.',
                3: 'Délai de géolocalisation dépassé.'
            };
            userLocation = messages[error.code] || 'Localisation non disponible';
            updateLocationDisplay(userLocation);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
    );
}

function formatCoords(c) {
    return 'Lat ' + c.lat.toFixed(5) + ', Lon ' + c.lon.toFixed(5);
}

/**
 * Reverse geocoding : convertit les coordonnées GPS en adresse lisible
 * (service gratuit OpenStreetMap Nominatim)
 */
async function reverseGeocode() {
    if (!userCoords) return;
    updateLocationDisplay('Recherche de l\'adresse...');
    try {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat='
            + userCoords.lat + '&lon=' + userCoords.lon + '&accept-language=fr&zoom=18';
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const a = data.address || {};
        const parts = [
            a.house_number && a.road ? a.house_number + ' ' + a.road : (a.road || ''),
            a.suburb || a.city_district || a.village || '',
            a.city || a.town || '',
            a.country || ''
        ].filter(Boolean);
        userLocation = parts.length > 0 ? parts.join(', ') : (data.display_name || formatCoords(userCoords));
    } catch (err) {
        console.warn('Reverse geocoding indisponible:', err.message);
        // On garde les coordonnées brutes si le service ne répond pas
        if (!userLocation || userLocation === 'Recherche de l\'adresse...' || userLocation === 'Détection de votre position...') {
            userLocation = userCoords ? formatCoords(userCoords) : 'Localisation non disponible';
        }
    }
    updateLocationDisplay(userLocation);
}

function updateLocationDisplay(location) {
    setText('current-location', location);
    setText('pointage-location', location);
    setText('location-text', location);
    // Panneau de la page pointage.html
    setText('geo-address', location);
    const coordsEl = document.getElementById('geo-coords');
    if (coordsEl && userCoords) coordsEl.textContent = formatCoords(userCoords);
    const statusEl = document.getElementById('geo-status');
    if (statusEl && location) {
        const ok = userCoords && !/refusé|indisponible|dépassé|non supportée|non disponible/i.test(location);
        statusEl.className = 'alert ' + (ok ? 'alert-success' : 'alert-warning');
        statusEl.innerHTML = ok
            ? '<i class="fas fa-check-circle"></i><div>Position détectée avec succès</div>'
            : '<i class="fas fa-exclamation-triangle"></i><div>' + location + '</div>';
    }
}

/* ============================================
   POINTAGE (fonction unifiée)
   ============================================ */

async function pointer(type, mode) {
    if (!currentUser) throw new Error('Utilisateur non connecté');

    // Mode : argument > select > bouton radio > défaut
    if (!mode) {
        const select = document.getElementById('work-mode');
        if (select) {
            mode = select.value;
        } else {
            const radio = document.querySelector('input[name="work-mode"]:checked');
            mode = radio ? radio.value : 'bureau';
        }
    }
    const note = document.getElementById('pointage-note')?.value || '';

    const data = {
        type,
        mode,
        location: userLocation || 'Non spécifié',
        latitude: userCoords?.lat || null,
        longitude: userCoords?.lon || null,
        notes: note
    };

    const result = await apiCall('pointage', { method: 'POST', body: data });

    // Mise à jour de l'UI (IDs communs aux pages dashboard et pointage)
    if (type === 'arrivee') {
        setText('arrival-time', result.heure);
        const btn = document.getElementById('btn-arrivee') || document.getElementById('btn-arrival');
        if (btn) btn.disabled = true;
        const btnD = document.getElementById('btn-depart') || document.getElementById('btn-departure');
        if (btnD) btnD.disabled = false;
    } else {
        setText('departure-time', result.heure);
        const btn = document.getElementById('btn-depart') || document.getElementById('btn-departure');
        if (btn) btn.disabled = true;
    }

    return result;
}

/* ============================================
   NOTIFICATIONS / TOASTS
   ============================================ */

function showToast(message, type = 'info', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;

    const icons = {
        success: 'check-circle',
        danger: 'times-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };

    toast.innerHTML = `
        <i class="fas fa-${icons[type] || 'info-circle'}"></i>
        <span>${message}</span>
        <button class="toast-close"><i class="fas fa-times"></i></button>
    `;

    container.appendChild(toast);

    // Fermeture
    toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());

    // Auto-fermeture
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function showAlert(message, type = 'info') {
    const content = document.querySelector('.page-content') || document.querySelector('.content');
    if (!content) {
        showToast(message, type);
        return;
    }

    const icons = {
        success: 'check-circle',
        danger: 'times-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };

    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.innerHTML = `<i class="fas fa-${icons[type]}"></i><span>${message}</span>`;
    content.insertBefore(alert, content.firstChild);

    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/* ============================================
   MODALS
   ============================================ */

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('show');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('show');
}

function setupGlobalModals() {
    // Fermer en cliquant sur le backdrop ou bouton close
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) {
                overlay.classList.remove('show');
            }
        });
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('show');
        });
    });

    // Échap pour fermer
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
        }
    });
}

/* ============================================
   FORMATAGE
   ============================================ */

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatDateLong(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    return timeStr.substring(0, 5);
}

function formatHeure(timeStr) {
    if (!timeStr) return '-';
    return timeStr.substring(0, 5);
}

function getDeptClass(dept) {
    const map = {
        'Informatique': 'dept-it',
        'Commercial': 'dept-commercial',
        'Ressources Humaines': 'dept-rh',
        'Administration': 'dept-administration'
    };
    return map[dept] || 'dept-default';
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/* ============================================
   PAGINATION
   ============================================ */

let currentPage = 1;
let itemsPerPage = 10;

function paginate(items, page = 1, perPage = 10) {
    currentPage = page;
    itemsPerPage = perPage;
    const start = (page - 1) * perPage;
    const end = start + perPage;
    return {
        items: items.slice(start, end),
        total: items.length,
        totalPages: Math.ceil(items.length / perPage),
        currentPage: page,
        start: start + 1,
        end: Math.min(end, items.length)
    };
}

function renderPagination(container, paginationInfo, onPageChange) {
    if (!container) return;
    container.innerHTML = '';

    if (paginationInfo.totalPages <= 1) return;

    // Bouton précédent
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = paginationInfo.currentPage === 1;
    prevBtn.addEventListener('click', () => onPageChange(paginationInfo.currentPage - 1));
    container.appendChild(prevBtn);

    // Numéros de page
    for (let i = 1; i <= paginationInfo.totalPages; i++) {
        const btn = document.createElement('button');
        btn.className = 'pagination-btn' + (i === paginationInfo.currentPage ? ' active' : '');
        btn.textContent = i;
        btn.addEventListener('click', () => onPageChange(i));
        container.appendChild(btn);
    }

    // Bouton suivant
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = paginationInfo.currentPage === paginationInfo.totalPages;
    nextBtn.addEventListener('click', () => onPageChange(paginationInfo.currentPage + 1));
    container.appendChild(nextBtn);
}

/* ============================================
   EXPORT UTILITAIRES
   ============================================ */

window.getCurrentUser = getCurrentUser;
window.requireAuth = requireAuth;
window.redirectBasedOnRole = redirectBasedOnRole;
window.logout = logout;
window.apiCall = apiCall;
window.setText = setText;
window.showToast = showToast;
window.showAlert = showAlert;
window.openModal = openModal;
window.closeModal = closeModal;
window.pointer = pointer;
window.formatDate = formatDate;
window.formatDateLong = formatDateLong;
window.formatTime = formatTime;
window.getDeptClass = getDeptClass;
window.escapeHtml = escapeHtml;
window.paginate = paginate;
window.renderPagination = renderPagination;
window.getRoleLabel = getRoleLabel;
window.getInitials = getInitials;
