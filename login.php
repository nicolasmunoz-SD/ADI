<?php
// login.php - SOLO NOMBRE Y CONTRASEÑA
session_start();

// Configuración
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "adi_db";

// Variables
$error = "";

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = $_POST['nombre'] ?? '';
    $clave = $_POST['clave'] ?? '';
    
    if (empty($nombre) || empty($clave)) {
        $error = "Por favor, complete todos los campos";
    } else {
        // Conexión
        $conn = new mysqli($host, $user, $pass, $dbname);
        
        if ($conn->connect_error) {
            $error = "Error de conexión a la base de datos";
        } else {
            // Consulta segura
            $sql = "SELECT id, nombre, clave FROM usuarios WHERE nombre = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();
                
                // Comparar contraseñas directamente (sin hash)
                if ($clave === $usuario['clave']) {
                    // Login exitoso
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['loggedin'] = true;
                    
                    // Guardar también en localStorage para JavaScript
                    echo '<script>
                        localStorage.setItem("loggedin", "true");
                        localStorage.setItem("usuario_nombre", "' . $usuario['nombre'] . '");
                        localStorage.setItem("user_id", "' . $usuario['id'] . '");
                        
                        window.location.href = "Dashboard.html";
                    </script>';
                    exit;
                } else {
                    $error = "Contraseña incorrecta";
                }
            } else {
                $error = "Usuario no encontrado";
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Asistente de Ingreso</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5ee;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Iniciar Sesión</h1>
        <p class="subtitle">Asistente de Ingreso</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre de Usuario</label>
                <input type="text" id="nombre" name="nombre" required 
                       placeholder="Tu nombre de usuario" 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required 
                       placeholder="Tu contraseña">
            </div>
            
            <button type="submit" class="btn-login">Ingresar al Sistema</button>
        </form>
        
        <div class="register-link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
    </div>
</body>
</html>