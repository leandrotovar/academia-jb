<?php
session_start();
include 'conexion.php';
require_once 'pizarra_bd.php';
pizarra_crear_tablas($conn);

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$res_user = $conn->query("SELECT * FROM usuarios WHERE id = $id_usuario");
$usuario = $res_user->fetch_assoc();
$dashboard_url = ($usuario['rol'] === 'docente') ? 'profesor.php'
    : (($usuario['rol'] === 'administrador') ? 'administrador.php'
    : (($usuario['tipo_tea'] == 1) ? 'index_tea.php' : 'index.php'));

$modo_oscuro = $usuario['modo_oscuro'];
$fuente_grande = $usuario['fuente_grande'];
$pictos = $usuario['pictogramas_activos'];
$es_profesor = ($usuario['rol'] === 'docente' || $usuario['rol'] === 'administrador');

// ---- Modo sala colaborativa ----
$sala_codigo = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$sala_info = null;
$trazos_iniciales = [];
if ($sala_codigo !== '') {
    $stmt = $conn->prepare("SELECT s.id, s.codigo, s.materia, s.epoca, s.creador_id, u.nombre AS creador_nombre
                            FROM pizarra_salas s LEFT JOIN usuarios u ON u.id = s.creador_id
                            WHERE s.codigo = ? AND s.activa = 1");
    $stmt->bind_param('s', $sala_codigo);
    $stmt->execute();
    $sala_info = $stmt->get_result()->fetch_assoc();
    if ($sala_info) {
        $sala_id = (int) $sala_info['id'];
        $conn->query("UPDATE pizarra_salas SET ultima_actividad = NOW() WHERE id = $sala_id");
        $r = $conn->query("SELECT id, tipo, datos FROM pizarra_trazos WHERE sala_id = $sala_id ORDER BY id ASC LIMIT 2000");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $lineas = json_decode($row['datos'], true);
                if (!is_array($lineas)) continue;
                $lineas['__id'] = (int) $row['id'];
                $trazos_iniciales[] = $lineas;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $sala_info ? 'Sala ' . $sala_info['codigo'] . ' - ' : ''; ?>Pizarra Virtual Inclusiva - Academia JB</title>
<style>
:root{
    --fondo-lienzo:<?php echo $modo_oscuro ? '#1f1f1f' : '#ffffff'; ?>;   /* superficie de dibujo blanca */
    --fondo-app:<?php echo $modo_oscuro ? '#121212' : '#e8f0fe'; ?>;      /* fondo azul claro como el resto de pestañas */
    --fondo-toolbar:<?php echo $modo_oscuro ? '#1f1f1f' : '#e3f2fd'; ?>;  /* paneles azul suave */
    --texto:<?php echo $modo_oscuro ? '#ffffff' : '#1a3557'; ?>;
    --texto-suave:<?php echo $modo_oscuro ? '#BBBBBB' : '#334155'; ?>;
    --acento:#2196F3;               /* azul de la app */
    --acento-2:#4CAF50;             /* verde de la app */
    --borde:<?php echo $modo_oscuro ? '#333333' : '#c3d4e5'; ?>;
}
*{box-sizing:border-box;}
body{
    margin:0;
    font-family:'Arial',sans-serif;
    background:var(--fondo-app);
    color:var(--texto);
    font-size:<?php echo $fuente_grande ? '20px' : '16px'; ?>;
    min-height:100vh;
}
.barra-superior{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    background:#1a426e;
    color:#fff;
    padding:10px 18px;
    box-shadow:0 2px 6px rgba(0,0,0,.08);
}
.barra-superior .titulo{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:bold;
    font-size:1.1em;
}
.barra-superior a.atras{
    text-decoration:none;
    color:#1a426e;
    background:#ffffff;
    padding:8px 14px;
    border-radius:10px;
    font-weight:bold;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.barra-superior a.atras:hover{filter:brightness(.96);}

.barra-sala{
    display:flex;
    align-items:center;
    justify-content:center;
    flex-wrap:wrap;
    gap:10px;
    padding:10px 16px;
    background:var(--fondo-toolbar);
    border-bottom:2px solid <?php echo $modo_oscuro ? '#333' : '#d5cfc0'; ?>;
}
.info-sala{
    display:flex;
    align-items:center;
    gap:10px;
    background:var(--fondo-app);
    padding:8px 14px;
    border-radius:12px;
    font-weight:bold;
    border-left:4px solid var(--acento-2);
}
.info-sala .codigo{
    background:var(--acento);
    color:#fff;
    padding:3px 10px;
    border-radius:8px;
    letter-spacing:2px;
    font-size:1.15em;
}
.barra-sala input[type=text]{
    border:2px solid <?php echo $modo_oscuro ? '#555' : '#c3bba6'; ?>;
    border-radius:10px;
    padding:9px 12px;
    font-size:1.05em;
    text-transform:uppercase;
    letter-spacing:2px;
    width:170px;
    background:var(--fondo-app);
    color:var(--texto);
}
.aviso-sala{
    font-size:.85em;
    color:var(--texto-suave);
    line-height:1.4;
}
.boton-sala{
    border:none;
    border-radius:10px;
    padding:9px 14px;
    font-weight:bold;
    cursor:pointer;
    font-size:.95em;
}
.boton-sala.crear{background:var(--acento-2); color:#fff;}
.boton-sala.unir{background:var(--acento); color:#fff;}
.boton-sala.salir{background:#C96F6F; color:#fff;}
.chip-arrastre.disabled, .boton-plantilla.disabled{opacity:.45; pointer-events:none;}

.mini-aviso{
    font-size:.8em;
    color:var(--texto-suave);
    text-align:center;
    padding:6px 12px;
    line-height:1.4;
}

.toolbar{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
    background:var(--fondo-toolbar);
    padding:12px 16px;
    border-bottom:2px solid <?php echo $modo_oscuro ? '#333' : '#d5cfc0'; ?>;
}
.grupo-tools{
    display:flex;
    gap:6px;
    align-items:center;
    margin-right:6px;
}
.tool{
    border:none;
    background:var(--fondo-lienzo);
    color:var(--texto);
    border-radius:12px;
    padding:8px 12px;
    font-size:<?php echo $fuente_grande ? '1em' : '.95em'; ?>;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:2px solid var(--borde);
    transition:transform .12s ease, background .15s ease;
}
.tool:hover{transform:translateY(-2px);}
.tool.activo{
    background:var(--acento);
    color:#fff;
    border-color:<?php echo $modo_oscuro ? '#7FB0E8' : '#1565C0'; ?>;
}
.tool .pic{font-size:1.35em; line-height:1;}
.tool .label{font-weight:bold;}

.separador{width:1px; height:34px; background:<?php echo $modo_oscuro ? '#444' : '#c3d4e5'; ?>; margin:0 4px;}

.color-picker{display:flex; gap:6px; align-items:center;}
.swatch{
    width:30px; height:30px; border-radius:50%;
    border:3px solid transparent; cursor:pointer;
    box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.swatch.activo{border-color:<?php echo $modo_oscuro ? '#fff' : 'var(--acento)'; ?>; transform:scale(1.12);}

.rango-linea{display:flex; align-items:center; gap:8px;}
.rango-linea input{accent-color:var(--acento); cursor:pointer;}

.lienzo-wrap{
    padding:14px;
    display:flex;
    justify-content:center;
    background:<?php echo $modo_oscuro ? '#0E0E0E' : '#E3F2FD'; ?>;
}
canvas#pizarra{
    background:var(--fondo-lienzo);
    border-radius:14px;
    box-shadow:0 8px 30px rgba(0,0,0,.15);
    touch-action:none;
    cursor:crosshair;
    max-width:100%;
}

.paneles{
    display:grid;
    grid-template-columns:220px 1fr;
    gap:16px;
    padding:16px;
    max-width:1200px;
    margin:0 auto;
}
@media(max-width:760px){
    .paneles{grid-template-columns:1fr;}
}
.panel{
    background:var(--fondo-toolbar);
    border-radius:14px;
    padding:14px;
}
.panel h3{
    margin:0 0 12px 0;
    font-size:1em;
    display:flex;
    align-items:center;
    gap:8px;
}
.opciones-platilla{display:flex; flex-direction:column; gap:8px;}
.boton-plantilla{
    border:none;
    background:var(--fondo-lienzo);
    color:var(--texto);
    border-radius:12px;
    padding:10px;
    font-size:1em;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:10px;
    text-align:left;
}
.boton-plantilla:hover{background:<?php echo $modo_oscuro ? '#333' : '#fff'; ?>;}
.boton-plantilla.activo{outline:3px solid var(--acento);}

.elementos-arrastre{display:flex; flex-direction:column; gap:8px; margin-top:12px;}
.chip-arrastre{
    border:2px dashed <?php echo $modo_oscuro ? '#777' : '#8a8371'; ?>;
    background:var(--fondo-app);
    color:var(--texto);
    border-radius:12px;
    padding:10px;
    cursor:grab;
    font-size:.95em;
    display:flex;
    align-items:center;
    gap:8px;
    user-select:none;
}
.chip-arrastre.drag{border-color:var(--acento); background:<?php echo $modo_oscuro ? '#2a2a2a' : '#E3F2FD'; ?>;}
.chip-arrastre .pic{font-size:1.5em;}

.accion-clas{display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;}
.btn-accion{
    border:none;
    border-radius:10px;
    padding:8px 12px;
    cursor:pointer;
    font-size:.95em;
    font-weight:bold;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.btn-accion.limpiar{background:#C96F6F; color:#fff;}
.btn-accion.guardar{background:#5B8DD9; color:#fff;}
.btn-accion.revertir{background:<?php echo $modo_oscuro ? '#3a3a3a' : '#cfd9e5'; ?>; color:var(--texto);}

.mensaje-bienvenida{
    max-width:700px;
    margin:20px auto;
    padding:16px 18px;
    background:var(--fondo-toolbar);
    border-radius:14px;
    font-size:.92em;
    line-height:1.6;
    color:var(--texto);
}
.mensaje-bienvenida strong{color:var(--acento);}

@media (prefers-reduced-motion: reduce){
    *,*::before,*::after{transition-duration:.01ms !important; animation-duration:.01ms !important;}
}
</style>
<script src="sweetalert2.all.min.js?v=7"></script>
<script src="msj_jb.js?v=7"></script>
</head>
<body>

<div class="barra-superior">
    <div class="titulo">
        <span style="font-size:1.5em;">🎨</span>
        <span>Pizarra Virtual Inclusiva</span>
    </div>
    <a class="atras" href="<?php echo $dashboard_url; ?>">⬅ Volver</a>
</div>

<div class="barra-sala" id="barraSala">
    <?php if ($sala_info): ?>
        <div class="info-sala">
            <span>🖥️ Sala:</span>
            <span class="codigo"><?php echo htmlspecialchars($sala_codigo); ?></span>
            <span>· <?php echo htmlspecialchars(ucfirst($sala_info['materia'] ?? 'Libre')); ?></span>
            <span>· 👨‍🏫 <?php echo htmlspecialchars($sala_info['creador_nombre'] ?? 'Profesor'); ?></span>
            <span class="aviso-sala">· 👤 Usted: <?php echo htmlspecialchars($usuario['nombre']); ?></span>
        </div>
        <div id="indicadorSincronizacion" class="aviso-sala">Conectado · sincronizando…</div>
        <button class="boton-sala salir" id="btnSalir">Salir de la sala</button>
<?php else: ?>
            <input type="text" id="inputCodigo" placeholder="Código de sala" maxlength="6" autocomplete="off">
            <button class="boton-sala unir" id="btnUnirse">🔑 Unirse a sala</button>
            <div class="aviso-sala">👤 Logueado como: <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></div>
            <?php if ($es_profesor): ?>
            <button class="boton-sala crear" id="btnCrearSala">🆕 Crear sala</button>
            <div class="aviso-sala">Muestra el código a tu estudiante o compártelo para dibujar juntos en tiempo real.</div>
        <?php else: ?>
            <div class="aviso-sala">Pide el código a tu profesor e ingrésalo para entrar a la pizarra compartida.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="toolbar">
    <div class="grupo-tools">
        <button class="tool activo" id="tool-lapiz" data-tool="lapiz">
            <span class="pic">✏️</span><span class="label">Lápiz</span>
        </button>
        <button class="tool" id="tool-alta" data-tool="resaltador">
            <span class="pic">🖍️</span><span class="label">Resaltar</span>
        </button>
        <button class="tool" id="tool-borrador" data-tool="borrador">
            <span class="pic">🧽</span><span class="label">Borrar</span>
        </button>
        <button class="tool" id="tool-texto" data-tool="texto">
            <span class="pic">🔤</span><span class="label">Texto</span>
        </button>
    </div>

    <div class="separador"></div>

    <div class="grupo-tools">
        <button class="tool" id="tool-rect" data-tool="rect">
            <span class="pic">▭</span><span class="label">Cuadro</span>
        </button>
        <button class="tool" id="tool-circulo" data-tool="circulo">
            <span class="pic">◯</span><span class="label">Círculo</span>
        </button>
        <button class="tool" id="tool-flecha" data-tool="flecha">
            <span class="pic">➡️</span><span class="label">Flecha</span>
        </button>
        <button class="tool" id="tool-linea" data-tool="linea">
            <span class="pic">↗️</span><span class="label">Línea</span>
        </button>
    </div>

    <div class="separador"></div>

    <div class="color-picker" id="colorPicker"></div>

    <div class="separador"></div>

    <div class="rango-linea">
        <span>📏</span>
        <input type="range" id="grosor" min="2" max="18" value="4">
    </div>

    <div class="separador"></div>

    <div class="accion-clas">
        <button class="btn-accion revertir" id="btnDeshacer">↩️ Deshacer</button>
        <button class="btn-accion limpiar" id="btnLimpiar">🧹 Limpiar</button>
        <button class="btn-accion guardar" id="btnGuardar">💾 Guardar</button>
    </div>
</div>

<div class="paneles">
    <div class="panel">
        <h3>🖼️ Lienzos</h3>
        <div class="opciones-platilla">
            <button class="boton-plantilla activo" data-platilla="blanco"><span class="pic">⬜</span> Lienzo libre</button>
            <button class="boton-plantilla" data-platilla="matematica"><span class="pic">➗</span> Fórmulas</button>
            <button class="boton-plantilla" data-platilla="informatica"><span class="pic">💡</span> Diagrama de flujo</button>
            <button class="boton-plantilla" data-platilla="aeronautica"><span class="pic">✈️</span> Aerodinámica</button>
            <button class="boton-plantilla" data-platilla="quimica"><span class="pic">⚗️</span> Química</button>
            <button class="boton-plantilla" data-platilla="fisica"><span class="pic">📐</span> Coordenadas</button>
        </div>

        <h3 style="margin-top:16px;">🧩 Elementos</h3>
        <p class="mini-aviso">Arrastra un elemento y suéltalo sobre el lienzo.</p>
        <div class="elementos-arrastre">
            <div class="chip-arrastre" draggable="true" data-el="rect"><span class="pic">▭</span> Caja</div>
            <div class="chip-arrastre" draggable="true" data-el="circulo"><span class="pic">◯</span> Círculo</div>
            <div class="chip-arrastre" draggable="true" data-el="flecha"><span class="pic">➡️</span> Flecha</div>
            <div class="chip-arrastre" draggable="true" data-el="diamante"><span class="pic">◆</span> Decisión</div>
            <div class="chip-arrastre" draggable="true" data-el="formula"><span class="pic">ƒ</span> Fórmula</div>
            <div class="chip-arrastre" draggable="true" data-el="mano"><span class="pic">✋</span> Flecha curva</div>
        </div>
    </div>

    <div class="lienzo-wrap">
        <canvas id="pizarra"></canvas>
    </div>
</div>

<div class="mensaje-bienvenida">
    <strong>🌿 Consejos para tu aprendizaje:</strong>
    Tómate tu tiempo. Usa los colores suaves, la flecha ➡️ para seguir pasos y los elementos 🧩 para ordenar tus ideas.
    Cuando estés listo, guarda tu trabajo con 💾 y luego continua con la <strong>evaluación</strong>.<br>
    <span style="opacity:.75;">👆 Puedes dibujar con el mouse o con el dedo (pantallas táctiles).</span>
</div>

<script>
(function(){
"use strict";

var MODO_DARK = <?php echo $modo_oscuro ? 'true' : 'false'; ?>;
var FUENTE_G = <?php echo $fuente_grande ? 'true' : 'false'; ?>;
var MATERIA_INICIAL = (new URLSearchParams(window.location.search)).get('materia') || 'blanco';

var canvas = document.getElementById('pizarra');
var ctx = canvas.getContext('2d');

var DPR = Math.max(1, Math.min(window.devicePixelRatio || 1, 2));

function tamLienzo(){
    var ancho = Math.min(window.innerWidth - 60, 900);
    var alto = Math.max(480, window.innerHeight - 260);
    canvas.width = ancho * DPR;
    canvas.height = alto * DPR;
    canvas.style.width = ancho + 'px';
    canvas.style.height = alto + 'px';
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
}
tamLienzo();
window.addEventListener('resize', function(){ tamLienzo(); renderTodo(); });

/* ------------------- Paleta cromática armónica (baja saturación) ---------- */
var COLORES = [
    { h:'#4A4A4A', n:'Gris' },
    { h:'#5B8DD9', n:'Azul' },
    { h:'#6B9E5B', n:'Verde' },
    { h:'#E8A87C', n:'Naranja' },
    { h:'#9D8DC1', n:'Lila' },
    { h:'#C96F6F', n:'Rojo' },
    { h:'#4FA3A3', n:'Turquesa' }
];
var color = COLORES[0].h;

var colorPicker = document.getElementById('colorPicker');
COLORES.forEach(function(c, i){
    var s = document.createElement('button');
    s.className = 'swatch' + (i === 0 ? ' activo' : '');
    s.style.background = c.h;
    s.title = c.n;
    s.addEventListener('click', function(){
        color = c.h;
        document.querySelectorAll('.swatch').forEach(function(x){ x.classList.remove('activo'); });
        s.classList.add('activo');
        if (toolActivo === 'texto') generaBurbujaColor(c.h);
    });
    colorPicker.appendChild(s);
});

/* ------------------- Estado del lienzo ------------------- */
var SALA_CODIGO = '<?php echo $sala_codigo; ?>';
var SALA_ID = <?php echo $sala_info ? (int) $sala_info['id'] : 0; ?>;
var SALA_EPOCA = <?php echo $sala_info ? (int) $sala_info['epoca'] : 0; ?>;
var SALA_MATERIA = '<?php echo $sala_info ? $sala_info['materia'] : ''; ?>';
var USUARIO_ID = <?php echo (int) $id_usuario; ?>;

var trazos = <?php echo json_encode($trazos_iniciales); ?>;
var elementosPlantilla = [];// elementos de plantilla (fondo)
var historial = [];         // para deshacer
var plantillaActiva = (SALA_ID && SALA_MATERIA) ? SALA_MATERIA : MATERIA_INICIAL;
var toolActivo = 'lapiz';
var grosor = 4;
var dibujando = false;
var actual = null;
var ultimoIdSync = 0;

if (trazos.length) {
    trazos.forEach(function(t){ if (t.__id && t.__id > ultimoIdSync) ultimoIdSync = t.__id; });
}

/* ------------------- Modo sala collaborate ------------------- */
function esSala(){ return !!SALA_ID; }

function guardaUltimoTrazo(){
    if (!esSala()) return;
    var t = trazos[trazos.length - 1];
    if (!t || t.__id) return;
    try {
        var copia = JSON.parse(JSON.stringify(t));
        delete copia.__id;
        delete copia.__usuario;
        var body = 'accion=agregar_trazo&sala_id=' + SALA_ID + '&tipo=' + encodeURIComponent(t.tipo || '') +
                   '&datos=' + encodeURIComponent(JSON.stringify(copia));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax_pizarra.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function(){
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.ok && r.id) { t.__id = r.id; ultimoIdSync = Math.max(ultimoIdSync, r.id); }
                } catch(e){}
            }
        };
        xhr.send(body);
    } catch(e){}
}

function sincronizarSala(){
    if (!esSala()) return;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_pizarra.php?accion=sincronizar&sala_id=' + SALA_ID + '&ultimo_id=' + ultimoIdSync + '&epoca=' + SALA_EPOCA, true);
    xhr.onreadystatechange = function(){
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (!r.ok) return;
                var ind = document.getElementById('indicadorSincronizacion');
                if (ind) ind.innerText = 'Conectado · al día';
                var ruta = r.epoca !== SALA_EPOCA;   // hubo limpieza en la sala
                if (ruta) {
                    trazos = [];
                    ultimoIdSync = 0;
                    SALA_EPOCA = r.epoca;
                }
                var nuevos = 0;
                (r.trazos || []).forEach(function(tr){
                    if (!ruta && tr.id <= ultimoIdSync) return;
                    var t = tr.datos;
                    if (!t || t.tipo === 'borrador') return;
                    if (t.__id === undefined) t.__id = tr.id;
                    if (ultimoIdSync < tr.id) ultimoIdSync = tr.id;
                    trazos.push(t);
                    nuevos++;
                });
                if (nuevos || ruta) renderTodo();
            } catch(e){}
        }
    };
    xhr.send();
}

if (esSala()) {
    setInterval(sincronizarSala, 2500);

    document.querySelectorAll('.boton-plantilla').forEach(function(b){
        b.classList.add('disabled');
        if (b.getAttribute('data-platilla') === plantillaActiva) b.classList.remove('disabled');
    });
    var toolBorrador = document.getElementById('tool-borrador');
    if (toolBorrador) toolBorrador.style.display = 'none';
} else {
    var btnCrearSala = document.getElementById('btnCrearSala');
    if (btnCrearSala) btnCrearSala.addEventListener('click', function(){
        var m = (MATERIA_INICIAL && MATERIA_INICIAL !== 'blanco') ? MATERIA_INICIAL
              : (plantillaActiva === 'blanco' ? 'matematica' : plantillaActiva);
        var body = 'accion=crear_sala&materia=' + encodeURIComponent(m);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax_pizarra.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function(){
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.ok) window.location.href = 'pizarra.php?sala=' + r.codigo + '&materia=' + r.materia;
                    else msjJb(r.error || 'No se pudo crear la sala', 'error');
                } catch(e){}
            }
        };
        xhr.send(body);
    });

    var btnUnirse = document.getElementById('btnUnirse');
    if (btnUnirse) btnUnirse.addEventListener('click', function(){
        var c = (document.getElementById('inputCodigo').value || '').trim().toUpperCase();
        if (c.length < 4) { msjJb('Escribe el código de la sala', 'aviso'); return; }
        var body = 'accion=unirse_sala&codigo=' + encodeURIComponent(c);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax_pizarra.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function(){
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.ok) window.location.href = 'pizarra.php?sala=' + r.codigo + '&materia=' + r.materia;
                    else msjJb(r.error || 'No se pudo unir', 'error');
                } catch(e){}
            }
        };
        xhr.send(body);
    });
}

var btnSalir = document.getElementById('btnSalir');
if (btnSalir) btnSalir.addEventListener('click', function(){ window.location.href = '<?php echo $dashboard_url; ?>'; });

/* ------------------- Menú colores para texto ------------------- */
function generaBurbujaColor(c){
    var ex = document.getElementById('burbujaColores');
    if (ex) ex.remove();
}
document.getElementById('colorPicker').title = 'Color de trazo';

/* ------------------- Herramientas ------------------- */
var tools = document.querySelectorAll('.tool');
tools.forEach(function(t){
    t.addEventListener('click', function(){
        toolActivo = t.getAttribute('data-tool');
        tools.forEach(function(x){ x.classList.remove('activo'); });
        t.classList.add('activo');
        if (toolActivo !== 'texto') generaBurbujaColor();
        canvas.style.cursor = (toolActivo === 'texto') ? 'text' : 'crosshair';
    });
});

document.getElementById('grosor').addEventListener('input', function(){
    grosor = parseInt(this.value, 10);
});

/* ------------------- Plantillas (lienzos preconfigurados) ------------------- */
function dibujaPlantilla(nombre){
    elementosPlantilla = [];
    var w = canvas.width / DPR;
    var h = canvas.height / DPR;
    ctx.clearRect(0, 0, w, h);
    if (nombre === 'blanco') return;

    ctx.save();
    if (nombre === 'matematica' || nombre === 'fisica') {
        // cuadrícula de coordenadas
        ctx.strokeStyle = '#C9D6E3';
        ctx.lineWidth = 1;
        ctx.beginPath();
        for (var x = 40; x < w; x += 40) { ctx.moveTo(x, 0); ctx.lineTo(x, h); }
        for (var y = 40; y < h; y += 40) { ctx.moveTo(0, y); ctx.lineTo(w, y); }
        ctx.stroke();
        ctx.strokeStyle = '#B8B29F';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(0, h/2); ctx.lineTo(w, h/2);
        ctx.moveTo(w/2, 0); ctx.lineTo(w/2, h);
        ctx.stroke();
    }
    if (nombre === 'matematica') {
        ctx.strokeStyle = '#7A8EBB';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(120, 150); ctx.quadraticCurveTo(300, 240, 500, 180);
        ctx.stroke();
        dibujaTexto('f(x) = lím f(x)\n     x→a', 620, 120, '#5B8DD9', 22, false);
        dibujaTexto('x² + y² = r²', 620, 230, '#6B9E5B', 20, false);
        dibujaTexto('a·x² + b·x + c = 0', 620, 300, '#E8A87C', 20, false);
    }
    if (nombre === 'fisica') {
        dibujaTexto('Ejes de coordenadas', 30, 30, '#9D8DC1', 16, true);
    }
    if (nombre === 'informatica') {
        // plantilla de diagrama de flujo
        ctx.strokeStyle = '#5B8DD9';
        ctx.lineWidth = 3;
        rect(170, 60, 260, 60, '#5B8DD9');
        rect(170, 190, 260, 60, '#9D8DC1');
        ctx.strokeStyle = '#6B9E5B';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.ellipse(300, 330, 130, 50, 0, 0, Math.PI*2);
        ctx.stroke();
        ctx.strokeStyle = '#5B8DD9';
        ctx.beginPath();
        ctx.moveTo(300, 120); ctx.lineTo(300, 190);
        ctx.moveTo(300, 250); ctx.lineTo(300, 280);
        ctx.stroke();
        dibujaTexto('Inicio', 300, 95, '#fff', 18, true);
        dibujaTexto('Proceso', 300, 225, '#fff', 18, true);
        dibujaTexto('¿Condición?', 300, 330, '#333', 16, true);
    }
    if (nombre === 'aeronautica') {
        // perfil de ala
        ctx.strokeStyle = '#7A8EBB';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.moveTo(60, 380);
        ctx.bezierCurveTo(180, 300, 320, 300, 520, 380);
        ctx.bezierCurveTo(520, 410, 320, 340, 180, 340);
        ctx.closePath();
        ctx.fillStyle = '#D3E3F0';
        ctx.fill();
        ctx.stroke();
        // líneas de flujo
        ctx.strokeStyle = '#9D8DC1';
        ctx.lineWidth = 2;
        var flujo = [[90,270],[200,240],[330,230],[460,240]];
        for (var i = 0; i < flujo.length - 1; i++) {
            ctx.beginPath();
            ctx.moveTo(flujo[i][0], flujo[i][1]);
            ctx.quadraticCurveTo((flujo[i][0]+flujo[i+1][0])/2, flujo[i][1]-28, flujo[i+1][0], flujo[i+1][1]);
            ctx.stroke();
        }
        dibujaTexto('Flujo de aire →', 90, 250, '#9D8DC1', 16, true);
        dibujaTexto('Sustentación ↑', 200, 120, '#6B9E5B', 20, true);
        dibujaTexto('Principio de Bernoulli', 120, 470, '#5B8DD9', 16, true);
    }
    ctx.restore();
    elementosPlantilla = [];
}

function rect(x, y, w, h, borde){
    ctx.beginPath();
    ctx.rect(x, y, w, h);
    ctx.stroke();
    if (borde === '#5B8DD9'){ ctx.fillStyle = '#B7CEEF'; ctx.fill(); }
    if (borde === '#9D8DC1'){ ctx.fillStyle = '#D8D1E8'; ctx.fill(); }
}

function dibujaTexto(t, x, y, c, tam, centrado){
    ctx.save();
    ctx.fillStyle = c;
    ctx.font = 'bold ' + tam + 'px Arial';
    ctx.textAlign = centrado ? 'center' : 'left';
    ctx.textBaseline = 'middle';
    t.split('\n').forEach(function(linea, i){
        ctx.fillText(linea, x, y + (i * (tam + 6)));
    });
    ctx.restore();
}

/* ------------------- Funciones de dibujo de trazos ------------------- */
function dibujaTrazos(){
    var w = canvas.width / DPR;
    var h = canvas.height / DPR;
    ctx.clearRect(0, 0, w, h);
    dibujaPlantillaBase();
    trazos.forEach(function(t){
        ctx.save();
        ctx.strokeStyle = t.color;
        ctx.lineWidth = t.grosor;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        if (t.tipo === 'lapiz' || t.tipo === 'resaltador' || t.tipo === 'borrador') {
            ctx.globalAlpha = (t.tipo === 'borrador') ? 1 : (t.tipo === 'resaltador' ? 0.4 : 1);
            ctx.beginPath();
            t.puntos.forEach(function(p, i){
                if (i === 0) ctx.moveTo(p.x, p.y);
                else ctx.lineTo(p.x, p.y);
            });
            ctx.stroke();
            if (t.tipo === 'borrador') {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath();
                t.puntos.forEach(function(p, i){
                    if (i === 0) ctx.moveTo(p.x, p.y);
                    else ctx.lineTo(p.x, p.y);
                });
                ctx.stroke();
                ctx.globalCompositeOperation = 'source-over';
            }
            ctx.globalAlpha = 1;
        } else if (t.tipo === 'rect') {
            ctx.strokeStyle = t.color; ctx.lineWidth = t.grosor;
            ctx.beginPath(); ctx.rect(t.x1, t.y1, t.x2 - t.x1, t.y2 - t.y1); ctx.stroke();
            if (t.relleno){ ctx.globalAlpha = .15; ctx.fillStyle = t.color; ctx.fill(); ctx.globalAlpha = 1; }
        } else if (t.tipo === 'circulo') {
            ctx.beginPath(); ctx.ellipse((t.x1+t.x2)/2, (t.y1+t.y2)/2, Math.abs(t.x2-t.x1)/2, Math.abs(t.y2-t.y1)/2, 0, 0, Math.PI*2); ctx.stroke();
            if (t.relleno){ ctx.globalAlpha = .15; ctx.fill(); ctx.globalAlpha = 1; }
        } else if (t.tipo === 'linea') {
            ctx.beginPath(); ctx.moveTo(t.x1, t.y1); ctx.lineTo(t.x2, t.y2); ctx.stroke();
        } else if (t.tipo === 'flecha') {
            ctx.beginPath(); ctx.moveTo(t.x1, t.y1); ctx.lineTo(t.x2, t.y2); ctx.stroke();
            var ang = Math.atan2(t.y2 - t.y1, t.x2 - t.x1);
            var l = 16 + grosor;
            ctx.beginPath();
            ctx.moveTo(t.x2, t.y2);
            ctx.lineTo(t.x2 - l*Math.cos(ang - 0.45), t.y2 - l*Math.sin(ang - 0.45));
            ctx.lineTo(t.x2 - l*Math.cos(ang + 0.45), t.y2 - l*Math.sin(ang + 0.45));
            ctx.closePath();
            ctx.fillStyle = t.color; ctx.fill();
        } else if (t.tipo === 'texto') {
            ctx.fillStyle = t.color;
            ctx.font = 'bold ' + t.tam + 'px Arial';
            ctx.textBaseline = 'top';
            t.lineas.forEach(function(linea, i){ ctx.fillText(linea, t.x, t.y + i * (t.tam + 6)); });
        } else if (t.tipo === 'diamante') {
            ctx.beginPath();
            ctx.moveTo(t.x, t.y - t.r);
            ctx.lineTo(t.x + t.r, t.y);
            ctx.lineTo(t.x, t.y + t.r);
            ctx.lineTo(t.x - t.r, t.y);
            ctx.closePath();
            ctx.stroke();
            ctx.globalAlpha = .15; ctx.fillStyle = t.color; ctx.fill(); ctx.globalAlpha = 1;
        } else if (t.tipo === 'mano') {
            ctx.beginPath(); ctx.moveTo(t.x, t.y + 40); ctx.quadraticCurveTo(t.x + 60, t.y - 40, t.x + 120, t.y - 20); ctx.stroke();
        }
        ctx.restore();
    });
}

function dibujaPlantillaBase(){
    var nombre = plantillaActiva;
    var w = canvas.width / DPR;
    var h = canvas.height / DPR;
    if (nombre === 'blanco') {
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, w, h);
        return;
    }
    ctx.save();
    if (nombre === 'matematica' || nombre === 'fisica') {
        ctx.strokeStyle = '#C9D6E3';
        ctx.lineWidth = 1;
        ctx.beginPath();
        for (var x = 40; x < w; x += 40) { ctx.moveTo(x, 0); ctx.lineTo(x, h); }
        for (var y = 40; y < h; y += 40) { ctx.moveTo(0, y); ctx.lineTo(w, y); }
        ctx.stroke();
        ctx.strokeStyle = '#B8B29F';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(0, h/2); ctx.lineTo(w, h/2);
        ctx.moveTo(w/2, 0); ctx.lineTo(w/2, h);
        ctx.stroke();
    }
    if (nombre === 'matematica') {
        ctx.strokeStyle = '#7A8EBB';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(120, 150); ctx.quadraticCurveTo(300, 240, 500, 180);
        ctx.stroke();
        dibujaTexto('f(x)=lím f(x)', 620, 120, '#5B8DD9', 22, false);
        dibujaTexto('x² + y² = r²', 620, 230, '#6B9E5B', 20, false);
        dibujaTexto('a·x²+b·x+c=0', 620, 300, '#E8A87C', 20, false);
    }
    if (nombre === 'informatica') {
        ctx.strokeStyle = '#5B8DD9';
        ctx.lineWidth = 3;
        rect(170, 60, 260, 60, '#5B8DD9');
        rect(170, 190, 260, 60, '#9D8DC1');
        ctx.strokeStyle = '#6B9E5B';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.ellipse(300, 330, 130, 50, 0, 0, Math.PI*2);
        ctx.stroke();
        ctx.strokeStyle = '#5B8DD9';
        ctx.beginPath();
        ctx.moveTo(300, 120); ctx.lineTo(300, 190);
        ctx.moveTo(300, 250); ctx.lineTo(300, 280);
        ctx.stroke();
        dibujaTexto('Inicio', 300, 95, '#fff', 18, true);
        dibujaTexto('Proceso', 300, 225, '#fff', 18, true);
    }
    if (nombre === 'aeronautica') {
        ctx.strokeStyle = '#7A8EBB';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.moveTo(60, 380);
        ctx.bezierCurveTo(180, 300, 320, 300, 520, 380);
        ctx.bezierCurveTo(520, 410, 320, 340, 180, 340);
        ctx.closePath();
        ctx.fillStyle = '#D3E3F0';
        ctx.fill();
        ctx.stroke();
        ctx.strokeStyle = '#9D8DC1';
        ctx.lineWidth = 2;
        var flujo = [[90,270],[200,240],[330,230],[460,240]];
        for (var i = 0; i < flujo.length - 1; i++) {
            ctx.beginPath();
            ctx.moveTo(flujo[i][0], flujo[i][1]);
            ctx.quadraticCurveTo((flujo[i][0]+flujo[i+1][0])/2, flujo[i][1]-28, flujo[i+1][0], flujo[i+1][1]);
            ctx.stroke();
        }
        dibujaTexto('Flujo de aire', 90, 250, '#9D8DC1', 16, true);
        dibujaTexto('Sustentación ↑', 200, 120, '#6B9E5B', 20, true);
    }
    if (nombre === 'quimica') {
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, w, h);
        // molécula de agua H2O
        ctx.strokeStyle = '#5B8DD9';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.arc(300, 200, 45, 0, Math.PI*2);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(300, 200, 40, 0, Math.PI*2);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(180, 320, 28, 0, Math.PI*2);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(420, 320, 28, 0, Math.PI*2);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(282, 225); ctx.lineTo(196, 300);
        ctx.moveTo(318, 225); ctx.lineTo(404, 300);
        ctx.stroke();
        dibujaTexto('O', 296, 195, '#C96F6F', 22, true);
        dibujaTexto('H', 172, 315, '#6FA8D9', 18, true);
        dibujaTexto('H', 412, 315, '#6FA8D9', 18, true);
        dibujaTexto('Enlace covalente:', 600, 150, '#5B8DD9', 18, false);
        dibujaTexto('comparten electrones', 600, 180, '#6B9E5B', 16, false);
    }
    ctx.restore();
}

function renderTodo(){ dibujaTrazos(); }

/* ------------------- Eventos del ratón / táctil ------------------- */
function obtenerPunto(e){
    var r = canvas.getBoundingClientRect();
    var te = (e.touches && e.touches[0]) ? e.touches[0] : e;
    return { x: (te.clientX - r.left) * (canvas.width / DPR) / (r.width / 1), y: (te.clientY - r.top) * (canvas.height / DPR) / (r.height / 1) };
}

// Escala: el CSS puede encoger, así que corregimos por el factor real
function puntoLienzo(e){
    var r = canvas.getBoundingClientRect();
    var te = (e.touches && e.touches[0]) ? e.touches[0] : e;
    var cssX = te.clientX - r.left;
    var cssY = te.clientY - r.top;
    var wCss = canvas.width / DPR;
    var hCss = canvas.height / DPR;
    if (r.width > 0) { cssX = cssX / r.width * wCss; cssY = cssY / r.height * hCss; }
    return { x: Math.round(cssX), y: Math.round(cssY) };
}

function iniciar(e){
    e.preventDefault();
    var p = puntoLienzo(e);
    if (toolActivo === 'texto') {
        msjJbPrompt('Escribe tu texto (fórmula o nota):', 'x² + y²', function (texto) {
            if (texto.trim() !== '') {
                trazos.push({
                    tipo: 'texto', x: p.x, y: p.y, color: color,
                    tam: FUENTE_G ? 26 : 20,
                    lineas: texto.split('\n')
                });
                renderTodo();
                guardaUltimoTrazo();
            }
        });
        return;
    }
    dibujando = true;
    if (toolActivo === 'lapiz' || toolActivo === 'resaltador' || toolActivo === 'borrador') {
        actual = {
            tipo: toolActivo === 'resaltador' ? 'resaltador' : (toolActivo === 'borrador' ? 'borrador' : 'lapiz'),
            color: color, grosor: toolActivo === 'borrador' ? Math.max(grosor + 6, 14) : grosor,
            puntos: [p]
        };
        trazos.push(actual);
    } else {
        actual = { tipo: toolActivo, color: color, grosor: grosor, x1: p.x, y1: p.y, x2: p.x, y2: p.y, relleno: false };
        trazos.push(actual);
    }
    renderTodo();
}

function mover(e){
    if (!dibujando || !actual) return;
    e.preventDefault();
    var p = puntoLienzo(e);
    if (actual.puntos) {
        actual.puntos.push(p);
    } else {
        actual.x2 = p.x; actual.y2 = p.y;
    }
    renderTodo();
}

function finalizar(e){
    if (!dibujando) return;
    e.preventDefault();
    dibujando = false;
    actual = null;
    historial.push(trazos.length);
    if (historial.length > 40) historial.shift();
    guardaUltimoTrazo();
}

canvas.addEventListener('mousedown', iniciar);
canvas.addEventListener('mousemove', mover);
window.addEventListener('mouseup', finalizar);
canvas.addEventListener('touchstart', iniciar, { passive: false });
canvas.addEventListener('touchmove', mover, { passive: false });
window.addEventListener('touchend', finalizar);

/* ------------------- Botón deshacer (solo modo individual) ------------------- */
var btnDeshacer = document.getElementById('btnDeshacer');
if (esSala()) { if (btnDeshacer) btnDeshacer.disabled = true; }
else if (btnDeshacer) {
    btnDeshacer.addEventListener('click', function(){
        if (historial.length > 0) {
            var n = historial.pop();
            trazos.splice(n - 1);
            renderTodo();
        } else {
            trazos = [];
            renderTodo();
        }
    });
}

/* ------------------- Botón limpiar ------------------- */
document.getElementById('btnLimpiar').addEventListener('click', function(){
    msjJbConfirm('¿Limpiar todo el lienzo' + (esSala() ? ' para todos' : '') + '?', function(){
        trazos = [];
        historial = [];
        renderTodo();
        if (esSala()) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax_pizarra.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('accion=limpiar&sala_id=' + SALA_ID);
        }
    }, 'aviso');
});

/* ------------------- Botón guardar / descargar ------------------- */
document.getElementById('btnGuardar').addEventListener('click', function(){
    var temp = document.createElement('canvas');
    temp.width = canvas.width;
    temp.height = canvas.height;
    var tctx = temp.getContext('2d');
    tctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    dibujaPlantillaBase(); // pinta el fondo en temp
    // replicar trazos en temp
    trazos.forEach(function(t){
        tctx.save();
        tctx.strokeStyle = t.color;
        tctx.lineWidth = t.grosor;
        tctx.lineCap = 'round';
        tctx.lineJoin = 'round';
        if (t.tipo === 'lapiz' || t.tipo === 'resaltador') {
            tctx.globalAlpha = t.tipo === 'resaltador' ? 0.4 : 1;
            tctx.beginPath();
            t.puntos.forEach(function(p, i){ if (i===0) tctx.moveTo(p.x,p.y); else tctx.lineTo(p.x,p.y); });
            tctx.stroke();
            tctx.globalAlpha = 1;
        } else if (t.tipo === 'borrador') {
            tctx.globalCompositeOperation = 'destination-out';
            tctx.beginPath();
            t.puntos.forEach(function(p, i){ if (i===0) tctx.moveTo(p.x,p.y); else tctx.lineTo(p.x,p.y); });
            tctx.stroke();
        } else if (t.tipo === 'rect') {
            tctx.beginPath(); tctx.rect(t.x1,t.y1,t.x2-t.x1,t.y2-t.y1); tctx.stroke();
        } else if (t.tipo === 'circulo') {
            tctx.beginPath(); tctx.ellipse((t.x1+t.x2)/2,(t.y1+t.y2)/2,Math.abs(t.x2-t.x1)/2,Math.abs(t.y2-t.y1)/2,0,0,Math.PI*2); tctx.stroke();
        } else if (t.tipo === 'linea') {
            tctx.beginPath(); tctx.moveTo(t.x1,t.y1); tctx.lineTo(t.x2,t.y2); tctx.stroke();
        } else if (t.tipo === 'flecha') {
            tctx.beginPath(); tctx.moveTo(t.x1,t.y1); tctx.lineTo(t.x2,t.y2); tctx.stroke();
            var ang = Math.atan2(t.y2-t.y1, t.x2-t.x1); var l = 16 + t.grosor;
            tctx.beginPath(); tctx.moveTo(t.x2,t.y2);
            tctx.lineTo(t.x2-l*Math.cos(ang-0.45), t.y2-l*Math.sin(ang-0.45));
            tctx.lineTo(t.x2-l*Math.cos(ang+0.45), t.y2-l*Math.sin(ang+0.45));
            tctx.closePath(); tctx.fillStyle = t.color; tctx.fill();
        } else if (t.tipo === 'texto') {
            tctx.fillStyle = t.color;
            tctx.font = 'bold ' + t.tam + 'px Arial';
            tctx.textBaseline = 'top';
            t.lineas.forEach(function(linea, i){ tctx.fillText(linea, t.x, t.y + i*(t.tam+6)); });
        } else if (t.tipo === 'diamante') {
            tctx.beginPath(); tctx.moveTo(t.x,t.y-t.r); tctx.lineTo(t.x+t.r,t.y); tctx.lineTo(t.x,t.y+t.r); tctx.lineTo(t.x-t.r,t.y); tctx.closePath(); tctx.stroke();
        } else if (t.tipo === 'mano') {
            tctx.beginPath(); tctx.moveTo(t.x,t.y+40); tctx.quadraticCurveTo(t.x+60,t.y-40,t.x+120,t.y-20); tctx.stroke();
        }
        tctx.restore();
    });
    var enlace = document.createElement('a');
    enlace.download = 'pizarra_academia_jb.png';
    enlace.href = temp.toDataURL('image/png');
    enlace.click();
});

/* ------------------- Selección de plantilla ------------------- */
document.querySelectorAll('.boton-plantilla').forEach(function(b){
    b.addEventListener('click', function(){
        plantillaActiva = b.getAttribute('data-platilla');
        document.querySelectorAll('.boton-plantilla').forEach(function(x){ x.classList.remove('activo'); });
        b.classList.add('activo');
        renderTodo();
    });
});

/* ------------------- Arrastrar y soltar elementos ------------------- */
document.querySelectorAll('.chip-arrastre').forEach(function(chip){
    chip.addEventListener('dragstart', function(e){
        e.dataTransfer.setData('text/plain', chip.getAttribute('data-el'));
        chip.classList.add('drag');
    });
    chip.addEventListener('dragend', function(){
        chip.classList.remove('drag');
    });
});

canvas.addEventListener('dragover', function(e){ e.preventDefault(); });
canvas.addEventListener('drop', function(e){
    e.preventDefault();
    var tipo = e.dataTransfer.getData('text/plain');
    if (!tipo) return;
    var p = puntoLienzo(e);
    if (tipo === 'rect') {
        trazos.push({ tipo:'rect', x1:p.x-70, y1:p.y-35, x2:p.x+70, y2:p.y+35, color:color, grosor:grosor, relleno:true });
    } else if (tipo === 'circulo') {
        trazos.push({ tipo:'circulo', x1:p.x-50, y1:p.y-50, x2:p.x+50, y2:p.y+50, color:color, grosor:grosor, relleno:true });
    } else if (tipo === 'flecha') {
        trazos.push({ tipo:'flecha', x1:p.x-80, y1:p.y, x2:p.x+40, y2:p.y, color:color, grosor:grosor });
    } else if (tipo === 'diamante') {
        trazos.push({ tipo:'diamante', x:p.x, y:p.y, r:50, color:color, grosor:grosor });
    } else if (tipo === 'formula') {
        trazos.push({ tipo:'texto', x:p.x, y:p.y, color:'#5B8DD9', tam: FUENTE_G ? 24 : 20, lineas:['f(x) = lím f(x)','       x→a'] });
    } else if (tipo === 'mano') {
        trazos.push({ tipo:'mano', x:p.x, y:p.y, color:color, grosor:grosor });
    }
    renderTodo();
    guardaUltimoTrazo();
});

/* ------------------- Inicializar con plantilla por materia ------------------- */
if (plantillaActiva !== 'blanco') {
    document.querySelectorAll('.boton-plantilla').forEach(function(b){
        b.classList.remove('activo');
        if (b.getAttribute('data-platilla') === plantillaActiva) b.classList.add('activo');
    });
}
renderTodo();

})();
</script>
</body>
</html>