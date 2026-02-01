<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Configuración de base de datos
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "adi_db";

// Conexión
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}

// Contar documentos del usuario
$sql = "SELECT COUNT(*) as total FROM documentos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'count' => $row['total']
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Error en consulta']);
}

$conn->close();
?>
