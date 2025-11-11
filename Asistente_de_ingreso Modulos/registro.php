<?php
include("Database.php"); // ← CAMBIA ESTA LÍNEA

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database(); // ← Y aquí también
    $conn = $db->getConexion();
    
    // Verificar datos del formulario
    if (isset($_POST['nombre'], $_POST['clave'])) {
        
        $nombre = trim($_POST['nombre']);
        $clave  = trim($_POST['clave']);
        
        // Validar que no estén vacíos
        if (!empty($nombre) && !empty($clave)) {
            
            try {
                // Consulta preparada
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, clave) VALUES (?, ?)");
                $stmt->bind_param("ss", $nombre, $clave);
                
                if ($stmt->execute()) {
                    // después del registro, redirige de vuelta al login
                    header("Location: Login.html?registro=exitoso");
                    exit;

                    
                } else {
                    $mensaje = "❌ Error al registrar: " . $stmt->error;
                    $tipo = "error";
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $mensaje = "❌ Error de base de datos: " . $e->getMessage();
                $tipo = "error";
            }
            
        } else {
            $mensaje = "⚠️ Los campos no pueden estar vacíos";
            $tipo = "error";
        }
        
    } else {
        $mensaje = "⚠️ Faltan datos: nombre y clave requeridos";
        $tipo = "error";
    }
    
    $conn->close();
    
} else {
    $mensaje = "❌ Acceso no permitido. Usa el formulario HTML o método POST.";
    $tipo = "error";
}

// Mostrar respuesta
echo $mensaje;
if ($tipo === "success") {
    echo "<br><a href='Registro.html'>Volver al formulario</a>";
}
?>