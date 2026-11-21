// Gráfico del inicio del administrador (Etapa 14).
// Los datos los arma AdminController y llegan en el bloque JSON #datos-admin; este archivo solo los dibuja.

(function () {
  const nodo = document.getElementById('datos-admin');
  const canvas = document.getElementById('graficoAdmin');

  if (!nodo || !canvas || typeof Chart === 'undefined') {
    return;
  }

  const datos = JSON.parse(nodo.textContent);

  // Mismo color por servicio que dashboard.js: 1 Luz, 2 Agua, 3 Gas.
  const COLORES = { 1: '#ffcf4d', 2: '#5b9bd5', 3: '#e0894a' };

  Chart.defaults.font.family = 'Poppins, sans-serif';
  Chart.defaults.color = '#74766f';

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: datos.etiquetas,
      datasets: datos.series.map((serie) => ({
        label: serie.nombre,
        data: serie.datos,
        backgroundColor: COLORES[serie.id] || '#b8b9b1',
        borderRadius: 6,
        maxBarThickness: 44
      }))
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
        tooltip: {
          callbacks: {
            label: (item) => `${item.dataset.label}: ${item.raw} ${item.raw === 1 ? 'boleta' : 'boletas'}`,
            footer: (items) => 'Total: ' + items.reduce((suma, item) => suma + item.raw, 0)
          }
        }
      },
      scales: {
        x: { stacked: true, grid: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(32, 34, 31, 0.05)' }, border: { display: false } }
      }
    }
  });
})();
