// Etapa 18: modo claro / oscuro.
// El tema inicial lo aplica un script corto en layouts/head.php, antes de dibujar la página (así no hay parpadeo).
// Este archivo maneja el botón: alterna el atributo data-theme de <html> y guarda la elección en localStorage.
// Si el usuario nunca eligió, se sigue el tema del sistema operativo (prefers-color-scheme).

(function () {
  const raiz = document.documentElement;
  const temaDelSistema = window.matchMedia('(prefers-color-scheme: dark)');

  function temaGuardado() {
    try {
      const tema = localStorage.getItem('tema');
      return tema === 'dark' || tema === 'light' ? tema : null;
    } catch (e) {
      return null; // navegador con el almacenamiento bloqueado
    }
  }

  function aplicar(tema, avisar) {
    raiz.setAttribute('data-theme', tema);

    document.querySelectorAll('[data-tema-boton]').forEach((boton) => {
      boton.setAttribute('aria-label', tema === 'dark' ? 'Activar modo claro' : 'Activar modo oscuro');
    });

    // Avisa al resto de la página (por ejemplo, a los gráficos del dashboard).
    if (avisar) {
      document.dispatchEvent(new CustomEvent('tema-cambiado', { detail: { tema } }));
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    aplicar(raiz.getAttribute('data-theme') === 'dark' ? 'dark' : 'light', false);

    document.querySelectorAll('[data-tema-boton]').forEach((boton) => {
      boton.addEventListener('click', () => {
        const nuevo = raiz.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        try {
          localStorage.setItem('tema', nuevo);
        } catch (e) {
          // Si no se puede guardar, el cambio vale solo para esta página.
        }
        aplicar(nuevo, true);
      });
    });
  });

  // Si el usuario no eligió, acompaña los cambios del sistema operativo.
  temaDelSistema.addEventListener('change', (evento) => {
    if (temaGuardado() === null) {
      aplicar(evento.matches ? 'dark' : 'light', true);
    }
  });
})();
