<?php
// documentos.php
session_start();

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: Login.html");
    exit;
}

// Verificar que existe user_id
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit;
}

$userId = $_SESSION['user_id'];
$dbname = "adi_db";

// Crear conexión
$conn = new mysqli("localhost", "root", "", $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8");

// Crear carpeta uploads si no existe
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        die("No se pudo crear la carpeta de uploads");
    }
}

// Variables para mensajes
$mensaje = '';
$tipoMensaje = ''; // success, error, info

// Procesar acciones
$action = $_GET['action'] ?? '';

// Manejar subida de documentos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_documentos'])) {
    $tipo = $_POST['tipo'] ?? 'otros';
    $uploadSuccess = false;
    $errores = [];
    
    // Procesar cada archivo
    foreach ($_FILES['documentos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['documentos']['error'][$key] == UPLOAD_ERR_OK) {
            $nombreOriginal = basename($_FILES['documentos']['name'][$key]);
            $tamaño = $_FILES['documentos']['size'][$key];
            
            // Validar tamaño (máximo 10MB)
            if ($tamaño > 10 * 1024 * 1024) {
                $errores[] = "El archivo '$nombreOriginal' es demasiado grande (máximo 10MB)";
                continue;
            }
            
            // Generar nombre único
            $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
            $nombreArchivo = uniqid('doc_', true) . '_' . $userId . '.' . $extension;
            $rutaCompleta = $uploadDir . $nombreArchivo;
            
            // Mover archivo
            if (move_uploaded_file($tmp_name, $rutaCompleta)) {
                // Guardar en BD
                $sql = "INSERT INTO documentos (usuario_id, nombre, ruta, tipo, tamaño) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssi", $userId, $nombreOriginal, $nombreArchivo, $tipo, $tamaño);
                
                if ($stmt->execute()) {
                    $uploadSuccess = true;
                } else {
                    $errores[] = "Error al guardar '$nombreOriginal' en la base de datos";
                    // Eliminar archivo si falló la BD
                    if (file_exists($rutaCompleta)) {
                        unlink($rutaCompleta);
                    }
                }
                $stmt->close();
            } else {
                $errores[] = "Error al subir '$nombreOriginal'";
            }
        }
    }
    
    if ($uploadSuccess) {
        $mensaje = count($errores) > 0 
            ? "Archivos subidos con algunos errores: " . implode(", ", $errores)
            : "Documentos subidos correctamente";
        $tipoMensaje = $uploadSuccess ? 'success' : 'error';
    } else {
        $mensaje = "Error al subir documentos: " . implode(", ", $errores);
        $tipoMensaje = 'error';
    }
}

// Manejar eliminación de documento
if (isset($_GET['eliminar'])) {
    $docId = intval($_GET['eliminar']);
    
    // Obtener la ruta del documento
    $sql = "SELECT ruta FROM documentos WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $docId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Eliminar archivo físico
        $rutaArchivo = $uploadDir . $row['ruta'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
        
        // Eliminar de la base de datos
        $sqlDelete = "DELETE FROM documentos WHERE id = ? AND usuario_id = ?";
        $stmtDelete = $conn->prepare($sqlDelete);
        $stmtDelete->bind_param("ii", $docId, $userId);
        
        if ($stmtDelete->execute()) {
            $mensaje = "Documento eliminado correctamente";
            $tipoMensaje = 'success';
        } else {
            $mensaje = "Error al eliminar documento de la base de datos";
            $tipoMensaje = 'error';
        }
        $stmtDelete->close();
    } else {
        $mensaje = "Documento no encontrado o no autorizado";
        $tipoMensaje = 'error';
    }
    $stmt->close();
}

// Obtener documentos del usuario
$sql = "SELECT id, nombre, tipo, tamaño, fecha_subida, ruta 
        FROM documentos 
        WHERE usuario_id = ? 
        ORDER BY fecha_subida DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$documentos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Función para formatear tamaño
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    
    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

// Cerrar conexión (la cerramos después de usarla completamente)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - Asistente de Ingreso</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .nav-menu {
            display: flex;
            gap: 15px;
        }
        
        .nav-item {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        /* Contenedor Principal */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Mensajes */
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .mensaje.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .mensaje.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .mensaje.info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        
        /* Sección de Subida */
        .upload-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .upload-area:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        /* Lista de Documentos */
        .documents-list {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .document-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .document-card:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .document-info {
            flex: 1;
        }
        
        .document-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .document-meta {
            font-size: 14px;
            color: #666;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Botones */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Formularios */
        select, input[type="file"] {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
            max-width: 300px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            margin-top: 50px;
            border-top: 1px solid #eee;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .document-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .document-actions {
                align-self: flex-end;
            }
        }
        
        /* Archivos seleccionados */
        .selected-files {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            display: none;
        }
        
        .selected-file-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Sin documentos */
        .no-documents {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1>📁 Documentos</h1>
            <div class="nav-menu">
                <a href="Dashboard.html" class="nav-item">Dashboard</a>
                <a href="Perfil.html" class="nav-item">Mi Perfil</a>
                <a href="formularios.html" class="nav-item">Formularios</a>
                <a href="logout.php" class="nav-item">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <div class="container">
        <!-- Mostrar mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Sección de Subida -->
        <section class="upload-section">
            <h2 style="margin-bottom: 20px; color: #444;">Subir Documentos</h2>
            
            <form method="POST" enctype="multipart/form-data" action="documentos.php">
                <div class="form-group">
                    <label for="documentos">Seleccionar archivos:</label>
                    <input type="file" id="documentos" name="documentos[]" multiple 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt"
                           onchange="mostrarArchivosSeleccionados(this)">
                    <small style="color: #666;">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, TXT. Máximo 10MB por archivo.</small>
                </div>
                
                <!-- Archivos seleccionados -->
                <div id="selected-files-container" class="selected-files"></div>
                
                <div class="form-group">
                    <label for="tipo">Tipo de documento:</label>
                    <select id="tipo" name="tipo" required>
                        <option value="identificacion">Identificación</option>
                        <option value="curriculum">Curriculum</option>
                        <option value="certificaciones">Certificaciones</option>
                        <option value="contratos">Contratos</option>
                        <option value="facturas">Facturas</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>
                
                <button type="submit" name="subir_documentos" class="btn btn-success">
                    Subir Documentos
                </button>
            </form>
        </section>

        <!-- Lista de Documentos -->
        <section class="documents-list">
            <h2 style="margin-bottom: 20px; color: #444;">Documentos Subidos</h2>
            
            <?php if (empty($documentos)): ?>
                <div class="no-documents">
                    <h3>No hay documentos subidos</h3>
                    <p>Sube tu primer documento usando el formulario arriba.</p>
                </div>
            <?php else: ?>
                <?php foreach ($documentos as $doc): ?>
                    <div class="document-card">
                        <div class="document-info">
                            <div class="document-name"><?php echo htmlspecialchars($doc['nombre']); ?></div>
                            <div class="document-meta">
                                Tipo: <?php echo htmlspecialchars($doc['tipo']); ?> | 
                                Subido: <?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?> |
                                Tamaño: <?php echo formatFileSize($doc['tamaño']); ?> |
                                ID: <?php echo $doc['id']; ?>
                            </div>
                        </div>
                        <div class="document-actions">
                            <a href="uploads/<?php echo htmlspecialchars($doc['ruta']); ?>" 
                               download="<?php echo htmlspecialchars($doc['nombre']); ?>"
                               class="btn btn-primary">
                                Descargar
                            </a>
                            <a href="documentos.php?eliminar=<?php echo $doc['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este documento?')">
                                Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>Asistente de Ingreso &copy; 2026</p>
    </footer>

    <script>
        // Mostrar archivos seleccionados
        function mostrarArchivosSeleccionados(input) {
            const container = document.getElementById('selected-files-container');
            const files = input.files;
            
            if (files.length === 0) {
                container.style.display = 'none';
                container.innerHTML = '';
                return;
            }
            
            let html = '<h4>Archivos seleccionados:</h4>';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                
                html += `
                    <div class="selected-file-item">
                        <div>
                            <strong>${file.name}</strong><br>
                            <span style="color: #666; font-size: 12px;">${sizeMB} MB</span>
                        </div>
                    </div>
                `;
            }
            
            html += `<p style="margin-top: 10px; color: #666;">
                Total: ${files.length} archivo(s)
            </p>`;
            
            container.innerHTML = html;
            container.style.display = 'block';
        }
        
        // Confirmación para eliminación
        function confirmarEliminacion() {
            return confirm('¿Estás seguro de que quieres eliminar este documento?');
        }
        
        // Validación de tamaño antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const input = document.getElementById('documentos');
            const files = input.files;
            let error = false;
            let errorMsg = '';
            
            for (let i = 0; i < files.length; i++) {
                if (files[i].size > 10 * 1024 * 1024) { // 10MB
                    error = true;
                    errorMsg = `El archivo "${files[i].name}" es demasiado grande (máximo 10MB)`;
                    break;
                }
            }
            
            if (error) {
                e.preventDefault();
                alert(errorMsg);
                return false;
            }
            
            if (files.length === 0) {
                e.preventDefault();
                alert('Por favor, selecciona al menos un archivo');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
