/**
 * OptiPoint - Responsable: Rapports & Statistiques
 */

let heuresChart;

document.addEventListener('DOMContentLoaded', async function() {
    if (!requireAuth(2)) return;
    document.getElementById('logout-btn').addEventListener('click', logout);
    document.getElementById('btn-generate').addEventListener('click', generateRapport);
    document.getElementById('btn-export').addEventListener('click', exportCSV);

    // Templates
    document.querySelectorAll('[data-template]').forEach(card => {
        card.addEventListener('click', () => applyTemplate(card.dataset.template));
    });

    // Dates par défaut: mois en cours
    const today = new Date();
    const first = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('filter-debut').value = first.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = today.toISOString().split('T')[0];

    initChart();
    await generateRapport();
});

function applyTemplate(tpl) {
    const today = new Date();
    let debut, fin;
    if (tpl === 'semaine') { debut = new Date(today); debut.setDate(today.getDate() - 7); fin = today; }
    else if (tpl === 'mois') { debut = new Date(today.getFullYear(), today.getMonth(), 1); fin = today; }
    else if (tpl === 'trimestre') { debut = new Date(today); debut.setMonth(today.getMonth() - 3); fin = today; }
    document.getElementById('filter-debut').value = debut.toISOString().split('T')[0];
    document.getElementById('filter-fin').value = fin.toISOString().split('T')[0];
    generateRapport();
}

async function generateRapport() {
    const debut = document.getElementById('filter-debut').value;
    const fin = document.getElementById('filter-fin').value;
    if (!debut || !fin) { showToast('Sélectionnez une période', 'warning'); return; }

    try {
        const data = await apiCall('rapport', { query: '&debut=' + debut + '&fin=' + fin });
        renderStats(data);
        renderTable(data.pointages || []);
        renderChart(data.pointages || []);
    } catch (err) {
        showToast('Erreur: ' + err.message, 'danger');
    }
}

function renderStats(data) {
    const h = data.total_heures || 0;
    const j = data.jours_presents || 0;
    setText('stat-heures', h + 'h');
    setText('stat-jours', j);
    setText('stat-retards', data.retards || 0);
    setText('stat-moyenne', j > 0 ? (h / j).toFixed(1) + 'h' : '0h');
}

function renderTable(pointages) {
    const tbody = document.getElementById('rapport-tbody');
    if (!pointages.length) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fas fa-file-circle-question"></i><h3>Aucune donnée</h3></div></td></tr>`;
        return;
    }
    const modeLabels = { bureau: 'Bureau', teletravail: 'Télétravail', client: 'Client' };
    tbody.innerHTML = pointages.map(p => `
        <tr>
            <td>${formatDate(p.date_pointage)}</td>
            <td>${escapeHtml(p.employe_nom)}</td>
            <td>${p.arrivee ? formatTime(p.arrivee) : '-'}</td>
            <td>${p.depart ? formatTime(p.depart) : '-'}</td>
            <td><strong>${p.heures ? p.heures + 'h' : '-'}</strong></td>
            <td><span class="chip">${modeLabels[p.modes] || p.modes || '-'}</span></td>
        </tr>
    `).join('');
}

function initChart() {
    const ctx = document.getElementById('chart-heures');
    if (!ctx) return;
    heuresChart = new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Heures', data: [], backgroundColor: 'rgba(139,92,246,0.7)', borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Heures' } }, x: { title: { display: true, text: 'Date' } } } }
    });
}

function renderChart(pointages) {
    if (!heuresChart) return;
    const byDate = {};
    pointages.forEach(p => { const d = p.date_pointage; byDate[d] = (byDate[d] || 0) + (parseFloat(p.heures) || 0); });
    const dates = Object.keys(byDate).sort();
    heuresChart.data.labels = dates.map(d => formatDate(d));
    heuresChart.data.datasets[0].data = dates.map(d => byDate[d]);
    heuresChart.update();
}

async function exportCSV() {
    const debut = document.getElementById('filter-debut').value;
    const fin = document.getElementById('filter-fin').value;
    try {
        const data = await apiCall('export', { query: '&type=pointages&debut=' + debut + '&date_fin=' + fin });
        if (data.csv) {
            const blob = new Blob(["\uFEFF" + data.csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = data.filename || 'rapport-responsable.csv';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            showToast('Export terminé', 'success');
        }
    } catch (err) { showToast('Erreur export: ' + err.message, 'danger'); }
}
