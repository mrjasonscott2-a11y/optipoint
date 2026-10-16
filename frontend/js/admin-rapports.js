/**
 * OptiPoint - Rapports & Statistiques (admin)
 */

let heuresChart;
let rapportData = null;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(1)) return;

    // Dates par défaut: mois en cours
    const today = new Date();
    const first = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('filter-debut').value = first.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = today.toISOString().split('T')[0];

    await loadUsers();
    initChart();
    await generateRapport();

    // Événements
    document.getElementById('btn-generate').addEventListener('click', generateRapport);
    document.getElementById('btn-export').addEventListener('click', exportCSV);

    // Templates
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', () => {
            const tpl = card.dataset.template;
            applyTemplate(tpl);
        });
    });
});

async function loadUsers() {
    try {
        const users = await apiCall('users', { query: '&role_id=4' });
        const select = document.getElementById('filter-user');
        select.innerHTML = '<option value="">Tous les employés</option>' +
            users.map(u => `<option value="${u.id}">${escapeHtml(u.nom)}</option>`).join('');
    } catch (err) {
        console.error('Erreur users:', err);
    }
}

function applyTemplate(tpl) {
    const today = new Date();
    let debut, fin;
    if (tpl === 'semaine') {
        debut = new Date(today);
        debut.setDate(today.getDate() - 7);
        fin = today;
    } else if (tpl === 'mois') {
        debut = new Date(today.getFullYear(), today.getMonth(), 1);
        fin = today;
    } else if (tpl === 'trimestre') {
        debut = new Date(today);
        debut.setMonth(today.getMonth() - 3);
        fin = today;
    }
    document.getElementById('filter-debut').value = debut.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = fin.toISOString().split('T')[0];
    generateRapport();
}

async function generateRapport() {
    const debut = document.getElementById('filter-debut').value;
    const fin = document.getElementById('filter-fin').value;
    const userId = document.getElementById('filter-user').value;

    if (!debut || !fin) {
        showToast('Veuillez sélectionner une période', 'warning');
        return;
    }

    let query = '&debut=' + debut + '&fin=' + fin;
    if (userId) query += '&user_id=' + userId;

    try {
        const data = await apiCall('rapport', { query });
        rapportData = data;
        renderStats(data);
        renderTable(data.pointages || []);
        renderChart(data.pointages || []);
    } catch (err) {
        showToast('Erreur génération rapport: ' + err.message, 'danger');
    }
}

function renderStats(data) {
    const totalHeures = data.total_heures || 0;
    const joursPresents = data.jours_presents || 0;
    const retards = data.retards || 0;
    const moyenne = joursPresents > 0 ? (totalHeures / joursPresents).toFixed(1) : 0;

    setText('stat-heures', totalHeures + 'h');
    setText('stat-jours', joursPresents);
    setText('stat-retards', retards);
    setText('stat-moyenne', moyenne + 'h');
}

function renderTable(pointages) {
    const tbody = document.getElementById('rapport-tbody');

    if (!pointages || pointages.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6">
            <div class="empty-state">
                <i class="fas fa-file-circle-question"></i>
                <h3>Aucune donnée</h3>
                <p>Aucun pointage sur cette période.</p>
            </div>
        </td></tr>`;
        return;
    }

    const modeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client' };

    tbody.innerHTML = pointages.map(p => `
        <tr>
            <td>${formatDate(p.date_pointage)}</td>
            <td>${escapeHtml(p.employe_nom)}</td>
            <td>${p.arrivee ? formatTime(p.arrivee) : '<span class="text-muted">-</span>'}</td>
            <td>${p.depart ? formatTime(p.depart) : '<span class="text-muted">-</span>'}</td>
            <td><strong>${p.heures ? p.heures + 'h' : '-'}</strong></td>
            <td><span class="chip">${escapeHtml(modeLabels[p.modes] || p.modes || '-')}</span></td>
        </tr>
    `).join('');
}

function initChart() {
    const ctx = document.getElementById('chart-heures');
    if (!ctx) return;
    heuresChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Heures travaillées',
                data: [],
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Heures' } },
                x: { title: { display: true, text: 'Date' } }
            }
        }
    });
}

function renderChart(pointages) {
    if (!heuresChart) return;

    // Grouper par date
    const byDate = {};
    pointages.forEach(p => {
        const date = p.date_pointage;
        if (!byDate[date]) byDate[date] = 0;
        byDate[date] += parseFloat(p.heures) || 0;
    });

    const labels = Object.keys(byDate).sort().map(d => formatDate(d));
    const values = Object.keys(byDate).sort().map(d => byDate[d]);

    heuresChart.data.labels = labels;
    heuresChart.data.datasets[0].data = values;
    heuresChart.update();
}

async function exportCSV() {
    const debut = document.getElementById('filter-debut').value;
    const fin = document.getElementById('filter-fin').value;
    try {
        showToast('Génération du CSV...', 'info');
        const data = await apiCall('export', { query: '&type=pointages&debut=' + debut + '&date_fin=' + fin });
        if (data.csv) {
            const blob = new Blob(["\uFEFF" + data.csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename || 'rapport.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Export terminé', 'success');
        }
    } catch (err) {
        showToast('Erreur export: ' + err.message, 'danger');
    }
}
