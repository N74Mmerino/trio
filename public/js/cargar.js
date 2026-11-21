// Carga de boletas por pasos (Etapa 20).
// Solo ayuda visual mientras se escribe: la validación que vale es la de PHP
// (ConsumoController::normalizarNumero y validarConsumo). Sin JavaScript la pantalla funciona igual.

(function () {
  // ---------------------------------------------------------------
  // Paso 1: mostrar el nombre del PDF elegido y resaltar la zona al arrastrar.
  // ---------------------------------------------------------------
  const zona = document.querySelector('.dropzone');

  if (zona) {
    const archivo = zona.querySelector('input[type="file"]');
    const nombre = zona.querySelector('.dropzone-file');

    archivo.addEventListener('change', function () {
      const elegido = archivo.files[0];
      nombre.textContent = elegido ? 'Elegiste: ' + elegido.name : '';
      nombre.hidden = !elegido;
    });

    ['dragenter', 'dragover'].forEach(function (evento) {
      zona.addEventListener(evento, function () { zona.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (evento) {
      zona.addEventListener(evento, function () { zona.classList.remove('is-dragover'); });
    });
  }

  // ---------------------------------------------------------------
  // Paso 2: vista previa y lista "Antes de guardar" en vivo.
  // ---------------------------------------------------------------
  const form = document.getElementById('form-revisar');

  if (!form) {
    return;
  }

  const hoy = form.dataset.hoy;
  const duplicadoInicial = form.dataset.duplicado; // 'repetida', 'controlado' o 'pendiente'
  const servicioLeido = form.dataset.servicioLeido;
  const fechaLeida = form.dataset.fechaLeida;

  const radios = Array.from(form.querySelectorAll('input[name="id_servicio"]'));
  const cantidad = form.querySelector('#cantidad');
  const importe = form.querySelector('#importe');
  const fecha = form.querySelector('#fecha');
  const confirmar = form.querySelector('[data-confirmar]');
  const previa = function (clave) { return form.querySelector('[data-preview="' + clave + '"]'); };
  const MARCAS = { ok: '✓', error: '!', pendiente: '·' };

  // Misma regla que ConsumoController::normalizarNumero(): con coma, los puntos son de miles;
  // sin coma, grupos de a tres también son miles ("152.658"). Devuelve null si está vacío y NaN si no es un número.
  function numero(texto) {
    let t = texto.replace(/[\s$]/g, '');

    if (t === '') {
      return null;
    }
    if (t.indexOf(',') !== -1) {
      t = t.replace(/\./g, '').replace(',', '.');
    } else if (/^\d{1,3}(\.\d{3})+$/.test(t)) {
      t = t.replace(/\./g, '');
    }

    return /^\d*\.?\d+$/.test(t) ? parseFloat(t) : NaN;
  }

  // Igual que Formato::numero(): sin decimales si es entero, si no con dos.
  function formatoNumero(valor) {
    const decimales = Number.isInteger(valor) ? 0 : 2;
    return valor.toLocaleString('es-AR', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
  }

  function formatoFecha(iso) {
    return iso.split('-').reverse().join('/');
  }

  function marcar(clave, estado, texto, nota) {
    const item = form.querySelector('[data-check="' + clave + '"]');

    if (!item) {
      return;
    }
    item.className = 'check is-' + estado;
    item.querySelector('.check-mark').textContent = MARCAS[estado];
    item.querySelector('.check-text').textContent = texto;
    item.querySelector('.check-note').textContent = nota || '';
  }

  function controlarNumero(clave, valor, maximo, faltaTexto, okTexto, errorTexto) {
    if (valor === null) {
      marcar(clave, 'pendiente', faltaTexto);
      return false;
    }
    if (isNaN(valor) || valor > maximo) {
      marcar(clave, 'error', errorTexto);
      return false;
    }
    marcar(clave, 'ok', okTexto);
    return true;
  }

  function actualizar() {
    const radio = radios.find(function (r) { return r.checked; });
    const idServicio = radio ? radio.value : '';
    const nombreServicio = radio ? radio.dataset.nombre : 'Servicio';
    const unidad = radio ? radio.dataset.unidad : '—';

    // Servicio: ícono, nombre y unidad (en la vista previa y al lado de la cantidad).
    form.querySelectorAll('[data-icono]').forEach(function (icono) {
      icono.hidden = icono.dataset.icono !== (idServicio || '0');
    });
    previa('servicio').textContent = nombreServicio;
    previa('unidad').textContent = unidad;
    form.querySelector('[data-unidad-campo]').textContent = unidad;
    if (radio) {
      marcar('servicio', 'ok', 'Servicio: ' + nombreServicio);
    } else {
      marcar('servicio', 'pendiente', 'Elegí el servicio');
    }

    // Cantidad e importe (mismos rangos que validarConsumo).
    const valorCantidad = numero(cantidad.value);
    const valorImporte = numero(importe.value);
    const cantidadOk = controlarNumero('cantidad', valorCantidad, 99999999.99, 'Falta la cantidad', 'Cantidad válida', 'La cantidad no es un número válido');
    const importeOk = controlarNumero('importe', valorImporte, 9999999999.99, 'Falta el importe', 'Importe válido', 'El importe no es un número válido');
    previa('cantidad').textContent = cantidadOk ? formatoNumero(valorCantidad) : '—';
    previa('importe').textContent = importeOk
      ? '$ ' + valorImporte.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      : '$ —';

    // Fecha: obligatoria y no posterior a hoy (la fecha de hoy la manda el servidor).
    let fechaOk = false;
    if (fecha.value === '') {
      marcar('fecha', 'pendiente', 'Falta la fecha');
    } else if (fecha.value > hoy) {
      marcar('fecha', 'error', 'La fecha no puede ser posterior a hoy');
    } else {
      marcar('fecha', 'ok', 'Fecha válida y no posterior a hoy');
      fechaOk = true;
    }
    previa('fecha').textContent = fecha.value ? formatoFecha(fecha.value) : 'dd/mm/aaaa';

    // Repetida: solo se sabe en el servidor. Si cambiaron el servicio o la fecha, se vuelve a controlar al guardar.
    const sinCambios = idServicio === servicioLeido && fecha.value === fechaLeida;
    let repetida = false;
    if (duplicadoInicial === 'repetida' && sinCambios) {
      marcar('duplicado', 'error', 'Ya tenés una boleta de ' + nombreServicio + ' del ' + formatoFecha(fecha.value),
        'Si es otra boleta, corregí la fecha. Si es la misma, no hace falta cargarla de nuevo.');
      repetida = true;
    } else if (duplicadoInicial === 'controlado' && sinCambios) {
      marcar('duplicado', 'ok', 'No tenés otra boleta de ' + nombreServicio + ' con esa fecha', 'Controlado al leer el PDF');
    } else {
      marcar('duplicado', 'pendiente', 'Que no esté repetida', 'Se controla al guardar');
    }

    confirmar.disabled = !(radio && cantidadOk && importeOk && fechaOk) || repetida;
  }

  radios.forEach(function (r) { r.addEventListener('change', actualizar); });
  [cantidad, importe, fecha].forEach(function (campo) { campo.addEventListener('input', actualizar); });
  actualizar();
})();
