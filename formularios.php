<?php
// formularios.php - ESPECÍFICO PARA LOS 5 CAMPOS
session_start();

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['error' => 'No autorizado. Por favor inicia sesión.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Conexión
$conn = new mysqli("localhost", "root", "", "adi_db");

if($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$action = $_GET['action'] ?? '';

// OBTENER FORMULARIOS
if($action == 'getForms') {
    $sql = "SELECT * FROM formularios WHERE activo = 1";
    $result = $conn->query($sql);
    
    $forms = [];
    while($row = $result->fetch_assoc()) {
        // Verificar si ya respondió
        $check = $conn->query("SELECT COUNT(*) as count FROM respuestas_formulario WHERE usuario_id = $userId AND formulario_id = {$row['id']}");
        $checkRow = $check->fetch_assoc();
        $row['estado_usuario'] = $checkRow['count'] > 0 ? 'completado' : 'pendiente';
        $forms[] = $row;
    }
    
    echo json_encode($forms);
}

// OBTENER EL FORMULARIO DE DATOS PERSONALES
elseif($action == 'getForm') {
    $formId = intval($_GET['id']);
    
    // Solo tenemos el formulario de datos personales (id = 1)
    $sql = "SELECT * FROM formularios WHERE id = $formId";
    $result = $conn->query($sql);
    $form = $result->fetch_assoc();
    
    if(!$form) {
        echo json_encode(['error' => 'Formulario no encontrado']);
        exit;
    }
    
    // Obtener los 5 campos específicos
    $sql = "SELECT * FROM campos_formulario WHERE formulario_id = $formId ORDER BY orden";
    $result = $conn->query($sql);
    $campos = [];
    while($row = $result->fetch_assoc()) {
        $campos[] = $row;
    }
    
    // Obtener respuestas existentes
    $sql = "SELECT campo_nombre, respuesta FROM respuestas_formulario WHERE usuario_id = $userId AND formulario_id = $formId";
    $result = $conn->query($sql);
    $respuestas = [];
    while($row = $result->fetch_assoc()) {
        $respuestas[$row['campo_nombre']] = $row['respuesta'];
    }
    
    // Agregar valores a campos
    foreach($campos as &$campo) {
        $campo['valor_actual'] = $respuestas[$campo['nombre_campo']] ?? '';
    }
    
    $form['campos'] = $campos;
    echo json_encode($form);
}

// GUARDAR LOS 5 CAMPOS
elseif($action == 'submitForm') {
    $formId = intval($_POST['form_id']);
    
    // Los 5 campos específicos
    $campos = [
        'nombre_completo' => $_POST['nombre_completo'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'ciudad' => $_POST['ciudad'] ?? '',
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
        'email' => $_POST['email'] ?? ''
    ];
    
    // Validar que todos los campos estén completos
    foreach($campos as $nombre => $valor) {
        if(empty(trim($valor))) {
            echo "Error: El campo '$nombre' es requerido";
            exit;
        }
    }
    
    // Validar formato de email
    if(!filter_var($campos['email'], FILTER_VALIDATE_EMAIL)) {
        echo "Error: El formato del email no es válido";
        exit;
    }
    
    // Eliminar respuestas anteriores
    $conn->query("DELETE FROM respuestas_formulario WHERE usuario_id = $userId AND formulario_id = $formId");
    
    // Insertar nuevas respuestas
    foreach($campos as $nombre => $valor) {
        $valor = $conn->real_escape_string(trim($valor));
        $conn->query("INSERT INTO respuestas_formulario (usuario_id, formulario_id, campo_nombre, respuesta) VALUES ($userId, $formId, '$nombre', '$valor')");
    }
    
    echo "success";
}

$conn->close();
?>