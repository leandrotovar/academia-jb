<?php
session_start();
include 'conexion.php';

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

require_once 'pizarra_bd.php';
pizarra_crear_tablas($conn);

$materia_curso_php = isset($_GET['materia']) ? trim($_GET['materia']) : '';
$sala_activa = null;
if ($materia_curso_php !== '') {
    $stmt = $conn->prepare("SELECT codigo, materia, epoca, creador_id FROM pizarra_salas
                            WHERE materia = ? AND activa = 1 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $materia_curso_php);
    $stmt->execute();
    $sala_activa = $stmt->get_result()->fetch_assoc();
}
$es_profesor = ($usuario['rol'] === 'docente' || $usuario['rol'] === 'administrador') ? 1 : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aula Virtual JB - Desarrollo de Clases</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: <?php echo $usuario['modo_oscuro'] ? '#121212' : '#f0f4f8'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#ffffff' : '#1a3557'; ?>;
            font-size: <?php echo $usuario['fuente_grande'] ? '24px' : '18px'; ?>;
            transition: all 0.3s ease;
        }
        .navbar {
            background-color: #1a426e;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; }

        .header-aula {
            max-width: 1200px;
            margin: 20px auto 0;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .breadcrumb {
            font-size: 14px;
            color: <?php echo $usuario['modo_oscuro'] ? '#ccc' : '#334155'; ?>;
        }
        .breadcrumb a {
            color: #1a426e;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb .actual {
            color: <?php echo $usuario['modo_oscuro'] ? '#fff' : '#334155'; ?>;
            font-weight: bold;
        }

        .pasos {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: bold;
            color: <?php echo $usuario['modo_oscuro'] ? '#ccc' : '#334155'; ?>;
        }
        .paso {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 14px;
            border-radius: 20px;
        }
        .paso.activo {
            background: #1a426e;
            color: white;
        }
        .paso.inactivo {
            background: #e2e8f0;
            color: #334155;
        }
        .paso.completado {
            background: #4CAF50;
            color: white;
        }
        .paso-sep {
            color: #94a3b8;
        }

        .timer-box {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#fff'; ?>;
            border-radius: 10px;
            padding: 12px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #FF9800;
        }
        .timer-box .icono { font-size: 24px; }
        .timer-box .cuerpo { flex: 1; }
        .timer-box .info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: bold;
            color: <?php echo $usuario['modo_oscuro'] ? '#ccc' : '#334155'; ?>;
            margin-bottom: 5px;
        }
        .timer-box .barra {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .timer-box .barra div {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #4CAF50, #FF9800);
            border-radius: 4px;
            transition: width 1s linear;
        }
        .timer-box .nota {
            font-size: 11px;
            color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#94a3b8'; ?>;
            margin: 5px 0 0 0;
        }

        .contenedor-aula {
            max-width: 1200px;
            margin: 20px auto 40px;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .columna-clase {
            flex: 2;
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .columna-clase h2 {
            margin: 0 0 5px 0;
            color: #1a426e;
            font-weight: bold;
        }
        .columna-clase .subtitulo {
            font-size: 14px;
            color: #555;
            margin: 0 0 25px 0;
        }
        .columna-clase .subtitulo strong {
            color: #1a426e;
        }

        .video-contenedor {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            background: #000;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .video-contenedor iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .lectura h3 {
            color: #1a426e;
            margin: 0 0 10px 0;
        }
        .lectura p {
            line-height: 1.7;
            text-align: justify;
        }

        .columna-recursos {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .tarjeta-panel {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .tarjeta-panel h4 {
            margin: 0 0 10px 0;
            color: #1a426e;
        }
        .tarjeta-panel p {
            font-size: 13px;
            margin: 0 0 15px 0;
            color: <?php echo $usuario['modo_oscuro'] ? '#bbb' : '#444'; ?>;
        }

        .btn-descarga {
            display: block;
            background-color: #1a426e;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        .btn-descarga:hover { background-color: #123052; }

        .opcion-test {
            display: block;
            margin: 10px 0;
            padding: 12px;
            background: <?php echo $usuario['modo_oscuro'] ? '#2d2d2d' : '#f8fafc'; ?>;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            color: <?php echo $usuario['modo_oscuro'] ? '#ddd' : '#334155'; ?>;
        }
        .opcion-test:hover { border-color: #1a426e; background-color: <?php echo $usuario['modo_oscuro'] ? '#3d3d3d' : '#f1f5f9'; ?>; }
        .opcion-test input { margin-right: 10px; }

        .btn-evaluar {
            width: 100%;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.2);
        }
        .btn-evaluar:hover { background-color: #45a049; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000;
            display: none; justify-content: center; align-items: center;
        }
        .modal-overlay.mostrar { display: flex; }
        .modal-contenido {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : 'white'; ?>;
            padding: 35px; border-radius: 16px;
            width: 420px; max-width: 90%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative; animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-contenido h2 { margin-top: 0; color: #1a426e; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        .modal-contenido h2.dark { color: #4fc3f7; border-bottom-color: #444; }
        .modal-cerrar {
            position: absolute; top: 15px; right: 20px;
            font-size: 24px; cursor: pointer; color: #555;
            background: none; border: none; font-weight: bold;
        }
        .modal-cerrar:hover { color: #f44336; }
        .perfil-item { margin-bottom: 18px; }
        .perfil-label { font-size: 12px; color: #334155; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .perfil-label.dark { color: #aaa; }
        .perfil-valor { font-size: 18px; color: #1a3557; font-weight: bold; margin-top: 3px; }
        .perfil-valor.dark { color: #e0e0e0; }
        .perfil-badge {
            display: inline-block; background: #1a426e; color: white;
            padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: bold;
        }
        .perfil-tea { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; }
        .perfil-tea-item { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .perfil-tea-si { background: #dcfce7; color: #15803d; }
        .perfil-tea-no { background: #fee2e2; color: #b91c1c; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        @media (max-width: 768px) {
            .contenedor-aula {
                flex-direction: column;
            }
            .pasos {
                flex-wrap: wrap;
            }
        }
    </style>
    <script src="sweetalert2.all.min.js?v=7"></script>
    <script src="msj_jb.js?v=7"></script>
</head>
<body>

<nav class="navbar">
    <div style="display:flex; align-items:center; gap:15px;">
        <div style="font-weight: bold; font-size: 20px;">Aula Virtual JB 🧩</div>
    </div>
    <div>
        <span>Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></span>
        <button onclick="togglePerfilEst()" style="background:#4CAF50; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-weight:bold; margin-left:10px;">👤 Mi Perfil</button>
        <a href="logout.php" style="background: #f44336; padding: 5px 10px; border-radius: 4px; margin-left:10px;">Cerrar Sesion</a>
    </div>
</nav>

<div class="header-aula">

    <div class="breadcrumb">
        <a href="<?php echo $dashboard_url; ?>">⬅️ Volver al Inicio</a>
        <span style="color:#94a3b8;">›</span>
        <a href="registrar_cursos.php">Mis Cursos</a>
        <span style="color:#94a3b8;">›</span>
        <span class="actual" id="breadcrumbCurso">Aula Virtual</span>
    </div>

    <div class="pasos">
        <span class="paso activo" id="step1">1️⃣ Video</span>
        <span class="paso-sep">→</span>
        <span class="paso inactivo" id="step2">2️⃣ Lectura</span>
        <span class="paso-sep">→</span>
        <span class="paso inactivo" id="step3">3️⃣ Pizarra Virtual</span>
        <span class="paso-sep">→</span>
        <span class="paso inactivo" id="step4">4️⃣ Evaluación</span>
    </div>

    <?php if ($usuario['temporizador_visual'] == 1): ?>
    <div class="timer-box">
        <div class="icono">⏱️</div>
        <div class="cuerpo">
            <div class="info">
                <span id="timerLabel">Tiempo de sesión</span>
                <span id="timerDisplay">00:00</span>
            </div>
            <div class="barra">
                <div id="timerBar"></div>
            </div>
            <p class="nota">Tómate tu tiempo. No hay límite.</p>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="contenedor-aula">

    <div class="columna-clase">
        <h2 id="tituloLeccion">Cargando...</h2>
        <p class="subtitulo">Asignatura: <strong id="nombreMateria">...</strong></p>

        <div class="video-contenedor">
            <iframe id="videoClase" src="" allowfullscreen></iframe>
        </div>

        <div class="lectura">
            <h3>📖 Lectura Guiada</h3>
            <p id="textoLeccion">Cargando material didáctico...</p>
        </div>
    </div>

    <div class="columna-recursos">

        <div class="tarjeta-panel">
            <h4>📁 Guía Didáctica (PDF)</h4>
            <p>Descarga la guía de estudio en PDF para repasar sin conexión.</p>
            <a href="#" id="linkDescarga" class="btn-descarga" download>📥 Descargar Guía de Estudio</a>
        </div>

        <div class="tarjeta-panel" id="panelSala">
            <h4>🔗 Pizarra Compartida del Profesor</h4>
            <div id="estadoSala">
                <?php if ($sala_activa): ?>
                    <p>El profesor compartió una pizarra en vivo.</p>
                    <div style="text-align:center; margin:12px 0;">
                        <span style="font-size:2em; font-weight:bold; letter-spacing:4px; color:#b91c1c;"><?php echo htmlspecialchars($sala_activa['codigo']); ?></span>
                    </div>
                    <a href="pizarra.php?sala=<?php echo urlencode($sala_activa['codigo']); ?>&materia=<?php echo urlencode($materia_curso_php); ?>" class="btn-descarga">🎨 Entrar a la pizarra</a>
                <?php elseif ($es_profesor): ?>
                    <p>Si abres una sala desde la pizarra, su código aparecerá aquí para tus estudiantes inscritos.</p>
                <?php else: ?>
                    <p>Aún no hay una pizarra compartida. Pregunta a tu profesor por el código.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tarjeta-panel">
            <h4>🎨 Pizarra Virtual Inclusiva</h4>
            <p>Descompón fórmulas, arma diagramas de flujo y practica los ejercicios de la clase antes de tu evaluación.</p>
            <a href="#" id="linkPizarra" class="btn-descarga">🎨 Abrir Pizarra Virtual</a>
        </div>

        <div class="tarjeta-panel">
            <h4>📝 Evaluación del Curso</h4>
            <p>Responde las 3 preguntas. Necesitas al menos 2 correctas (60%) para aprobar.</p>
            <div id="contenedorEvaluacion"></div>
            <div id="resultadoEvaluacion" style="display:none; text-align:center; padding:20px; border-radius:8px; margin-top:15px;"></div>
            <button onclick="procesarEvaluacion()" class="btn-evaluar" id="btnEnviar">Enviar Respuestas</button>
        </div>

    </div>
</div>

<script>
const bancoLecciones = {
    matematica: {
        materia: "Matemáticas",
        titulo: "Cálculo Diferencial - Límites Matemáticos",
        video: "https://youtube.com",
        texto: "Un límite matemático describe cómo se comporta una función cuando se acerca a un valor específico. Es la base del cálculo diferencial y se usa para medir cambios.",
        preguntas: [
            { pregunta: "¿Qué formaliza conceptualmente un límite matemático?", opciones: ["La aproximación hacia un punto de una función", "La multiplicación infinita de enteros", "La raíz cuadrada de números negativos"], correcta: 0 },
            { pregunta: "¿Qué significa la derivada de una función en un punto?", opciones: ["El área bajo la curva", "La pendiente de la recta tangente", "El valor máximo de la función"], correcta: 1 },
            { pregunta: "¿Cuál es el resultado de la integral de x dx?", opciones: ["x²/2 + C", "x + C", "2x + C"], correcta: 0 }
        ]
    },
    fisica: {
        materia: "Física",
        titulo: "Cinemática - Movimiento Rectilíneo Uniforme",
        video: "https://youtube.com",
        texto: "El Movimiento Rectilíneo Uniforme (MRU) es cuando un objeto se mueve en línea recta a velocidad constante. No acelera ni frena.",
        preguntas: [
            { pregunta: "¿Cómo es la aceleración en un cuerpo que experimenta M.R.U.?", opciones: ["Es variable y exponencial", "Es completamente nula o cero", "Es igual a la fuerza de gravedad"], correcta: 1 },
            { pregunta: "¿Qué ley explica que a toda acción corresponde una reacción?", opciones: ["Primera Ley de Newton", "Segunda Ley de Newton", "Tercera Ley de Newton"], correcta: 2 },
            { pregunta: "¿Cuál es la unidad de medida de la fuerza en el SI?", opciones: ["Newton (N)", "Joule (J)", "Watt (W)"], correcta: 0 }
        ]
    },
    quimica: {
        materia: "Química",
        titulo: "Química Orgánica - Enlaces del Carbono",
        video: "https://youtube.com",
        texto: "La química orgánica estudia los compuestos de carbono. El carbono puede formar hasta 4 enlaces con otros átomos. Esto se llama tetravalencia.",
        preguntas: [
            { pregunta: "¿Cuántos enlaces covalentes puede formar un átomo de carbono?", opciones: ["Dos enlaces", "Ocho enlaces", "Cuatro enlaces"], correcta: 2 },
            { pregunta: "¿Cuál es el símbolo químico del agua?", opciones: ["CO₂", "H₂O", "NaCl"], correcta: 1 },
            { pregunta: "¿Qué tipo de enlace se forma cuando se comparten electrones?", opciones: ["Enlace iónico", "Enlace covalente", "Enlace metálico"], correcta: 1 }
        ]
    },
    aeronautica: {
        materia: "Aeronáutica",
        titulo: "Aerodinámica - Principio de Sustentación",
        video: "https://youtube.com",
        texto: "La sustentación es la fuerza que eleva un avión. El aire pasa más rápido por la parte curva del ala, creando una diferencia de presión que empuja el ala hacia arriba (Principio de Bernoulli).",
        preguntas: [
            { pregunta: "¿Qué teorema físico explica la diferencia de presiones que genera sustentación?", opciones: ["El principio de Bernoulli", "La ley de Hooke", "El teorema de Pitágoras"], correcta: 0 },
            { pregunta: "¿Qué superficies móviles en las alas permiten girar la aeronave?", opciones: ["Los alerones", "El timón de cola", "Los flaps"], correcta: 0 },
            { pregunta: "¿Cómo se llama la fuerza que se opone al avance de un avión?", opciones: ["Sustentación", "Empuje", "Arrastre o resistencia"], correcta: 2 }
        ]
    },
    informatica: {
        materia: "Informática",
        titulo: "Estructuras de Datos - Arreglos y Matrices",
        video: "https://youtube.com",
        texto: "Un arreglo es una lista de datos del mismo tipo guardados en posiciones seguidas de memoria. Cada elemento se encuentra con un número de índice (empezando desde 0).",
        preguntas: [
            { pregunta: "¿Cómo se accede de forma precisa a un elemento dentro de un arreglo?", opciones: ["Mediante un índice numérico entero", "A través de una señal física", "Utilizando un cable de red"], correcta: 0 },
            { pregunta: "¿Qué estructura de datos sigue el principio LIFO?", opciones: ["La cola (queue)", "La pila (stack)", "La lista enlazada"], correcta: 1 },
            { pregunta: "¿Qué lenguaje se usa para crear páginas web interactivas?", opciones: ["Python", "JavaScript", "C++"], correcta: 1 }
        ]
    }
};

const urlParams = new URLSearchParams(window.location.search);
const materiaClave = urlParams.get('materia') || 'matematica';

// Cargar materiales desde la base de datos
const materialesDB = <?php
$mat_res = $conn->query("SELECT clave, video_url, pdf_path, texto_leccion FROM cursos");
$mat_data = [];
while ($mr = $mat_res->fetch_assoc()) {
    $mat_data[$mr['clave']] = [
        'video' => $mr['video_url'] ?? 'https://youtube.com',
        'pdf'   => $mr['pdf_path'] ?? 'guias/Guia_Estudio_'.$mr['clave'].'.pdf',
        'texto' => $mr['texto_leccion'] ?? ''
    ];
}
echo json_encode($mat_data, JSON_UNESCAPED_UNICODE);
?>;

const cursoInscrito = localStorage.getItem("curso_bloqueado");

if (!cursoInscrito) {
    const nombreLindo = bancoLecciones[materiaClave] ? bancoLecciones[materiaClave].materia : materiaClave;
    msjJbConfirm("¿Inscribirte en " + nombreLindo + "? Solo puedes tener un curso activo a la vez.", function(){
        localStorage.setItem("curso_bloqueado", materiaClave);
        localStorage.setItem("curso_nombre", nombreLindo);
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "ajax_inscribir.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("accion=inscribir&materia=" + materiaClave);
    }, 'aviso', function(){
        window.location.href = "registrar_cursos.php";
    });
} else if (cursoInscrito !== materiaClave) {
    const nombreActivo = localStorage.getItem("curso_nombre");
    msjJb("Ya tienes un curso activo: '" + nombreActivo + "'. Termínalo primero.", 'aviso', 'registrar_cursos.php');
}

let totalPreguntas = 0;

if (bancoLecciones[materiaClave]) {
    const cursoData = bancoLecciones[materiaClave];
    const matDB = materialesDB[materiaClave] || {};
    document.getElementById("tituloLeccion").innerText = cursoData.titulo;
    document.getElementById("nombreMateria").innerText = cursoData.materia;
    var bc = document.getElementById('breadcrumbCurso');
    if (bc) bc.innerText = cursoData.titulo;
    var urlVideo = matDB.video || cursoData.video;
    // Convertir watch URL a embed URL para que funcione en el iframe
    if (urlVideo.indexOf('youtube.com/watch') !== -1) {
        var match = urlVideo.match(/v=([a-zA-Z0-9_-]+)/);
        if (match) urlVideo = 'https://www.youtube.com/embed/' + match[1];
    } else if (urlVideo.indexOf('youtu.be/') !== -1) {
        var match = urlVideo.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
        if (match) urlVideo = 'https://www.youtube.com/embed/' + match[1];
    }
    document.getElementById("videoClase").src = urlVideo;
    document.getElementById("textoLeccion").innerText = matDB.texto || cursoData.texto;

    document.getElementById("linkDescarga").setAttribute("download", "Guia_Estudio_" + materiaClave + ".pdf");
    document.getElementById("linkDescarga").setAttribute("href", matDB.pdf || "guias/Guia_Estudio_" + materiaClave + ".pdf");

    var linkPizarra = document.getElementById("linkPizarra");
    if (linkPizarra) linkPizarra.setAttribute("href", "pizarra.php?materia=" + materiaClave);

    totalPreguntas = cursoData.preguntas.length;
    const container = document.getElementById("contenedorEvaluacion");
    container.innerHTML = "";

    cursoData.preguntas.forEach(function(q, idx) {
        const qDiv = document.createElement("div");
        qDiv.style.cssText = "background:" + (<?php echo $usuario['modo_oscuro'] ? "'#2d2d2d'" : "'#f8fafc'"; ?>) + "; padding:15px; border-radius:8px; margin-bottom:15px;";
        const qLabel = document.createElement("p");
        qLabel.style.cssText = "font-weight:bold; margin:0 0 10px 0; font-size:15px; color:" + (<?php echo $usuario['modo_oscuro'] ? "'#ddd'" : "'#1a3557'"; ?>) + ";";
        qLabel.innerText = (idx + 1) + ". " + q.pregunta;
        qDiv.appendChild(qLabel);

        q.opciones.forEach(function(opc, oIdx) {
            const label = document.createElement("label");
            label.className = "opcion-test";
            const input = document.createElement("input");
            input.type = "radio";
            input.name = "q" + idx;
            input.value = oIdx;
            input.style.marginRight = "10px";
            label.appendChild(input);
            label.appendChild(document.createTextNode(" " + opc));
            qDiv.appendChild(label);
        });
        container.appendChild(qDiv);
    });
}

function procesarEvaluacion() {
    const cursoData = bancoLecciones[materiaClave];
    if (!cursoData) return;

    const total = cursoData.preguntas.length;
    let correctas = 0;

    for (let i = 0; i < total; i++) {
        const q = cursoData.preguntas[i];
        const seleccionado = document.querySelector('input[name="q' + i + '"]:checked');
        const respuestaUsuario = seleccionado ? parseInt(seleccionado.value) : -1;
        const esCorrecta = respuestaUsuario === q.correcta;
        if (esCorrecta) correctas++;

        const qDiv = document.querySelector('#contenedorEvaluacion > div:nth-child(' + (i+1) + ')');
        if (qDiv) {
            qDiv.style.borderLeft = '5px solid ' + (esCorrecta ? '#4CAF50' : '#f44336');
            qDiv.style.borderRadius = '8px';
        }

        const labels = document.querySelectorAll('input[name="q' + i + '"]');
        labels.forEach(function(input, j) {
            const label = input.parentElement;
            const esSeleccionada = j === respuestaUsuario;
            const esCorrectaOpcion = j === q.correcta;

            if (esCorrectaOpcion) {
                label.style.background = '#dcfce7';
                label.style.color = '#15803d';
                label.style.fontWeight = 'bold';
                label.style.border = '2px solid #4CAF50';
            } else if (esSeleccionada && !esCorrecta) {
                label.style.background = '#fee2e2';
                label.style.color = '#b91c1c';
                label.style.fontWeight = 'bold';
                label.style.border = '2px solid #f44336';
            } else {
                label.style.opacity = '0.5';
            }
            input.disabled = true;
        });
    }

    const porcentaje = Math.round((correctas / total) * 100);
    const aprobado = porcentaje >= 60;

    const resultadoDiv = document.getElementById("resultadoEvaluacion");
    resultadoDiv.style.display = "block";

    if (aprobado) {
        var s4 = document.getElementById('step4');
        if (s4) { s4.className = 'paso completado'; s4.innerText = '✅ Completado'; }
        localStorage.removeItem("curso_bloqueado");
        localStorage.removeItem("curso_nombre");

        var notaTexto = "Evaluación: " + correctas + "/" + total + " (" + porcentaje + "%) - Aprobado";
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "ajax_inscribir.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("accion=desinscribir&materia=" + materiaClave + "&notas=" + encodeURIComponent(notaTexto));

        resultadoDiv.innerHTML = '<div style="background:#dcfce7; color:#15803d; padding:20px; border-radius:8px;">' +
            '<div style="font-size:40px; margin-bottom:10px;">🎉</div>' +
            '<div style="font-size:22px; font-weight:bold; margin-bottom:5px;">¡APROBADO!</div>' +
            '<div style="font-size:16px;">' + correctas + '/' + total + ' correctas (' + porcentaje + '%)</div>' +
            '<div style="font-size:14px; margin-top:10px;">Curso completado. Ya puedes elegir otro curso.</div>' +
            '<a href="<?php echo $dashboard_url; ?>" style="display:inline-block; margin-top:15px; padding:10px 25px; background:#15803d; color:white; border-radius:8px; font-size:16px; font-weight:bold; text-decoration:none;">Volver al inicio</a>' +
            '</div>';
        document.getElementById("btnEnviar").style.display = "none";
    } else {
        resultadoDiv.innerHTML = '<div style="background:#fee2e2; color:#b91c1c; padding:20px; border-radius:8px;">' +
            '<div style="font-size:40px; margin-bottom:10px;">❌</div>' +
            '<div style="font-size:22px; font-weight:bold; margin-bottom:5px;">NO APROBADO</div>' +
            '<div style="font-size:16px;">' + correctas + '/' + total + ' correctas (' + porcentaje + '%)</div>' +
            '<div style="font-size:14px; margin-top:10px;">Necesitas al menos 60% (2 de 3). Repasa donde te equivocaste e intenta de nuevo.</div>' +
            '<button onclick="document.getElementById(\'resultadoEvaluacion\').style.display=\'none\';" style="margin-top:15px; padding:10px 25px; background:#b91c1c; color:white; border:none; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer;">Volver a intentar</button>' +
            '</div>';
    }
}

let timerSeconds = 0;
let timerInterval = null;
const timerDisplay = document.getElementById('timerDisplay');
const timerBar = document.getElementById('timerBar');
if (timerDisplay && timerBar) {
    timerInterval = setInterval(function() {
        timerSeconds++;
        var mins = Math.floor(timerSeconds / 60);
        var secs = timerSeconds % 60;
        timerDisplay.innerText =
            (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        var pct = Math.min(timerSeconds / 6, 100);
        timerBar.style.width = pct + '%';
        if (pct >= 80) {
            timerBar.style.background = 'linear-gradient(90deg, #FF9800, #f44336)';
        }
    }, 1000);
}

var stepTimer = setTimeout(function() {
    var s2 = document.getElementById('step2');
    if (s2) { s2.className = 'paso activo'; }
}, 30000);

var pizarraTimer = setTimeout(function() {
    var s3 = document.getElementById('step3');
    if (s3) { s3.className = 'paso activo'; }
}, 60000);

/* ------------------- Panel sala compartida en vivo ------------------- */
var estadoSalaData = {
    sala: <?php echo json_encode($sala_activa); ?>,
    esProfesor: <?php echo $es_profesor; ?>,
    materia: <?php echo json_encode($materia_curso_php); ?>
};
function actualizarPanelSala(){
    var panel = document.getElementById('panelSala');
    var estado = document.getElementById('estadoSala');
    if (!panel || !estado) return;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_pizarra.php?accion=sala_curso&materia=' + encodeURIComponent(estadoSalaData.materia), true);
    xhr.onreadystatechange = function(){
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var r = JSON.parse(xhr.responseText);
                if (!r.ok) return;
                var s = r.sala;
                var html;
                if (s) {
                    html = '<p>El profesor compartió una pizarra en vivo.</p>' +
                           '<div style="text-align:center; margin:12px 0;">' +
                           '<span style="font-size:2em; font-weight:bold; letter-spacing:4px; color:#b91c1c;">' + s.codigo + '</span></div>' +
                           '<a href="pizarra.php?sala=' + encodeURIComponent(s.codigo) + '&materia=' + encodeURIComponent(estadoSalaData.materia) + '" class="btn-descarga">🎨 Entrar a la pizarra</a>';
                } else if (estadoSalaData.esProfesor) {
                    html = '<p>Si abres una sala desde la pizarra, su código aparecerá aquí para tus estudiantes inscritos.</p>';
                } else {
                    html = '<p>Aún no hay una pizarra compartida. Pregunta a tu profesor por el código.</p>';
                }
                if (estado.innerHTML !== html) estado.innerHTML = html;
            } catch(e){}
        }
    };
    xhr.send();
}
if (estadoSalaData.materia) setInterval(actualizarPanelSala, 4000);
</script>

<div class="modal-overlay" id="modalPerfilEst">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="togglePerfilEst()">&times;</button>
        <h2 class="<?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">👤 Mi Perfil</h2>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Nombre Completo</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Cédula</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo htmlspecialchars($usuario['cedula'] ?? ''); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Correo Electrónico</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo htmlspecialchars($usuario['email']); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Rol</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">
                <span class="perfil-badge">Estudiante</span>
            </div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Miembro desde</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Preferencias TEA</div>
            <div class="perfil-tea">
                <span class="perfil-tea-item <?php echo $usuario['fuente_grande'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Letra Grande: <?php echo $usuario['fuente_grande'] ? 'SÍ' : 'NO'; ?></span>
                <span class="perfil-tea-item <?php echo $usuario['pictogramas_activos'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Pictogramas: <?php echo $usuario['pictogramas_activos'] ? 'SÍ' : 'NO'; ?></span>
                <span class="perfil-tea-item <?php echo $usuario['modo_oscuro'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Modo Oscuro: <?php echo $usuario['modo_oscuro'] ? 'SÍ' : 'NO'; ?></span>
                <span class="perfil-tea-item <?php echo $usuario['temporizador_visual'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Temporizador: <?php echo $usuario['temporizador_visual'] ? 'SÍ' : 'NO'; ?></span>
            </div>
        </div>
    </div>
</div>

<script>
function togglePerfilEst() {
    document.getElementById('modalPerfilEst').classList.toggle('mostrar');
}
document.getElementById('modalPerfilEst').addEventListener('click', function(e) {
    if (e.target === this) togglePerfilEst();
});
</script>

<?php include 'asistente.php'; ?>
</body>
</html>
