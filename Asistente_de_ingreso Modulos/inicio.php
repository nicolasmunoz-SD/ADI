<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: Login.html");
    exit;
}

$usuario_nombre = $_SESSION['usuario_nombre'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Inicio</title>
</head>
<body>
    <h1>¡Bienvenido, <?php echo htmlspecialchars($usuario_nombre); ?>! 🎉</h1>
    
    <div>
        <h2>Esta es tu página de inicio</h2>
        <p>Has iniciado sesión correctamente en el sistema.</p>
        
        <h3>Información de tu cuenta:</h3>
        <ul>
            <li><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario_nombre); ?></li>
            <li><strong>ID de usuario:</strong> <?php echo $_SESSION['usuario_id']; ?></li>
            <li><strong>Sesión iniciada:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
        </ul>
    </div>
    
    <br>
    
    <div>
        <a href="logout.php">Cerrar Sesión</a> |
        <a href="Registro.html">Registrar otro usuario</a>
    </div>
</body>
</html>