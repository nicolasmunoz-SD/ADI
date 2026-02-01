<?php

session_start();
require_once 'Database.php';

if(!isset($_SESSION['user_id'])) {
    die('No autorizado');
}



$db = new Database();
$userId = $_SESSION['user_id'];
$formId = $_GET['form_id'] ?? 0;
$all = $_GET['all'] ?? false;


if($all) {
    $query = "SELECT f.titulo, rf.campo_nombre, rf.respuesta, rf.fecha_respuesta
              FROM respuestas_formulario rf
              JOIN formularios f ON rf.formulario_id = f.id
              WHERE rf.usuario_id = ?
              ORDER BY f.titulo, rf.campo_nombre";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
} else {
    $query = "SELECT f.titulo, rf.campo_nombre, rf.respuesta, rf.fecha_respuesta
              FROM respuestas_formulario rf
              JOIN formularios f ON rf.formulario_id = f.id
              WHERE rf.usuario_id = ? AND rf.formulario_id = ?
              ORDER BY rf.campo_nombre";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $formId]);
}

$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);


header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Respuestas de Formularios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .respuesta { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .campo { font-weight: bold; color: #667eea; }
        .valor { margin-left: 10px; }
        .fecha { color: #666; font-size: 12px; float: right; }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>
    <h1>Respuestas de Formularios</h1>
    <p>Generado: <?php echo date('d/m/Y H:i:s'); ?></p>
    
    <?php
    $currentForm = '';
    foreach($respuestas as $resp) {
        if($resp['titulo'] !== $currentForm) {
            $currentForm = $resp['titulo'];
            echo "<h2>{$currentForm}</h2>";
        }
        
        echo "<div class='respuesta'>";
        echo "<span class='campo'>{$resp['campo_nombre']}:</span>";
        echo "<span class='valor'>{$resp['respuesta']}</span>";
        echo "<span class='fecha'>{$resp['fecha_respuesta']}</span>";
        echo "</div>";
    }
    ?>
    
    <br><br>
    <button onclick="window.print()">Imprimir/Guardar como PDF</button>
    <button onclick="window.close()">Cerrar</button>
</body>
</html>