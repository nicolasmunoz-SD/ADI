<?php
session_start();
require_once 'Database.php';

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die('No autorizado');
}

$db = new Database();
$action = $_GET['action'] ?? '';

switch($action) {
    case 'getUserData':
        $userId = $_SESSION['user_id'];
        
        // CONSULTA CORREGIDA - Solo columnas que existen
        $stmt = $db->prepare("SELECT id, nombre FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($usuario) {
            // Agregar campos vacíos para compatibilidad
            $usuario['email'] = '';
            $usuario['telefono'] = '';
            $usuario['direccion'] = '';
            $usuario['empresa'] = '';
        }
        
        echo json_encode($usuario ?: []);
        break;
        
    case 'updateProfile':
        $userId = $_SESSION['user_id'];
        $nombre = $_POST['nombre'] ?? '';
        
        // Solo actualizar el nombre (que es lo único que existe)
        $stmt = $db->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        
        if($stmt->execute([$nombre, $userId])) {
            // Actualizar también en sesión
            $_SESSION['usuario_nombre'] = $nombre;
            echo 'success';
        } else {
            echo 'Error al actualizar';
        }
        break;
        
    case 'changePassword':
        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        // Verificar contraseña actual
        $stmt = $db->prepare("SELECT clave FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if($user && $currentPassword === $user['clave']) {
            // Actualizar contraseña (sin hash por ahora)
            $stmt = $db->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
            if($stmt->execute([$newPassword, $userId])) {
                echo 'success';
            } else {
                echo 'Error al cambiar contraseña';
            }
        } else {
            echo 'Contraseña actual incorrecta';
        }
        break;
        
    default:
        echo 'Acción no válida';
}
?>