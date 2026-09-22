/* Mensajes flotantes estilo Academia JB (SweetAlert2)
   Paleta de la app: azul marino #1a426e, azul #2196F3, verde #4CAF50, rojo #C96F6F.
   Diseño tranquilo y sin brusquedad para no alterar a personas con TEA. */
(function () {
    'use strict';
    if (typeof window.Swal === 'undefined') return;
    if (window.msjJb) return;

    var inyectaEstilo = function () {
        if (document.getElementById('jb-estilos-msj')) return;
        var css =
            '@keyframes jbEntrada{from{opacity:0;transform:translateY(-10px) scale(.98);}to{opacity:1;transform:none;}}' +
            '@keyframes jbSalida{from{opacity:1;transform:none;}to{opacity:0;transform:translateY(-8px) scale(.99);}}' +
            '.swal2-popup.jb-popup{border-radius:18px;font-family:Arial,sans-serif;padding:16px 18px;box-shadow:0 14px 44px rgba(26,66,110,.22);border:1px solid #dbe6f2;}' +
            '.swal2-popup.jb-popup.swal2-show{animation:jbEntrada .18s ease !important;}' +
            '.swal2-popup.jb-popup.swal2-hide{animation:jbSalida .12s ease forwards !important;}' +
            '.jb-popup .jb-titulo{color:#1a426e !important;font-size:1.15em !important;padding-top:4px !important;}' +
            '.jb-popup .jb-contenido{color:#334155 !important;font-size:1em !important;line-height:1.55 !important;}' +
            '.jb-popup .jb-boton{border-radius:10px !important;padding:10px 28px !important;font-weight:bold !important;font-size:1em !important;box-shadow:none !important;}' +
            '.jb-popup .swal2-close{color:#94a3b8 !important;}' +
            '.swal2-toast.jb-popup-toast{border-radius:16px;font-family:Arial,sans-serif;padding:10px 18px;box-shadow:0 8px 26px rgba(26,66,110,.16);}' +
            '.swal2-toast.jb-popup-toast.swal2-show{animation:jbEntrada .18s ease !important;}' +
            '.swal2-toast.jb-popup-toast.swal2-hide{animation:jbSalida .12s ease forwards !important;}' +
            '.jb-popup-toast .jb-titulo{font-size:.95em !important;color:#1a3557 !important;padding:0 !important;}' +
            '.jb-popup-toast .jb-progreso{background:#2196F3 !important;}' +
            'body.jb-oscuro .swal2-popup.jb-popup{background:#1f1f1f !important;border-color:#333a44;}' +
            'body.jb-oscuro .jb-popup .jb-titulo{color:#ffffff !important;}' +
            'body.jb-oscuro .jb-popup .jb-contenido{color:#cccccc !important;}' +
            'body.jb-oscuro .swal2-toast.jb-popup-toast{background:#2d2d2d !important;}' +
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
    document.body.classList.toggle('jb-oscuro', esOscuro());

    /* Mensaje modal tranquilo: texto claro, un solo botón, cierre suave.
       Uso: msjJb('Texto', 'exito|error|aviso|info', 'redirigirA.php') */
    window.msjJb = function (texto, tipo, redirigirA, titulo) {
        tipo = tipo || 'info';
        Swal.fire({
            icon: iconoDe(tipo),
            title: titulo || 'Academia JB',
            text: texto,
            confirmButtonText: 'Entendido',
            confirmButtonColor: colorDe(tipo),
            customClass: {
                popup: 'jb-popup',
                title: 'jb-titulo',
                htmlContainer: 'jb-contenido',
                confirmButton: 'jb-boton'
            },
            backdrop: 'rgba(26, 66, 110, 0.28)',
            width: 420
        }).then(function () {
            if (typeof redirigirA === 'function') {
                redirigirA();
            } else if (redirigirA) {
                window.location.href = redirigirA;
            }
        });
    };

    /* Toast pequeño y suave que se cierra solo.
       Uso: msjJbToast('Texto', 'exito|error|aviso|info') */
    window.msjJbToast = function (texto, tipo) {
        tipo = tipo || 'info';
        Swal.fire({
            toast: true,
            position: 'top-end',
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
        });
    };
})();