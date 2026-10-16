/**
 * OptiPoint - Authentification
 * Gestion de la connexion, déconnexion, et vérification de session
 */

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
    // Si déjà connecté, rediriger
    try {
        const user = JSON.parse(sessionStorage.getItem('optipoint_user') || 'null');
        if (user) {
            redirectBasedOnRole(user);
            return;
        }
    } catch (e) { console.warn('Session illisible:', e); }

    // Récupérer le matricule remembered
    try {
        const remembered = localStorage.getItem('optipoint_remembered');
        if (remembered) {
            document.getElementById('matricule').value = remembered;
            document.getElementById('remember-me').checked = true;
        }
    } catch (e) { /* localStorage indisponible : ignorer */ }

    // Événements
    setupLogin();
    setupPasswordToggle();
});

/**
 * Configuration du formulaire de connexion
 */
function setupLogin() {
    const form = document.getElementById('login-form');
    if (!form) return;

    // Garde absolue contre la soumission native du formulaire (recharge de page)
    // même si le JS échouait avant l'attachement du listener
    form.setAttribute('action', 'javascript:void(0)');
    form.setAttribute('autocomplete', 'on');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const matricule = document.getElementById('matricule').value.trim();
        const password = document.getElementById('password').value;

        if (!matricule || !password) {
            showAuthMessage('Veuillez remplir tous les champs', 'error');
            return;
        }

        login(matricule, password);
    });
    // NB: la touche Entrée déclenche nativement l'événement submit du formulaire,
    // pas besoin de handler clavier dédié (source de double-soumission).
}

/**
 * Toggle visibilité du mot de passe
 */
function setupPasswordToggle() {
    const toggleBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    if (!toggleBtn || !passwordInput) return;

    toggleBtn.addEventListener('click', function() {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        this.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
}

/**
 * Connexion via API
 */
function login(matricule, password) {
    const btn = document.getElementById('login-btn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');

    // État chargement
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    hideAuthMessage();

    fetch(appBase() + '../backend/api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ matricule, password }),
        credentials: 'same-origin'
    })
    .then(res => {
        // Lire le corps JSON quelle que soit la réponse (200, 400, 401, 500...)
        return res.json().then(body => ({ ok: res.ok, status: res.status, body }))
            .catch(() => ({ ok: false, status: res.status, body: null }));
    })
    .then(({ ok, status, body }) => {
        if (ok && body && body.success && body.user) {
            // Stocker la session
            sessionStorage.setItem('optipoint_user', JSON.stringify(body.user));

            // Remember me
            try {
                const remember = document.getElementById('remember-me').checked;
                if (remember) {
                    localStorage.setItem('optipoint_remembered', matricule);
                } else {
                    localStorage.removeItem('optipoint_remembered');
                }
            } catch (e) { /* ignorer */ }

            // Redirection selon le rôle
            redirectBasedOnRole(body.user);
        } else if (body && body.error) {
            // Erreur renvoyée par l'API (identifiants incorrects, compte inactif...)
            showAuthMessage(body.error, 'error');
            resetButton(btn, btnText, btnLoader);
        } else if (!body) {
            // Réponse illisible (page d'erreur HTML, etc.)
            showAuthMessage('Réponse invalide du serveur (code ' + status + '). Rechargez la page avec Ctrl+F5 et réessayez.', 'error');
            resetButton(btn, btnText, btnLoader);
        } else {
            showAuthMessage('Erreur inconnue (code ' + status + ')', 'error');
            resetButton(btn, btnText, btnLoader);
        }
    })
    .catch(err => {
        console.error('Erreur connexion:', err);
        showAuthMessage('Impossible de joindre le serveur. Vérifiez qu\'Apache est démarré, puis rechargez la page (Ctrl+F5).', 'error');
        resetButton(btn, btnText, btnLoader);
    });
}

/**
 * Réinitialiser le bouton après une erreur
 */
function resetButton(btn, btnText, btnLoader) {
    btn.disabled = false;
    btnText.style.display = 'inline';
    btnLoader.style.display = 'none';
}

/**
 * Afficher un message d'authentification (erreur ou succès)
 */
function showAuthMessage(message, type) {
    const container = document.getElementById('auth-message');
    if (!container) return;

    container.style.display = 'flex';
    container.className = 'auth-message auth-message-' + type;

    const icon = type === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle';
    container.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';

    // Auto-masquer après 6 secondes
    clearTimeout(container._timeout);
    container._timeout = setTimeout(() => {
        container.style.display = 'none';
    }, 6000);
}

/**
 * Masquer le message
 */
function hideAuthMessage() {
    const container = document.getElementById('auth-message');
    if (container) container.style.display = 'none';
}

/**
 * Rediriger selon le rôle de l'utilisateur (URL absolue)
 */
function redirectBasedOnRole(user) {
    const roleId = parseInt(user.role_id);
    let targetPage;

    switch (roleId) {
        case 1: targetPage = 'admin.html'; break;           // Admin
        case 2: targetPage = 'dashboard-responsable.html'; break; // Responsable
        case 3: targetPage = 'dashboard-manager.html'; break;    // Manager
        case 4: // Employé
        default: targetPage = 'dashboard.html'; break;
    }

    window.location.replace(appBase() + targetPage);
}

/**
 * Déconnexion
 */
function logout() {
    fetch(appBase() + '../backend/api.php?action=logout', { method: 'POST', credentials: 'same-origin' })
    .catch(() => {})
    .finally(() => {
        sessionStorage.removeItem('optipoint_user');
        window.location.replace(appBase() + 'login.html');
    });
}
