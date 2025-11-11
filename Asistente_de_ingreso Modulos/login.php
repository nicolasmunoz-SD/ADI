<?php
include("Database.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $db = new Database();
    $conn = $db->getConexion();
    
    if (isset($_POST['nombre'], $_POST['clave'])) {
        
        $nombre = trim($_POST['nombre']);
        $clave = trim($_POST['clave']);
        
        if (!empty($nombre) && !empty($clave)) {
            
            try {
                // Buscar usuario por nombre
                $stmt = $conn->prepare("SELECT id, nombre, clave FROM usuarios WHERE nombre = ?");
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $usuario = $result->fetch_assoc();
                    
                    // Verificar contraseña (sin hash por ahora)
                    if ($clave === $usuario['clave']) {
                        // Login exitoso
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'];
                        $_SESSION['loggedin'] = true;
                        
                        header("Location: inicio.php");
                        exit;
                    } else {
                        $error = "❌ Contraseña incorrecta";
                    }
                } else {
                    $error = "❌ Usuario no encontrado";
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
            
        } else {
            $error = "⚠️ Todos los campos son requeridos";
        }
        
    } else {
        $error = "⚠️ Faltan datos";
    }
    
    $conn->close();
}

// Si hay error o no es POST, mostrar formulario
?>