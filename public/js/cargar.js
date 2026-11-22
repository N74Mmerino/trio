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
  const MARCAS = { ok: '✓', error: '!', pendiente: '·', aviso: '!' };

  // Etapa 23: cuotas (opcionales). Mismas reglas que ConsumoController::validarVencimientos()
  // y mismos estados que app/servicios/Vencimientos.php.
  const umbral = parseInt(form.dataset.umbral, 10);
  const filasCuota = Array.from(form.querySelectorAll('[data-cuota]'));
  const fila2 = form.querySelector('[data-cuota="2"]');
  const agregarCuota = form.querySelector('[data-agregar-cuota]');
  const quitarCuota = form.querySelector('[data-quitar-cuota]');

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

  function pesos(valor) {
    return '$ ' + valor.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Etapa 23: lo escrito en una fila de cuota. importe: null si está vacío, NaN si no es un número.
  function leerCuota(fila) {
    const fechaCuota = fila.querySelector('input[type="date"]').value;
    const importeCuota = numero(fila.querySelector('input[inputmode="decimal"]').value);
    return { fecha: fechaCuota, importe: importeCuota, empezada: fechaCuota !== '' || importeCuota !== null };
  }

  // Días desde hoy (fecha del servidor) hasta la fecha. Se usan medianoches UTC para no depender del horario.
  function diasHasta(iso) {
    return Math.round((Date.parse(iso + 'T00:00:00Z') - Date.parse(hoy + 'T00:00:00Z')) / 86400000);
  }

  function estadoCuota(iso) {
    const dias = diasHasta(iso);

    if (dias < 0) {
      return { estado: 'pasado', texto: 'Venció el ' + formatoFecha(iso) };
    }
    if (dias <= umbral) {
      return { estado: 'proximo', texto: dias === 0 ? 'Vence hoy' : (dias === 1 ? 'Vence mañana' : 'Vence en ' + dias + ' días') };
    }
    return { estado: 'futuro', texto: 'Vence el ' + formatoFecha(iso) };
  }

  // Devuelve el primer problema de las cuotas (o null). Mismo orden que validarVencimientos().
  function errorDeCuotas(cuotas) {
    for (let i = 0; i < cuotas.length; i++) {
      const c = cuotas[i];
      const n = i + 1;

      if (!c.empezada) {
        continue;
      }
      if (i > 0 && !cuotas[i - 1].empezada) {
        return 'Completá primero la cuota ' + i;
      }
      if (c.fecha === '') {
        return 'Falta la fecha de vencimiento de la cuota ' + n;
      }
      if (c.importe === null) {
        return 'Falta el importe de la cuota ' + n;
      }
      if (isNaN(c.importe) || c.importe > 9999999999.99) {
        return 'El importe de la cuota ' + n + ' no es un número válido';
      }
      if (fecha.value !== '' && c.fecha < fecha.value) {
        return 'El vencimiento de la cuota ' + n + ' es anterior a la fecha de la boleta';
      }
      if (i > 0 && c.fecha <= cuotas[i - 1].fecha) {
        return 'La cuota ' + n + ' tiene que vencer después de la cuota ' + i;
      }
    }
    return null;
  }

  function actualizarCuotas(importeOk, valorImporte) {
    const cuotas = filasCuota.map(leerCuota);
    const empezadas = cuotas.filter(function (c) { return c.empezada; });
    const error = errorDeCuotas(cuotas);

    if (error !== null) {
      marcar('vencimientos', 'error', error);
    } else if (empezadas.length === 0) {
      marcar('vencimientos', 'pendiente', 'Sin vencimientos', 'Son opcionales');
    } else {
      marcar('vencimientos', 'ok', empezadas.length === 1 ? 'Vencimiento válido' : 'Vencimientos válidos y en orden');
    }

    // Suma de las cuotas: solo un aviso, no bloquea el guardado.
    const itemSuma = form.querySelector('[data-check="suma"]');
    const calculable = error === null && empezadas.length > 0 && importeOk;
    itemSuma.hidden = !calculable;
    if (calculable) {
      const suma = empezadas.reduce(function (total, c) { return total + c.importe; }, 0);
      if (Math.abs(suma - valorImporte) < 0.005) {
        marcar('suma', 'ok', 'Las cuotas suman el importe total');
      } else {
        marcar('suma', 'aviso', 'Las cuotas suman ' + pesos(suma) + ' y el total es ' + pesos(valorImporte),
          'Revisá los importes con la boleta. Si coinciden con el papel, podés guardar igual.');
      }
    }

    // Vista previa: la primera cuota que todavía no venció (si vencieron todas, la última).
    const chip = previa('vencimiento');
    if (error !== null || empezadas.length === 0) {
      chip.hidden = true;
    } else {
      const proxima = empezadas.find(function (c) { return diasHasta(c.fecha) >= 0; }) || empezadas[empezadas.length - 1];
      const estado = estadoCuota(proxima.fecha);
      chip.className = 'due-chip is-' + estado.estado;
      chip.textContent = estado.texto;
      chip.hidden = false;
    }

    return error === null;
  }

  // Cuota 2: sin JavaScript se ven las dos filas. Con JavaScript, si está vacía se esconde
  // detrás del botón "Agregar 2ª cuota"; "Quitar" la vacía y la vuelve a esconder.
  function mostrarCuota2(mostrar) {
    fila2.hidden = !mostrar;
    agregarCuota.hidden = mostrar;
    quitarCuota.hidden = !mostrar;
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

    const cuotasOk = actualizarCuotas(importeOk, valorImporte);

    confirmar.disabled = !(radio && cantidadOk && importeOk && fechaOk && cuotasOk) || repetida;
  }

  radios.forEach(function (r) { r.addEventListener('change', actualizar); });
  [cantidad, importe, fecha].forEach(function (campo) { campo.addEventListener('input', actualizar); });
  form.querySelectorAll('[data-cuota] input').forEach(function (campo) { campo.addEventListener('input', actualizar); });

  if (fila2 && agregarCuota && quitarCuota) {
    mostrarCuota2(leerCuota(fila2).empezada || fila2.querySelector('.field-error') !== null);

    agregarCuota.addEventListener('click', function () {
      mostrarCuota2(true);
      fila2.querySelector('input').focus();
    });
    quitarCuota.addEventListener('click', function () {
      fila2.querySelectorAll('input').forEach(function (campo) { campo.value = ''; });
      mostrarCuota2(false);
      agregarCuota.focus();
      actualizar();
    });
  }

  actualizar();
})();
