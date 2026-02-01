<?php
// registro.php - SOLO NOMBRE Y CONTRASEÑA
session_start();

// Configuración
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "adi_db";

// Variables
$error = "";
$success = "";
$nombre = "";

// Procesar registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = trim($_POST['nombre'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');
    
    // Validaciones
    if (empty($nombre) || empty($clave) || empty($confirmar)) {
        $error = "Todos los campos son requeridos";
    } elseif (strlen($clave) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif ($clave !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Conexión
        $conn = new mysqli($host, $user, $pass, $dbname);
        
        if ($conn->connect_error) {
            $error = "Error de conexión a la base de datos";
        } else {
            // Verificar si el usuario ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre = ?");
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "El nombre de usuario ya está registrado";
                $stmt->close();
            } else {
                $stmt->close();
                
                // Insertar nuevo usuario
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, clave) VALUES (?, ?)");
                $stmt->bind_param("ss", $nombre, $clave);
                
                if ($stmt->execute()) {
                    $success = "¡Registro exitoso! Serás redirigido al login...";
                    $nombre = "";
                    
                    // Redirigir después de 2 segundos
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 2000);
                    </script>';
                } else {
                    $error = "Error al registrar usuario";
                }
                
                $stmt->close();
            }
            
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
    <title>Registro - Asistente de Ingreso</title>
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
        
        .register-box {
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
            margin-bottom: 20px;
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
        
        .btn-register {
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
            margin-top: 10px;
        }
        
        .btn-register:hover {
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
        
        .alert-success {
            background-color: #efe;
            color: #383;
            border: 1px solid #cfc;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h1>Crear Cuenta</h1>
        <p class="subtitle">Registro de nuevo usuario</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre de Usuario *</label>
                <input type="text" id="nombre" name="nombre" required 
                       placeholder="Ej: juan.perez"
                       value="<?php echo htmlspecialchars($nombre); ?>"
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="clave">Contraseña *</label>
                <input type="password" id="clave" name="clave" required 
                       placeholder="Mínimo 6 caracteres"
                       minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirmar">Confirmar Contraseña *</label>
                <input type="password" id="confirmar" name="confirmar" required 
                       placeholder="Repite tu contraseña"
                       minlength="6">
            </div>
            
            <button type="submit" class="btn-register">Registrarse</button>
        </form>
        
        <div class="login-link">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
    </div>
    
    <script>
        // Validar que las contraseñas coincidan
        document.getElementById('confirmar').addEventListener('input', function() {
            var clave = document.getElementById('clave').value;
            var confirmar = this.value;
            
            if(clave !== confirmar && confirmar !== '') {
                this.style.borderColor = '#c33';
            } else {
                this.style.borderColor = '#e1e5ee';
            }
        });
    </script>
</body>
</html>