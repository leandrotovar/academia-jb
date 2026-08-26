-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 12-07-2026 a las 23:58:41
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `academia_jb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id` int(11) NOT NULL,
  `actividad` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`id`, `actividad`, `fecha`, `hora`) VALUES
(1, 'El Administrador ha registrado al docente: franyer (franyer@gmail.com)', '2026-06-22', '20:13:46'),
(2, 'El Administrador ha registrado al docente: daniel rojas (daniel1@gmail.com)', '2026-07-08', '21:36:43'),
(3, 'El Administrador modificó al docente: franyer (franyer@gmail.com)', '2026-07-08', '22:52:54'),
(4, 'El Administrador modificó al docente: leandro (leandro@gmail.com)', '2026-07-08', '22:53:03'),
(5, 'El Administrador registró al docente: Franyer Lugo (franyer@gmail.com)', '2026-07-09', '15:31:41'),
(6, 'El Administrador actualizó materiales del curso ID 4', '2026-07-09', '17:12:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `texto_leccion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `clave`, `nombre`, `video_url`, `pdf_path`, `texto_leccion`) VALUES
(1, 'matematica', 'Matemáticas', 'https://youtube.com', 'guias/Guia_Estudio_matematica.pdf', 'Un lÝmite matemßtico describe c¾mo se comporta una funci¾n cuando se acerca a un valor especÝfico. Es la base del cßlculo diferencial y se usa para medir cambios.'),
(2, 'fisica', 'Física', 'https://youtube.com', 'guias/Guia_Estudio_fisica.pdf', 'El Movimiento RectilÝneo Uniforme (MRU) es cuando un objeto se mueve en lÝnea recta a velocidad constante. No acelera ni frena.'),
(3, 'quimica', 'Química', 'https://youtube.com', 'guias/Guia_Estudio_quimica.pdf', 'La quÝmica orgßnica estudia los compuestos de carbono. El carbono puede formar hasta 4 enlaces con otros ßtomos. Esto se llama tetravalencia.'),
(4, 'aeronautica', 'Aeronáutica', 'https://www.youtube.com/watch?v=hKTTIm4H1HI&list=PLMeJoueUoVQMyYIiqa8UYmVN4rjfL1Vr_', 'uploads/pdf/Guia_Estudio_aeronautica.pdf', 'La sustentaci¾n es la fuerza que eleva un avi¾n. El aire pasa mßs rßpido por la parte curva del ala, creando una diferencia de presi¾n que empuja el ala hacia arriba (Principio de Bernoulli).'),
(5, 'informatica', 'Informática', 'https://youtube.com', 'guias/Guia_Estudio_informatica.pdf', 'Un arreglo es una lista de datos del mismo tipo guardados en posiciones seguidas de memoria. Cada elemento se encuentra con un n·mero de Ýndice (empezando desde 0).');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `tipo` enum('inscripcion','clase','entrega','otro') DEFAULT 'otro',
  `icono` varchar(10) DEFAULT '??',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `tipo`, `icono`, `activo`, `created_at`) VALUES
(4, 'Período de Preinscripciones e Inscripciones', 'Habilitación formal del sistema para la selección y registro en los nuevos módulos avanzados de estudio.', '2026-06-01', '2026-06-15', 'inscripcion', '📝', 1, '2026-07-09 16:46:48'),
(5, 'Inicio Oficial de Clases Virtuales', 'Apertura del material interactivo, lecturas guiadas y videos didácticos en las 5 áreas del conocimiento.', '2026-06-16', NULL, 'clase', '🚀', 1, '2026-07-09 16:46:48'),
(6, 'Fecha Límite - Primera Evaluación Formativa', 'Cierre del primer bloque de cuestionarios para la validación y habilitación de los siguientes niveles del curso.', '2026-07-10', NULL, 'entrega', '📂', 1, '2026-07-09 16:46:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `materia` varchar(50) NOT NULL,
  `fecha_inscripcion` datetime DEFAULT current_timestamp(),
  `estado` enum('activo','completado') DEFAULT 'activo',
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `usuario_id`, `materia`, `fecha_inscripcion`, `estado`, `notas`) VALUES
(3, 15, 'matematica', '2026-07-09 16:14:15', 'completado', '\nEvaluación: 2/3 (67%) - Aprobado\nEvaluación: 2/3 (67%) - Aprobado\nEvaluación: 2/3 (67%) - Aprobado\nEvaluación: 2/3 (67%) - Aprobado\nEvaluación: 2/3 (67%) - Aprobado'),
(4, 2, 'quimica', '2026-07-09 16:46:56', 'completado', '\nEvaluación: 2/3 (67%) - Aprobado'),
(10, 7, 'aeronautica', '2026-07-09 17:13:21', 'activo', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lecciones`
--

CREATE TABLE `lecciones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `contenido_texto` text NOT NULL,
  `url_pictograma` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `lecciones`
--

INSERT INTO `lecciones` (`id`, `titulo`, `contenido_texto`, `url_pictograma`) VALUES
(1, 'Las Plantas', 'Las plantas necesitan agua y sol para vivir.', 'imagenes/planta.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_materias`
--

CREATE TABLE `profesor_materias` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `materia_clave` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesor_materias`
--

INSERT INTO `profesor_materias` (`id`, `profesor_id`, `materia_clave`) VALUES
(2, 14, 'fisica'),
(3, 14, 'informatica'),
(1, 14, 'matematica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `terminos`
--

CREATE TABLE `terminos` (
  `id` int(11) NOT NULL,
  `termino` varchar(100) NOT NULL,
  `definicion` text NOT NULL,
  `materia` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `terminos`
--

INSERT INTO `terminos` (`id`, `termino`, `definicion`, `materia`, `activo`, `created_at`) VALUES
(6, 'Algoritmo', 'Conjunto prescrito de instrucciones o reglas bien definidas, ordenadas y finitas que permiten llevar a cabo una actividad mediante pasos sucesivos sin generar dudas a quien deba realizar dicha acción.', 'matematica', 1, '2026-07-09 16:46:48'),
(7, 'Cinemática', 'Rama de la física que estudia las leyes del movimiento de los cuerpos sin tomar en consideración las causas (fuerzas) que lo producen, limitándose esencialmente al estudio de la trayectoria en función del tiempo.', 'fisica', 1, '2026-07-09 16:46:48'),
(8, 'Enlace Covalente', 'Tipo de enlace químico de alta estabilidad que se produce cuando dos átomos se unen para alcanzar el octeto estable, compartiendo electrones procedentes de sus capas de valencia externas.', 'quimica', 1, '2026-07-09 16:46:48'),
(9, 'Aerodinámica', 'Parte de la mecánica de fluidos que estudia las interacciones y fuerzas físicas generadas cuando un cuerpo sólido se desplaza a través del aire o de cualquier otro gas circulante.', 'aeronautica', 1, '2026-07-09 16:46:48'),
(10, 'Matriz (Array Bidimensional)', 'Estructura de datos organizada en filas y columnas indexadas numéricamente que permite almacenar múltiples valores homogéneos bajo una misma variable en la memoria del sistema informático.', 'informatica', 1, '2026-07-09 16:46:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('estudiante','docente','administrador') DEFAULT 'estudiante',
  `tipo_tea` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `modo_oscuro` tinyint(1) DEFAULT 0,
  `fuente_grande` tinyint(1) DEFAULT 0,
  `pictogramas_activos` tinyint(1) DEFAULT 1,
  `temporizador_visual` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `cedula`, `nombre`, `email`, `password`, `rol`, `tipo_tea`, `fecha_registro`, `modo_oscuro`, `fuente_grande`, `pictogramas_activos`, `temporizador_visual`) VALUES
(2, NULL, 'leandro', 'leandrotovar26090@gmail.com', '$2y$10$wwpER55wgUKyXm4SpyNF1eGnviz6tZh8/oNZ0XOae0PWE5CwDpW7i', 'estudiante', 0, '2026-07-08 21:38:09', 0, 0, 1, 1),
(7, NULL, 'Betania Silva', 'betania@academiajb.com', '$2y$10$uF7ivGqqSrgJEkcKlM3Pdu9Kr2f4UWjFewJ84of7oYILXhD5TlBrC', 'administrador', 0, '2026-07-08 21:38:09', 0, 0, 1, 1),
(14, '26090156', 'Franyer Lugo', 'franyer@gmail.com', '$2y$10$6IfPh/lsDU2O0GB9b453terHXnolCBU5ILj5f4.6/U3fsZdpmet3a', 'docente', 0, '2026-07-09 15:31:41', 0, 0, 1, 1),
(15, 'v-11111111', 'luis diaz', 'luis@gmail.com', '$2y$10$mYFsS8nqAPtWBdlOit3lKOiBEl6W3nXzEgzBdkGAOl/ddR7PuJAD2', 'estudiante', 1, '2026-07-09 15:59:12', 0, 0, 1, 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inscripcion_unica` (`usuario_id`,`materia`);

--
-- Indices de la tabla `lecciones`
--
ALTER TABLE `lecciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `profesor_materias`
--
ALTER TABLE `profesor_materias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `profesor_id` (`profesor_id`,`materia_clave`);

--
-- Indices de la tabla `terminos`
--
ALTER TABLE `terminos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `lecciones`
--
ALTER TABLE `lecciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `profesor_materias`
--
ALTER TABLE `profesor_materias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `terminos`
--
ALTER TABLE `terminos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesor_materias`
--
ALTER TABLE `profesor_materias`
  ADD CONSTRAINT `profesor_materias_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
