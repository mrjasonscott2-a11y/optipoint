/**
 * OptiPoint - Authentification
 * Gestion de la connexion, déconnexion, et vérification de session
 */

document.addEventListener('DOMContentLoaded', function() {
    // Si déjà connecté, rediriger
    const user = JSON.parse(sessionStorage.getItem('optipoint_user') || 'null');
    if (user) {
        redirectBasedOnRole(user);
        return;
    }
    
    // Récupérer le matricule remembered
    const remembered = localStorage.getItem('optipoint_remembered');
    if (remembered) {
        document.getElementById('matricule').value = remembered;
        document.getElementById('remember-me').checked = true;
    }
    
    // Événements
    setupLogin();
    setupPasswordToggle();
    setupDemoAccounts();
});

/**
 * Configuration du formulaire de connexion
 */
function setupLogin() {
    const form = document.getElementById('login-form');
    if (!form) return;
    
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
    
    // Soumission avec Entrée
    form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });
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
 * Configuration des boutons de démonstration
 */
function setupDemoAccounts() {
    document.querySelectorAll('.demo-account-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const matricule = this.dataset.matricule;
            document.getElementById('matricule').value = matricule;
            document.getElementById('password').value = 'admin123';
            // Animation visuelle
            this.classList.add('active');
            setTimeout(() => login(matricule, 'admin123'), 300);
        });
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
    
    fetch('api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ matricule, password })
    })
    .then(res => {
        if (!res.ok) throw new Error('Erreur serveur (' + res.status + ')');
        return res.json();
    })
    .then(data => {
        if (data.success && data.user) {
            // Stocker la session
            sessionStorage.setItem('optipoint_user', JSON.stringify(data.user));
            
            // Remember me
            const remember = document.getElementById('remember-me').checked;
            if (remember) {
                localStorage.setItem('optipoint_remembered', matricule);
            } else {
                localStorage.removeItem('optipoint_remembered');
            }
            
            // Redirection selon le rôle
            const role = data.user.role_id || data.user.role;
            redirectBasedOnRole(data.user);
        } else {
            showAuthMessage(data.error || 'Identifiants incorrects', 'error');
            resetButton(btn, btnText, btnLoader);
        }
    })
    .catch(err => {
        console.error('Erreur connexion:', err);
        showAuthMessage('Erreur de connexion au serveur. Vérifiez que XAMPP/Laragon est démarré.', 'error');
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
 * Rediriger selon le rôle de l'utilisateur
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
    
    window.location.href = targetPage;
}

/**
 * Déconnexion
 */
function logout() {
    fetch('api.php?action=logout', { method: 'POST' })
    .finally(() => {
        sessionStorage.removeItem('optipoint_user');
        window.location.href = 'login.html';
    });
}

/**
 * Vérifier si l'utilisateur est connecté et a le bon rôle
 * Appelé au chargement de chaque page protégée
 */
function requireAuth(requiredRoleId) {
    const user = JSON.parse(sessionStorage.getItem('optipoint_user') || 'null');
    
    if (!user) {
        window.location.href = 'login.html';
        return null;
    }
    
    const roleId = parseInt(user.role_id);
    if (requiredRoleId && roleId > requiredRoleId) {
        // Pas le bon niveau de permission
        redirectBasedOnRole(user);
        return null;
    }
    
    return user;
}

/**
 * Obtenir l'utilisateur courant stocké en session
 */
function getCurrentUser() {
    return JSON.parse(sessionStorage.getItem('optipoint_user') || 'null');
}
