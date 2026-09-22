/* Mensajes flotantes estilo Academia JB (SweetAlert2).
   Paleta: azul marino #1a426e · azul #2196F3 · verde #4CAF50 · rojo #C96F6F.
   Estilo tranquilo, pensado para no alterar a personas con TEA:
   sin animaciones bruscas, sin sonidos, texto claro y botones de colores de la app.

   ⚠️ Cargar SIEMPRE con el CDN de SweetAlert2 y este archivo en el <head>,
   y usar msjJb(...) / msjJbConfirm(...) en lugar de alert/confirm nativos.

   Carga recomendada (antes de </head>):
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
     <script src="msj_jb.js?v=7"></script>

   Uso:
     msjJb('Texto', 'exito|error|aviso|info', 'redirigirA.php' o function)  → aviso inferior discreto
     msjJbConfirm('Pregunta', alAceptar, tipo, alCancelar)                   → confirmación centrada tranquila */
(function () {
    'use strict';
    if (typeof window.Swal === 'undefined') return;
    if (window.msjJb) return;

    var esOscuro = function () {
        var c = getComputedStyle(document.body).backgroundColor || '';
        var m = c.match(/(\d+(?:\.\d+)?)/g);
        if (!m || m.length < 3) return false;
        var r = parseFloat(m[0]);
        var g = parseFloat(m[1]);
        var b = parseFloat(m[2]);
        return (0.2126 * r + 0.7152 * g + 0.0722 * b) < 60;
    };

    var colorDe = function (tipo) {
        switch (tipo) {
            case 'exito': return '#4CAF50';
            case 'error': return '#C96F6F';
            case 'aviso': return '#2196F3';
            default: return '#2196F3';
        }
    };

    var iconoDe = function (tipo) {
        switch (tipo) {
            case 'exito': return 'success';
            case 'error': return 'error';
            case 'aviso': return 'warning';
            default: return 'info';
        }
    };

    var inyectaEstilo = function () {
        if (document.getElementById('jb-msj-estilos')) return;
        var css =
            '@keyframes jbEntrada{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}' +
            '@keyframes jbSalida{from{opacity:1;transform:none;}to{opacity:0;transform:translateY(10px);}}' +
            /* ---- Aviso inferior (toast), discreto y tranquilo ---- */
            '.swal2-toast.jb-aviso{font-family:Arial,sans-serif;border-radius:16px;padding:6px 18px;box-shadow:0 8px 26px rgba(26,66,110,.16);border:1px solid #e2e8f0;background:#ffffff !important;}' +
            '.swal2-toast.jb-aviso.swal2-show{animation:jbEntrada .22s ease !important;}' +
            '.swal2-toast.jb-aviso.swal2-hide{animation:jbSalida .14s ease forwards !important;}' +
            '.swal2-toast.jb-aviso .swal2-title{font-size:.98em !important;color:#1a3557 !important;padding:0 !important;}' +
            '.swal2-toast.jb-aviso .swal2-timer-progress-bar{background:#2196F3 !important;}' +
            /* ---- Confirmación CENTRADA (más visible y tranquila, estilo app) ---- */
            '.swal2-popup.jb-confirmar{font-family:Arial,sans-serif;border-radius:18px !important;padding:16px 18px !important;box-shadow:0 14px 44px rgba(26,66,110,.22);border:1px solid #dbe6f2;}' +
            '.swal2-popup.jb-confirmar.swal2-show{animation:jbEntrada .18s ease !important;}' +
            '.swal2-popup.jb-confirmar.swal2-hide{animation:jbSalida .12s ease forwards !important;}' +
            '.jb-confirmar .swal2-title{font-size:1.1em !important;color:#1a3557 !important;padding:0 !important;margin-bottom:10px !important;}' +
            '.jb-confirmar .swal2-html-container{color:#334155 !important;font-size:1em !important;line-height:1.65 !important;padding:0 !important;}' +
            '.jb-confirmar .swal2-confirm{border-radius:10px !important;padding:10px 28px !important;font-weight:bold !important;font-size:1em !important;box-shadow:none !important;}' +
            '.jb-confirmar .swal2-cancel{border-radius:10px !important;padding:10px 28px !important;font-weight:bold !important;font-size:1em !important;box-shadow:none !important;}' +
            '.jb-confirmar .swal2-close{color:#94a3b8 !important;}' +
            /* ---- Oscuro ---- */
            'body.jb-oscuro .swal2-toast.jb-aviso{background:#2d2d2d !important;border-color:#3a3a3a;}' +
            'body.jb-oscuro .swal2-toast.jb-aviso .swal2-title{color:#ffffff !important;}' +
            'body.jb-oscuro .swal2-popup.jb-confirmar{background:#1f1f1f !important;border-color:#3a3a3a;}' +
            'body.jb-oscuro .jb-confirmar .swal2-title{color:#ffffff !important;}' +
            'body.jb-oscuro .jb-confirmar .swal2-html-container{color:#cccccc !important;}' +
            'body.jb-oscuro .jb-confirmar .swal2-cancel{background:#3a3a3a !important;color:#dddddd !important;}';
        var hoja = document.createElement('style');
        hoja.id = 'jb-msj-estilos';
        hoja.textContent = css;
        document.head.appendChild(hoja);
    };

    inyectaEstilo();
    document.body.classList.toggle('jb-oscuro', esOscuro());

    /* Aviso discreto en la parte INFERIOR. Se cierra solo.
       Uso: msjJb('Texto', 'exito|error|aviso|info', 'redirigirA.php' o function) */
    window.msjJb = function (texto, tipo, redirigirA) {
        tipo = tipo || 'info';
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: iconoDe(tipo),
            title: texto,
            showConfirmButton: false,
            timer: 3400,
            timerProgressBar: true,
            customClass: { popup: 'jb-aviso' },
            backdrop: false
        }).then(function () {
            if (typeof redirigirA === 'function') {
                redirigirA();
            } else if (redirigirA) {
                window.location.href = redirigirA;
            }
        });
    };

    /* Confirmación CENTRADA (más visible, tranquila, con los colores de la app).
       Uso: msjJbConfirm('Texto', alAceptar, 'exito|error|aviso|info', alCancelar) */
    window.msjJbConfirm = function (texto, alAceptar, tipo, alCancelar) {
        tipo = tipo || 'aviso';
        Swal.fire({
            icon: iconoDe(tipo),
            title: '¿Estás seguro?',
            text: texto,
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: colorDe(tipo),
            cancelButtonColor: '#cbd5e1',
            customClass: { popup: 'jb-confirmar' },
            backdrop: 'rgba(26, 66, 110, 0.3)',
            width: 430
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                if (alAceptar) alAceptar();
            } else if (alCancelar) {
                alCancelar();
            }
        });
    };
})();
