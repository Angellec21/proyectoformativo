<?php
// test_upload_simple.php
// Prueba simple de upload

$resultado = [];

if ($_FILES && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    $resultado['recibido'] = true;
    $resultado['nombre'] = $file['name'];
    $resultado['tipo'] = $file['type'];
    $resultado['tamaño'] = $file['size'];
    $resultado['tmp_name'] = $file['tmp_name'];
    $resultado['error'] = $file['error'];
    
    if ($file['error'] === 0) {
        $destino = __DIR__ . '/../Productos/img/' . time() . '_' . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $destino)) {
            $resultado['movido'] = true;
            $resultado['destino'] = $destino;
        } else {
            $resultado['movido'] = false;
            $resultado['error_msg'] = 'No se pudo mover el archivo';
        }
    } else {
        $resultado['error_msg'] = 'Error en la carga: ' . $file['error'];
    }
} else {
    $resultado['recibido'] = false;
    $resultado['msg'] = 'No se recibió archivo';
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
