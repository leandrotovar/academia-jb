<?php
// index.php - PARTE 1
session_start();
include 'conexion.php';

// Validar inicio de sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_publico.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id']; 
$res_user = $conn->query("SELECT * FROM usuarios WHERE id = $id_usuario");
$usuario = $res_user->fetch_assoc();

// Consultar historial de cursos y notas del profesor
$materias_map = [
    'matematica' => 'Matemáticas',
    'fisica' => 'Física',
    'quimica' => 'Química',
    'aeronautica' => 'Aeronáutica',
    'informatica' => 'Informática'
];
$res_historial = $conn->query("
    SELECT i.materia, i.estado, i.fecha_inscripcion, i.notas
    FROM inscripciones i
    WHERE i.usuario_id = $id_usuario
    ORDER BY i.fecha_inscripcion DESC
");

// Manejo de ajustes rápidos dinámicos en la URL (Herramientas TEA)
if (isset($_GET['cambiar_ajuste'])) {
    $ajuste = $_GET['cambiar_ajuste'];
    if ($ajuste === 'fuente_grande' || $ajuste === 'pictogramas_activos' || $ajuste === 'modo_oscuro' || $ajuste === 'temporizador_visual') {
        $valor_actual = $usuario[$ajuste];
        $nuevo_valor = $valor_actual == 1 ? 0 : 1;
        $conn->query("UPDATE usuarios SET $ajuste = $nuevo_valor WHERE id = $id_usuario");
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formación Académica JB - Inicio</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: <?php echo $usuario['modo_oscuro'] ? '#121212' : '#f0f4f8'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#ffffff' : '#1a1a1a'; ?>;
            font-size: <?php echo $usuario['fuente_grande'] ? '24px' : '18px'; ?>;
            transition: all 0.3s ease;
        }

        .navbar {
            background-color: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#1a426e'; ?>; /* Azul cálido JB unificado */
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }
        
        .contenedor { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        
        .bienvenida { 
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
        }
        
        /* Nuevo diseño del Asistente integrado como bloque de tarjeta */
        .asistente-integrado-bloque {
            background: #1a426e; /* Naranja llamativo de tu marca */
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .asistente-integrado-bloque:hover {
            transform: translateY(-5px);
            background-color: #1a426e;
        }

        /* Grid de accesos directos principales (Modos Simétricos) */
        .grid-modulos { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
            margin-top: 10px;
        }
        
        .tarjeta {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 3px solid transparent;
            transition: transform 0.2s, border-color 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
        }
        .tarjeta:hover { transform: translateY(-5px); border-color: #2196F3; }

        /* Modales y burbujas adaptadas para abrir en el lateral derecho de forma elegante */
.ventana-asistente-nueva {
    position: fixed;
    bottom: 30px; /* Separación del borde inferior de la pantalla */
    right: 30px;  /* Separación del borde derecho de la pantalla */
    width: 360px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    display: none; /* Se activa dinámicamente */
    overflow: hidden;
    border: 3px solid #1a426e;
    z-index: 2000;
    animation: slideInRight 0.3s ease forwards; /* Animación de entrada lateral */
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

        /* Fondo oscuro detrás del chat para evitar distracciones visuales */
        .pantalla-oscura-asistente {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 1999;
            display: none;
        }

        /* Barra de ajustes rápidos */
        .ajustes-rapidos { 
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#e3f2fd'; ?>; 
            padding: 20px; 
            border-radius: 12px; 
            margin-top: 40px; 
        }
        
        .btn-ajuste { 
            display: inline-block; 
            padding: 10px 15px; 
            margin: 5px; 
            border-radius: 20px; 
            border: none; 
            background: #1a426e; 
            color: white; 
            text-decoration: none; 
            font-size: 14px; 
            cursor: pointer; 
            font-weight: bold; 
        }
        .btn-ajuste.activo { background: #4CAF50; }
        
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
        .perfil-tea {
            display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px;
        }
        .perfil-tea-item {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 12px; font-weight: bold;
        }
        .perfil-tea-si { background: #dcfce7; color: #15803d; }
        .perfil-tea-no { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<!-- Barra de Navegación Superior -->
<nav class="navbar">
    <div style="font-weight: bold; font-size: 20px;">Formación Académica JB 🧩</div>
    <div>
        <span>Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></span>
        <button onclick="togglePerfilEst()" style="background:#4CAF50; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-weight:bold; margin-left:10px;">👤 Mi Perfil</button>
        <a href="logout.php" style="background: #f44336; padding: 5px 10px; border-radius: 4px; margin-left:10px;">Cerrar Sesion</a>
    </div>
</nav>
<div class="contenedor">
    
    <!-- Bloque de Bienvenida y Asistente en Dos Columnas Simétricas -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Columna Izquierda: Mensaje de Bienvenida -->
        <div class="bienvenida" style="display: flex; flex-direction: column; justify-content: center;">
            <h2 style="margin-top: 0; color: #1a426e;">¡Qué bueno verte hoy! 👋</h2>
            <p style="margin-bottom: 0;">Selecciona una opción aquí abajo para empezar a aprender de forma divertida.</p>
        </div>

        <!-- Columna Derecha: El nuevo bloque integrado del Asistente Virtual -->
        <div class="asistente-integrado-bloque" onclick="alternarAsistente()">
            <div style="font-size: 40px; margin-bottom: 5px;">🤖</div>
            <h3 style="margin: 0; font-size: 18px;">Asistente Virtual</h3>
            <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.9;">¿Tienes dudas? Haz clic aquí</p>
        </div>

    </div>

     <!-- Opciones de Navegación del Estudiante en Cuadrícula Simétrica -->
    <div class="grid-modulos">
        
        <!-- Tarjeta 1: Mis Cursos (Redirige limpio a la página independiente estilo cronograma) -->
        <a href="registrar_cursos.php" class="tarjeta">
            <?php if ($usuario['pictogramas_activos'] == 1): ?>
                <div style="font-size: 50px; margin-bottom: 10px;">📖</div>
            <?php endif; ?>
            <h3>Mis Cursos</h3>
            <p>Entra aquí para ver tus lecciones disponibles hoy.</p>
        </a>

        <!-- Tarjeta Pizarra Virtual Inclusiva -->
        <a href="pizarra.php" class="tarjeta">
            <?php if ($usuario['pictogramas_activos'] == 1): ?>
                <div style="font-size: 50px; margin-bottom: 10px;">🎨</div>
            <?php endif; ?>
            <h3>Pizarra Virtual</h3>
            <p>Practica fórmulas, diagramas y ejercicios con nuestro lienzo inclusivo.</p>
        </a>

        <!-- Tarjeta 2: Mi Progreso -->
        <a href="progreso.php" class="tarjeta">
            <?php if ($usuario['pictogramas_activos'] == 1): ?>
                <div style="font-size: 50px; margin-bottom: 10px;">🏆</div>
            <?php endif; ?>
            <h3>Mi Progreso</h3>
            <p>Revisa tus cursos completados y calificaciones.</p>
        </a>

        <!-- Tarjeta 3: Calendario Académico -->
        <a href="calendario.php" class="tarjeta">
            <?php if ($usuario['pictogramas_activos'] == 1): ?>
                <div style="font-size: 50px; margin-bottom: 10px;">📅</div>
            <?php endif; ?>
            <h3>Calendario Académico</h3>
            <p>Mira las fechas de inscripción, inicios de clases y entregas.</p>
        </a>

        <!-- Tarjeta 4: Glosario de Términos -->
        <a href="glosario.php" class="tarjeta">
            <?php if ($usuario['pictogramas_activos'] == 1): ?>
                <div style="font-size: 50px; margin-bottom: 10px;">📖</div>
            <?php endif; ?>
            <h3>Glosario de Términos</h3>
            <p>Consulta conceptos clave y palabras técnicas de tus asignaturas.</p>
        </a>
    </div>

    <!-- Historial de Cursos y Notas del Profesor -->
    <div class="historial-notas" style="background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-top: 30px;">
        <h4 style="margin: 0 0 5px 0; color: <?php echo $usuario['modo_oscuro'] ? '#4fc3f7' : '#1a426e'; ?>;">📋 Mi Historial de Cursos</h4>
        <p style="margin: 0 0 15px 0; font-size: 14px; color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#444'; ?>;">Aquí puedes ver los cursos en los que te has inscrito y las observaciones de tu profesor.</p>

        <?php if ($res_historial && $res_historial->num_rows > 0): ?>
            <?php while($h = $res_historial->fetch_assoc()): 
                $materia_nom = $materias_map[$h['materia']] ?? $h['materia'];
                $es_completado = $h['estado'] === 'completado';
                $badge_color = $es_completado ? '#15803d' : '#1d4ed8';
                $badge_bg = $es_completado ? '#dcfce7' : '#dbeafe';
                $badge_text = $es_completado ? '✅ Completado' : '📌 Activo';
            ?>
                <div style="background: <?php echo $usuario['modo_oscuro'] ? '#2d2d2d' : '#f8fafc'; ?>; border-left: 5px solid #1a426e; padding: 15px; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <strong style="font-size: 16px; color: <?php echo $usuario['modo_oscuro'] ? '#4fc3f7' : '#1a426e'; ?>;"><?php echo htmlspecialchars($materia_nom); ?></strong>
                            <span style="display: inline-block; margin-left: 10px; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;"><?php echo $badge_text; ?></span>
                        </div>
                        <div style="font-size: 13px; color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#334155'; ?>;">
                            📅 <?php echo date('d/m/Y', strtotime($h['fecha_inscripcion'])); ?>
                        </div>
                    </div>
                    <?php if ($h['notas'] && trim($h['notas']) !== ''): ?>
                        <div style="margin-top: 10px; padding: 10px; background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#fff'; ?>; border-radius: 6px; border: 1px solid <?php echo $usuario['modo_oscuro'] ? '#444' : '#e2e8f0'; ?>;">
                            <span style="font-size: 12px; font-weight: bold; color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#334155'; ?>;">📝 Nota del profesor:</span>
                            <p style="margin: 5px 0 0 0; font-size: 14px; color: <?php echo $usuario['modo_oscuro'] ? '#ddd' : '#334155'; ?>;"><?php echo nl2br(htmlspecialchars($h['notas'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: <?php echo $usuario['modo_oscuro'] ? '#555' : '#334155'; ?>; background: <?php echo $usuario['modo_oscuro'] ? '#2d2d2d' : '#f8fafc'; ?>; border-radius: 8px; font-size: 15px;">
                🎯 Aún no te has inscrito en ningún curso. ¡Ve a <strong>Mis Cursos</strong> para empezar!
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel de Control de Estímulos Opcional (Herramientas TEA) -->
    <div class="ajustes-rapidos" id="panelTEA" style="<?php echo $usuario['tipo_tea'] == 0 ? 'display:none;' : ''; ?>">
        <h4>🛠️ Ajustes Visuales Rápidos (Herramientas TEA)</h4>
        <p style="font-size: 14px; margin-bottom: 15px;">Haz clic en los botones para adaptar la pantalla a tu gusto:</p>
        
        <a href="index.php?cambiar_ajuste=fuente_grande" class="btn-ajuste <?php echo $usuario['fuente_grande'] ? 'activo' : ''; ?>">
            Texto Grande 🔍 (<?php echo $usuario['fuente_grande'] ? 'SÍ' : 'NO'; ?>)
        </a>
        <a href="index.php?cambiar_ajuste=pictogramas_activos" class="btn-ajuste <?php echo $usuario['pictogramas_activos'] ? 'activo' : ''; ?>">
            Dibujos 🖼️ (Pictogramas: <?php echo $usuario['pictogramas_activos'] ? 'SÍ' : 'NO'; ?>)
        </a>
        <a href="index.php?cambiar_ajuste=modo_oscuro" class="btn-ajuste <?php echo $usuario['modo_oscuro'] ? 'activo' : ''; ?>">
            🌙 Modo Oscuro (<?php echo $usuario['modo_oscuro'] ? 'SÍ' : 'NO'; ?>)
        </a>
        <a href="index.php?cambiar_ajuste=temporizador_visual" class="btn-ajuste <?php echo $usuario['temporizador_visual'] ? 'activo' : ''; ?>">
            ⏱️ Temporizador Visual (<?php echo $usuario['temporizador_visual'] ? 'SÍ' : 'NO'; ?>)
        </a>
    </div>
</div>

<!-- CAPA OSCURA DE ENFOQUE (Evita la distracción sensorial) -->
<div class="pantalla-oscura-asistente" id="capaFondoAsistente" onclick="alternarAsistente()"></div>

<!-- Ventana de Diálogo Guiado Estilo Modal Centrado -->
<div class="ventana-asistente-nueva" id="chatAsistente">
    <div class="asistente-cabecera" style="font-family: Arial; display: flex; justify-content: space-between; align-items: center; background-color: #1a426e; color: white; padding: 15px; font-weight: bold;">
        <span>🤖 Asistente Virtual JB</span>
        <span onclick="alternarAsistente()" style="cursor: pointer; font-size: 18px;">✖️</span>
    </div>
    <div class="asistente-cuerpo" style="font-family: Arial; padding: 20px; background-color: #f8fafc;">
        <div class="burbuja-asistente" style="background: white; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 15px; line-height: 1.5; color: #1a3557;">
            <span id="textoAsistente">¡Hola! Soy tu asistente de aprendizaje. Estoy aquí para guiarte de forma fácil. ¿Qué te gustaría hacer hoy?</span>
            <br>
            <button class="btn-asistente-audio" onclick="leerTextoVoz()" style="background: #4CAF50; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: bold; margin-top: 8px;">🔊 Escuchar Mensaje</button>
        </div>
        
        <button class="btn-opcion-asistente" onclick="asistenteResponde('cursos')">📖 ¿Cómo ingreso a mis cursos?</button>
        <button class="btn-opcion-asistente" onclick="asistenteResponde('progreso')">🏆 ¿Cómo completo un curso?</button>
        <button class="btn-opcion-asistente" onclick="asistenteResponde('ajustes')">🛠️ ¿Para qué sirven los botones de abajo?</button>
        <button class="btn-opcion-asistente" onclick="asistenteResponde('saludar')">✨ Volver al saludo inicial</button>
    </div>
</div>

<!-- Modal Perfil del Estudiante -->
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
    const modal = document.getElementById('modalPerfilEst');
    modal.classList.toggle('mostrar');
}
document.getElementById('modalPerfilEst').addEventListener('click', function(e) {
    if (e.target === this) togglePerfilEst();
});

// Función para abrir y cerrar el asistente integrado al centro
function alternarAsistente() {
    const asistente = document.getElementById("chatAsistente");
    const capaFondo = document.getElementById("capaFondoAsistente");
    
    if (asistente.style.display === "block") {
        asistente.style.display = "none";
        capaFondo.style.display = "none";
        window.speechSynthesis.cancel();
    } else {
        asistente.style.display = "block";
        capaFondo.style.display = "block";
    }
}

// Banco de respuestas del asistente guiado
function asistenteResponde(clave) {
    const cajaTexto = document.getElementById("textoAsistente");
    window.speechSynthesis.cancel();

    if (clave === 'cursos') {
        cajaTexto.innerText = "Para estudiar, haz clic en el módulo de 'Mis Cursos'. Se abrirá una página completa con tus materias ordenadas en una lista limpia. Pulsa la que desees aprender hoy.";
    } else if (clave === 'progreso') {
        cajaTexto.innerText = "Dentro de la lección, lee la guía corta o mira el video instructivo. Al finalizar, responde el cuestionario para completar tu curso y liberar tu compromiso pedagógico.";
    } else if (clave === 'ajustes') {
        cajaTexto.innerText = "Los botones del panel inferior adaptan los estímulos de la pantalla. Sirven para agrandar el tamaño del texto si tienes dificultad de lectura o activar dibujos de apoyo gráfico.";
    } else if (clave === 'saludar') {
        cajaTexto.innerText = "¡Hola! Soy tu asistente de aprendizaje. Estoy aquí para guiarte de forma fácil. ¿Qué te gustaría hacer hoy?";
    }
}

// Función inclusiva de Texto a Voz nativa de HTML5
function leerTextoVoz() {
    const textoParaLeer = document.getElementById("textoAsistente").innerText;
    window.speechSynthesis.cancel();
    const mensajeVoz = new SpeechSynthesisUtterance(textoParaLeer);
    mensajeVoz.lang = 'es-ES';
    mensajeVoz.rate = 0.9; // Velocidad pausada para facilitar la asimilación cognitiva
    window.speechSynthesis.speak(mensajeVoz);
}

</script>
</body>
</html>