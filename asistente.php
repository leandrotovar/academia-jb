<?php
$modo_oscuro = isset($usuario) ? ($usuario['modo_oscuro'] ?? 0) : 0;
$fuente_grande = isset($usuario) ? ($usuario['fuente_grande'] ?? 0) : 0;
$pagina = basename($_SERVER['PHP_SELF']);

// Saludo de bienvenida personalizado por página
$saludos = [
    'login.php'             => 'Estás en la pantalla de inicio de sesión. Ingresa tu correo y contraseña para entrar a la plataforma.',
    'registro.php'          => 'Estás en el formulario de registro. Crea tu cuenta con un correo de Gmail y una contraseña segura.',
    'index.php'             => '¡Bienvenido a tu panel de aprendizaje! Aquí puedes acceder a tus cursos, ver tu progreso y ajustar tus herramientas de apoyo.',
    'index_tea.php'         => '¡Bienvenido a tu panel de aprendizaje! Aquí puedes acceder a tus cursos, ver tu progreso y ajustar tus herramientas de apoyo.',
    'curso.php'             => 'Estás dentro de una lección. Sigue los pasos: mira el video, lee la guía y completa la evaluación.',
    'registrar_cursos.php'  => 'Aquí puedes ver e inscribirte en los cursos disponibles. Elige el que más te guste y empieza a aprender.',
    'calendario.php'        => 'Este es el calendario académico. Consulta las fechas importantes de inscripciones, clases y entregas.',
    'glosario.php'          => 'Este es el glosario de términos. Busca palabras clave para entender mejor los conceptos de tus cursos.',
    'inicio_publico.php'    => 'Bienvenido a Academia JB. Explora nuestros cursos y herramientas diseñadas para un aprendizaje inclusivo.',
];
$saludo = $saludos[$pagina] ?? '¡Hola! Soy tu asistente de aprendizaje. Estoy aquí para guiarte de forma fácil. ¿Qué te gustaría hacer hoy?';

// Botones personalizados por página
$botones_por_pagina = [];
$botones_comunes = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📖 ¿Qué puedo hacer aquí?</button>' . "\n" .
    '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';

switch ($pagina) {
    case 'login.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">🔑 ¿Cómo inicio sesión?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'registro\')">📝 ¿Cómo me registro?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    case 'registro.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📋 ¿Qué datos necesito?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'tea\')">♿ ¿Qué es usuario TEA?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    case 'index.php':
    case 'index_tea.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📖 ¿Cómo ingreso a mis cursos?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'progreso\')">🏆 ¿Cómo completo un curso?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ajustes\')">🛠️ ¿Para qué sirven los botones de abajo?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    case 'curso.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📖 ¿Cómo uso esta lección?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'timer\')">⏱️ ¿Para qué es el temporizador?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'evaluacion\')">✏️ ¿Cómo hago la evaluación?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    case 'registrar_cursos.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📖 ¿Cómo me inscribo?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'cursos\')">📚 ¿Qué cursos hay?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    case 'calendario.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📅 ¿Cómo usar el calendario?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'fechas\')">📆 ¿Qué fechas son importantes?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    case 'glosario.php':
        $botones = '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'ayuda\')">📖 ¿Cómo buscar términos?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'navegar\')">🔍 ¿Cómo navego el glosario?</button>' . "\n" .
            '<button class="btn-opcion-asistente" onclick="asistenteResponde(\'saludar\')">✨ Volver al saludo inicial</button>';
        break;
    default:
        $botones = $botones_comunes;
}
?>
<style>
.btn-asistente-flotante {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: #1a426e;
    color: white;
    border: none;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-asistente-flotante:hover {
    transform: scale(1.1);
}
.ventana-asistente-nueva {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 360px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    display: none;
    overflow: hidden;
    border: 3px solid #1a426e;
    z-index: 2000;
    animation: slideInRight 0.3s ease forwards;
}
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}
.pantalla-oscura-asistente {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    z-index: 1999;
    display: none;
}
.btn-opcion-asistente {
    width: 100%;
    padding: 12px;
    margin-bottom: 8px;
    background: <?php echo $modo_oscuro ? '#1f1f1f' : 'white'; ?>;
    color: <?php echo $modo_oscuro ? '#ddd' : '#1a3557'; ?>;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: <?php echo $fuente_grande ? '16px' : '14px'; ?>;
    cursor: pointer;
    text-align: left;
    font-weight: bold;
    transition: background 0.2s;
}
.btn-opcion-asistente:hover {
    background: #e2e8f0;
}
.btn-asistente-audio {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 8px;
}
@media (prefers-reduced-motion: reduce) {
    .ventana-asistente-nueva {
        animation: none;
    }
    .btn-asistente-flotante {
        transition: none;
    }
}
</style>

<button class="btn-asistente-flotante" onclick="alternarAsistente()" title="Asistente Virtual">🤖</button>

<div class="pantalla-oscura-asistente" id="capaFondoAsistente" onclick="alternarAsistente()"></div>

<div class="ventana-asistente-nueva" id="chatAsistente">
    <div style="font-family: Arial; display: flex; justify-content: space-between; align-items: center; background-color: #1a426e; color: white; padding: 15px; font-weight: bold;">
        <span>🤖 Asistente Virtual JB</span>
        <span onclick="alternarAsistente()" style="cursor: pointer; font-size: 18px;">✖️</span>
    </div>
    <div style="font-family: Arial; padding: 20px; background-color: <?php echo $modo_oscuro ? '#1f1f1f' : '#f8fafc'; ?>;">
        <div style="background: <?php echo $modo_oscuro ? '#2d2d2d' : 'white'; ?>; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 15px; line-height: 1.5; color: <?php echo $modo_oscuro ? '#ddd' : '#1a3557'; ?>;">
            <span id="textoAsistente"><?php echo $saludo; ?></span>
            <br>
            <button class="btn-asistente-audio" onclick="leerTextoVoz()">🔊 Escuchar Mensaje</button>
        </div>
        <div id="botonesAsistente">
<?php echo $botones; ?>
        </div>
    </div>
</div>

<script>
const paginaActual = '<?php echo $pagina; ?>';

const respuestas = {
    'login.php': {
        'ayuda': 'Para iniciar sesión, escribe tu correo electrónico en el primer campo y tu contraseña en el segundo. Luego haz clic en "Ingresar".',
        'registro': 'Si aún no tienes cuenta, haz clic en "¿No tienes cuenta? Regístrate aquí" debajo del formulario. Solo se aceptan correos de Gmail.',
    },
    'registro.php': {
        'ayuda': 'Completa todos los campos: nombre completo, cédula, un correo @gmail.com y una contraseña de al menos 8 caracteres. Luego elige si eres Usuario General o Persona con TEA.',
        'tea': 'Si eres Persona con TEA, puedes activar herramientas de accesibilidad como Letra Grande, Pictogramas y más. Si eres Usuario General, estas opciones no aparecerán.',
    },
    'index.php': {
        'ayuda': 'En el panel principal, haz clic en "Mis Cursos" para ver tus materias. Usa "Mi Progreso" para ver tu avance. También puedes revisar el calendario o el glosario.',
        'progreso': 'Dentro de cada lección, lee la guía, mira el video y responde el cuestionario. Al completarlo, tu curso se marcará como finalizado.',
        'ajustes': 'Los botones del panel "Ajustes Visuales Rápidos" te permiten agrandar el texto, activar pictogramas, cambiar a modo oscuro o mostrar un temporizador visual.',
    },
    'index_tea.php': {
        'ayuda': 'En el panel principal, haz clic en "Mis Cursos" para ver tus materias. Usa "Mi Progreso" para ver tu avance. También puedes revisar el calendario o el glosario.',
        'progreso': 'Dentro de cada lección, lee la guía, mira el video y responde el cuestionario. Al completarlo, tu curso se marcará como finalizado.',
        'ajustes': 'Los botones del panel "Ajustes Visuales Rápidos" te permiten agrandar el texto, activar pictogramas, cambiar a modo oscuro o mostrar un temporizador visual.',
    },
    'curso.php': {
        'ayuda': 'Sigue los pasos en orden: 1) Mira el video explicativo, 2) Lee la guía de estudio, 3) Completa la evaluación. Puedes ir a tu propio ritmo.',
        'timer': 'El temporizador visual te ayuda a gestionar tu tiempo de estudio. Muestra cuánto tiempo llevas en la lección.',
        'evaluacion': 'Al final de la lección hay una evaluación. Respóndela y al enviarla se marcará el curso como completado.',
    },
    'registrar_cursos.php': {
        'ayuda': 'En esta página ves todos los cursos disponibles. Haz clic en "Inscribirme" junto al curso que quieras tomar. Puedes inscribirte en varios.',
        'cursos': 'Los cursos disponibles incluyen Matemáticas, Física, Química, Informática y Lectura. Cada uno tiene video, guía y evaluación.',
    },
    'calendario.php': {
        'ayuda': 'El calendario muestra las fechas importantes del período académico. Desplázate para ver todos los eventos del mes.',
        'fechas': 'Las fechas clave incluyen inicio de inscripciones, inicio de clases, fechas de entrega de evaluaciones y cierre de período.',
    },
    'glosario.php': {
        'ayuda': 'Usa el campo de búsqueda para encontrar términos. Escribe una palabra y los resultados se filtrarán automáticamente.',
        'navegar': 'Cada término del glosario tiene una definición breve. Puedes hacer clic en un término para ver más detalles.',
    },
};

function asistenteResponde(clave) {
    const cajaTexto = document.getElementById("textoAsistente");
    window.speechSynthesis.cancel();

    if (clave === 'saludar') {
        cajaTexto.innerText = '<?php echo str_replace("'", "\\'", $saludo); ?>';
        return;
    }

    const pagRespuestas = respuestas[paginaActual];
    if (pagRespuestas && pagRespuestas[clave]) {
        cajaTexto.innerText = pagRespuestas[clave];
    } else {
        cajaTexto.innerText = 'No tengo información específica sobre eso en esta página. Pregúntame de otra forma.';
    }
}

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

function leerTextoVoz() {
    const textoParaLeer = document.getElementById("textoAsistente").innerText;
    window.speechSynthesis.cancel();
    const mensajeVoz = new SpeechSynthesisUtterance(textoParaLeer);
    mensajeVoz.lang = 'es-ES';
    mensajeVoz.rate = 0.9;
    window.speechSynthesis.speak(mensajeVoz);
}
</script>
