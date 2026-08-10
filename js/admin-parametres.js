/**
 * OptiPoint - Paramètres Système (admin)
 */

let allParams = {};

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(1)) return;

    setupTabs();
    setupGlobalModals();

    // Soumission des formulaires de paramètres
    ['form-general', 'form-pointage', 'form-conges'].forEach(id => {
        const form = document.getElementById(id);
        if (form) form.addEventListener('submit', e => handleParamSubmit(e, id));
    });

    // Gestion des lieux
    document.getElementById('btn-add-lieu').addEventListener('click', () => openLieuModal());
    document.getElementById('form-lieu').addEventListener('submit', saveLieu);

    document.getElementById('logout-btn').addEventListener('click', logout);

    // Chargement initial
    await Promise.all([loadParams(), loadLieux(), loadLogs()]);
    setText('systeme-date', new Date().toLocaleDateString('fr-FR'));
});

/* ============================================
   ONGLETS
   ============================================ */
function setupTabs() {
    document.querySelectorAll('.parametres-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.parametres-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });
}

/* ============================================
   PARAMÈTRES
   ============================================ */
async function loadParams() {
    try {
        const params = await apiCall('parametres');
        allParams = params;
        // Remplir tous les champs dont l'ID est param-<cle>
        Object.keys(params).forEach(cle => {
            const el = document.getElementById('param-' + cle);
            if (el) el.value = params[cle];
        });
    } catch (err) {
        showToast('Erreur chargement paramètres: ' + err.message, 'danger');
    }
}

async function handleParamSubmit(e, formId) {
    e.preventDefault();
    const form = document.getElementById(formId);
    const data = {};
    form.querySelectorAll('input, select, textarea').forEach(field => {
        if (field.id && field.id.startsWith('param-')) {
            const cle = field.id.replace('param-', '');
            data[cle] = field.value;
        }
    });
    // Catégorie basée sur l'onglet
    const categories = {
        'form-general': 'general',
        'form-pointage': 'pointage',
        'form-conges': 'conges'
    };
    data._categorie = categories[formId] || 'general';

    try {
        await apiCall('parametres', { method: 'PUT', body: data });
        showToast('Paramètres enregistrés avec succès', 'success');
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

/* ============================================
   LIEUX AUTORISÉS
   ============================================ */
async function loadLieux() {
    try {
        const lieux = await apiCall('lieux');
        const tbody = document.getElementById('lieux-tbody');
        if (!lieux || lieux.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">
                <div class="empty-state">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>Aucun lieu</h3>
                    <p>Ajoutez un premier lieu de pointage autorisé.</p>
                </div>
            </td></tr>`;
            return;
        }
        tbody.innerHTML = lieux.map(l => `
            <tr>
                <td><strong>${escapeHtml(l.nom)}</strong></td>
                <td>${escapeHtml(l.adresse || '-')}</td>
                <td><span class="chip">${l.latitude}, ${l.longitude}</span></td>
                <td>${l.rayon_metres} m</td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteLieu(${l.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        showToast('Erreur chargement lieux: ' + err.message, 'danger');
    }
}

function openLieuModal() {
    document.getElementById('form-lieu').reset();
    document.getElementById('lieu-id').value = '';
    document.getElementById('lieu-rayon').value = 100;
    openModal('modal-lieu');
}

async function saveLieu(e) {
    e.preventDefault();
    const data = {
        nom: document.getElementById('lieu-nom').value.trim(),
        adresse: document.getElementById('lieu-adresse').value.trim(),
        latitude: parseFloat(document.getElementById('lieu-latitude').value),
        longitude: parseFloat(document.getElementById('lieu-longitude').value),
        rayon_metres: parseInt(document.getElementById('lieu-rayon').value) || 100
    };
    if (!data.nom || isNaN(data.latitude) || isNaN(data.longitude)) {
        showToast('Veuillez remplir tous les champs obligatoires', 'warning');
        return;
    }
    try {
        await apiCall('lieux', { method: 'POST', body: data });
        showToast('Lieu ajouté avec succès', 'success');
        closeModal('modal-lieu');
        await loadLieux();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

async function deleteLieu(id) {
    if (!confirm('Supprimer ce lieu autorisé ?')) return;
    try {
        await apiCall('lieux', { method: 'DELETE', body: { id } });
        showToast('Lieu supprimé', 'success');
        await loadLieux();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

/* ============================================
   JOURNAUX ADMIN
   ============================================ */
async function loadLogs() {
    try {
        const logs = await apiCall('logs', { query: '&limit=50' });
        const container = document.getElementById('logs-timeline');
        if (!logs || logs.length === 0) {
            container.innerHTML = `<div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>Aucun journal</h3>
                <p>Aucune action administrateur enregistrée.</p>
            </div>`;
            return;
        }
        container.innerHTML = logs.map(log => {
            const date = new Date(log.created_at);
            return `<div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-title">${escapeHtml(log.action)}</div>
                    <div class="timeline-text">${escapeHtml(log.details || '')}</div>
                    <div class="timeline-time">
                        <i class="fas fa-user-shield"></i> ${escapeHtml(log.admin_nom || 'Admin')} ·
                        ${date.toLocaleDateString('fr-FR')} à ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                    </div>
                </div>
            </div>`;
        }).join('');
    } catch (err) {
        showToast('Erreur chargement journaux: ' + err.message, 'danger');
    }
}

window.deleteLieu = deleteLieu;
