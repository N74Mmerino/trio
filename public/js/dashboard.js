// Gráficos del dashboard.
// Los datos los arma DashboardController y llegan en el bloque JSON #datos-dashboard;
// este archivo solo los dibuja con Chart.js (no calcula nada).

(function () {
  const nodo = document.getElementById('datos-dashboard');

  if (!nodo || typeof Chart === 'undefined') {
    return;
  }

  const datos = JSON.parse(nodo.textContent);

  // Un color por servicio (id de la tabla servicios): 1 Luz, 2 Agua, 3 Gas.
  // Etapa 18: se leen de las variables de style.css, así el gráfico usa los mismos colores que el resto de la app.
  const estilos = getComputedStyle(document.documentElement);
  const variable = (nombre, respaldo) => estilos.getPropertyValue(nombre).trim() || respaldo;
  const COLORES = { 1: variable('--luz', '#ffcf4d'), 2: variable('--agua', '#6fb3e6'), 3: variable('--gas', '#f39a5f') };
  const COLOR_POR_DEFECTO = variable('--service-none', '#b8b9b1');
  const colorDe = (id) => COLORES[id] || COLOR_POR_DEFECTO;

  // Formato argentino para ejes y tooltips.
  const pesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
  const pesosConDecimales = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const numero = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
  const pesosCompacto = new Intl.NumberFormat('es-AR', { notation: 'compact', maximumFractionDigits: 1 });

  // Estilo general tomado del prototipo (dashboard.js original).
  Chart.defaults.font.family = 'Poppins, sans-serif';
  Chart.defaults.color = variable('--text-secondary', '#74766f');

  const ejes = {
    y: {
      beginAtZero: true,
      grid: { color: variable('--chart-grid', 'rgba(32, 34, 31, 0.05)') },
      border: { display: false }
    },
    x: {
      grid: { display: false }
    }
  };

  const leyendaAbajo = {
    display: true,
    position: 'bottom',
    labels: { usePointStyle: true, boxWidth: 8, padding: 16 }
  };

  // Degradado bajo la línea, como el gráfico de área del prototipo.
  function degradado(color) {
    return (ctx) => {
      const area = ctx.chart.chartArea;
      if (!area) {
        return 'transparent';
      }
      const gradiente = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
      gradiente.addColorStop(0, color + '4d'); // 30 % de opacidad
      gradiente.addColorStop(1, color + '00');
      return gradiente;
    };
  }

  const graficos = []; // Etapa 18: se guardan para volver a pintarlos al cambiar de tema

  function crear(id, configuracion) {
    const canvas = document.getElementById(id);
    if (canvas) {
      graficos.push(new Chart(canvas, configuracion));
    }
  }

  // ---------------------------------------------------------------
  // Vista General (en pesos)
  // ---------------------------------------------------------------
  function dibujarGeneral(d) {
    if (d.mensual) {
      crear('graficoMensual', {
        type: 'bar',
        data: {
          labels: d.mensual.etiquetas,
          datasets: d.mensual.series.map((serie) => ({
            label: serie.nombre,
            data: serie.datos,
            backgroundColor: colorDe(serie.id),
            borderRadius: 6,
            maxBarThickness: 44
          }))
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: leyendaAbajo,
            tooltip: {
              callbacks: {
                label: (item) => `${item.dataset.label}: ${pesos.format(item.raw)}`,
                footer: (items) => 'Total: ' + pesos.format(items.reduce((suma, item) => suma + item.raw, 0))
              }
            }
          },
          interaction: { mode: 'index', intersect: false },
          scales: {
            x: { ...ejes.x, stacked: true },
            y: { ...ejes.y, stacked: true, ticks: { callback: (valor) => '$ ' + pesosCompacto.format(valor) } }
          }
        }
      });
    }

    if (d.distribucion) {
      const total = d.distribucion.datos.reduce((suma, valor) => suma + valor, 0);

      crear('graficoDistribucion', {
        type: 'doughnut',
        data: {
          labels: d.distribucion.etiquetas,
          datasets: [{
            data: d.distribucion.datos,
            backgroundColor: d.distribucion.ids.map(colorDe),
            borderWidth: 2,
            borderColor: variable('--surface', '#ffffff'),
            temaColor: 'superficie' // el borde entre porciones toma el color de fondo de la tarjeta
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '68%',
          plugins: {
            legend: { display: false }, // Etapa 18: la leyenda está en el HTML, con montos y porcentajes
            tooltip: {
              callbacks: {
                label: (item) => {
                  const porcentaje = total > 0 ? (item.raw / total * 100) : 0;
                  return `${item.label}: ${pesos.format(item.raw)} (${numero.format(porcentaje)} %)`;
                }
              }
            }
          }
        }
      });
    }
  }

  // ---------------------------------------------------------------
  // Vista de un servicio (en su unidad)
  // ---------------------------------------------------------------
  function dibujarServicio(d) {
    const color = colorDe(d.id);
    const sinLeyenda = { legend: { display: false } };

    crear('graficoConsumo', {
      type: 'line',
      data: {
        labels: d.etiquetas,
        datasets: [{
          label: `Consumo (${d.unidad})`,
          data: d.cantidades,
          fill: true,
          tension: 0.35,
          borderWidth: 2,
          borderColor: color,
          backgroundColor: degradado(color),
          pointRadius: 3, // con pocas boletas, los puntos tienen que verse
          pointBackgroundColor: color
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          ...sinLeyenda,
          tooltip: { callbacks: { label: (item) => `${numero.format(item.raw)} ${d.unidad}` } }
        },
        scales: {
          x: ejes.x,
          y: { ...ejes.y, ticks: { callback: (valor) => `${numero.format(valor)} ${d.unidad}` } }
        }
      }
    });

    crear('graficoImporte', {
      type: 'bar',
      data: {
        labels: d.etiquetas,
        datasets: [{
          label: 'Importe',
          data: d.importes,
          backgroundColor: color,
          borderRadius: 6,
          maxBarThickness: 40
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          ...sinLeyenda,
          tooltip: { callbacks: { label: (item) => pesosConDecimales.format(item.raw) } }
        },
        scales: {
          x: ejes.x,
          y: { ...ejes.y, ticks: { callback: (valor) => '$ ' + pesosCompacto.format(valor) } }
        }
      }
    });

    crear('graficoCosto', {
      type: 'line',
      data: {
        labels: d.etiquetas,
        datasets: [{
          label: `$ por ${d.unidad}`,
          data: d.costos,
          tension: 0.25,
          borderWidth: 2,
          borderColor: variable('--brand-text', '#45463f'),
          pointRadius: 3,
          pointBackgroundColor: variable('--brand-text', '#45463f'),
          temaColor: 'marca',
          spanGaps: true // una boleta con consumo 0 no tiene costo por unidad
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          ...sinLeyenda,
          tooltip: { callbacks: { label: (item) => `${pesosConDecimales.format(item.raw)} por ${d.unidad}` } }
        },
        scales: {
          x: ejes.x,
          y: { ...ejes.y, ticks: { callback: (valor) => pesos.format(valor) } }
        }
      }
    });
  }

  // Etapa 18: al cambiar de tema (public/js/tema.js) se releen las variables y se repintan los gráficos.
  document.addEventListener('tema-cambiado', () => {
    const texto = variable('--text-secondary', '#74766f');
    const grilla = variable('--chart-grid', 'rgba(32, 34, 31, 0.05)');
    Chart.defaults.color = texto;

    graficos.forEach((grafico) => {
      Object.values(grafico.options.scales || {}).forEach((eje) => {
        eje.ticks.color = texto;
        if (eje.grid.display !== false) {
          eje.grid.color = grilla;
        }
      });

      grafico.data.datasets.forEach((serie) => {
        if (serie.temaColor === 'superficie') {
          serie.borderColor = variable('--surface', '#ffffff');
        } else if (serie.temaColor === 'marca') {
          serie.borderColor = variable('--brand-text', '#45463f');
          serie.pointBackgroundColor = serie.borderColor;
        }
      });

      if (grafico.options.plugins.legend.labels) {
        grafico.options.plugins.legend.labels.color = texto;
      }
      grafico.update('none');
    });
  });

  if (datos.tipo === 'general') {
    dibujarGeneral(datos);
  } else if (datos.tipo === 'servicio') {
    dibujarServicio(datos);
  }
})();
