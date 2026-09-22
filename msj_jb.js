/* Mensajes flotantes estilo Academia JB (SweetAlert2)
   Paleta de la app: azul marino #1a426e, azul #2196F3, verde #4CAF50, rojo #C96F6F.
   Avisos DISCRETOS en la parte inferior de la pantalla, con cierre suave,
   para no invadir la atención (accesible para personas con TEA). */
(function () {
    'use strict';
    if (typeof window.Swal === 'undefined') return;
    if (window.msjJb) return;

    var requiereCuerpo = function (fn) {
        if (document.body) {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    };

    var inyectaEstilo = function () {
        if (document.getElementById('jb-estilos-msj')) return;
        var css =
            '@keyframes jbEntrada{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}' +
            '@keyframes jbSalida{from{opacity:1;transform:none;}to{opacity:0;transform:translateY(10px);}}' +
            '.swal2-toast.jb-popup-toast{border-radius:14px;font-family:Arial,sans-serif;padding:10px 18px;box-shadow:0 8px 26px rgba(26,66,110,.16);border:1px solid #e2e8f0;}' +
            '.swal2-toast.jb-popup-toast.swal2-show{animation:jbEntrada .25s ease !important;}' +
            '.swal2-toast.jb-popup-toast.swal2-hide{animation:jbSalida .15s ease forwards !important;}' +
            '.jb-popup-toast .jb-titulo{font-size:.95em !important;color:#334155 !important;padding:0 !important;}' +
            '.jb-popup-toast .jb-progreso{background:#2196F3 !important;}' +
            'body.jb-oscuro .swal2-toast.jb-popup-toast{background:#2d2d2d !important;border-color:#333a44;}' +
            'body.jb-oscuro .jb-popup-toast .jb-titulo{color:#ffffff !important;}';
        var hoja = document.createElement('style');
        hoja.id = 'jb-estilos-msj';
        hoja.textContent = css;
        document.head.appendChild(hoja);
    };

    var esOscuro = function () {
        var c = getComputedStyle(document.body).backgroundColor || '';
        var m = c.match(/(\d+(?:\.\d+)?)/g);
        if (!m || m.length < 3) return false;
        var r = parseFloat(m[0]), g = parseFloat(m[1]), b = parseFloat(m[2]);
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

    inyectaEstilo();
    requiereCuerpo(function () {
        document.body.classList.toggle('jb-oscuro', esOscuro());
    });

    /* Aviso discreto en la parte INFERIOR de la pantalla. Se cierra solo.
       Uso: msjJb('Texto', 'exito|error|aviso|info', 'redirigirA.php' o function) */
    window.msjJb = function (texto, tipo, redirigirA) {
        tipo = tipo || 'info';
        if (typeof window.Swal === 'undefined') return;
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: iconoDe(tipo),
            title: texto,
            showConfirmButton: false,
            timer: 3400,
            timerProgressBar: true,
            customClass: {
                popup: 'jb-popup-toast',
                title: 'jb-titulo',
                timerProgressBar: 'jb-progreso'
            },
            backdrop: false
        }).then(function () {
            if (typeof redirigirA === 'function') {
                redirigirA();
            } else if (redirigirA) {
                window.location.href = redirigirA;
            }
        });
    };

    /* Igual que msjJb pero para usos futuros de avisos rápidos. */
    window.msjJbToast = function (texto, tipo) {
        window.msjJb(texto, tipo);
    };
})();