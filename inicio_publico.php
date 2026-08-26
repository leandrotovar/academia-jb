<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Academia JB - Asesoría Académica Integral</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f8;
            color: #1a3557;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            background-color: #1a426e;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 { margin: 0; font-size: 24px; }
        .btn-header {
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-header:hover { background-color: #1976D2; }
        
        .main-container {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 40px;
            gap: 40px;
        }
        .seccion-texto {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px;
        }
        .seccion-texto h2 { font-size: 32px; color: #1a426e; margin-bottom: 20px; }
        .seccion-texto p { font-size: 18px; line-height: 1.6; color: #555; }
        
        .seccion-imagen {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .infografia-jb {
            max-width: 100%;
            max-height: 500px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .botones-accion {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        .btn-principal {
             background-color: #45a049;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-principal:hover { background-color: #45a049; }
    </style>
</head>
<body>

<header class="header">
    <h1>Formacion Academica JB 🧩</h1>
    <!-- Enlace directo al inicio de sesión -->

    <a href="login.php" class="btn-header">Iniciar Sesión</a>
</h2>
   
        
</header>

<div class="main-container">
    <div class="seccion-texto">
        <h2>Asesoría Académica Integral</h2>
        <p> Ofrece formación académica online interactiva y de alta calidad a través de un componente web inclusivo, que permita al público general y a personas con Trastorno del Espectro Autista (TEA) acceder a material educativo especializado en áreas metodológicas, técnicas y científicas, promoviendo el aprendizaje autónomo y el desarrollo profesional sin barreras geográficas.</p>
        
    </div>

    <div class="seccion-imagen">
        <img src="imagenes/servicios.jpg" class="infografia-jb" alt="Servicios de Asesoría Academia JB">
    </div>
</div>

</body>
</html>