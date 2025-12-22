<?php
// Prueba simple de input file
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Input File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Prueba de Input File</h1>
        
        <div class="alert alert-info">
            Esta página prueba si el input type="file" funciona correctamente
        </div>

        <!-- Test 1: Input file simple sin modal -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Test 1: Input File Directo</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="test1" class="form-label">Selecciona un archivo:</label>
                        <input type="file" class="form-control" id="test1" name="test1" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </form>
                <div id="result1" class="mt-2"></div>
            </div>
        </div>

        <!-- Test 2: Input file dentro de modal -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Test 2: Input File en Modal</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">Abrir Modal</button>
            </div>
        </div>

        <!-- Test 3: Multipart form con archivo -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Test 3: Upload con enctype="multipart/form-data"</h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="test3" class="form-label">Selecciona imagen:</label>
                        <input type="file" class="form-control" id="test3" name="test3" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-success">Subir Archivo</button>
                </form>
                <div id="result3" class="mt-2"></div>
            </div>
        </div>
    </div>

    <!-- Modal Test -->
    <div class="modal fade" id="testModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prueba de Input en Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formModal" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="testModal_file" class="form-label">Selecciona archivo:</label>
                            <input type="file" class="form-control" id="testModal_file" name="testModal_file" accept="image/*" onchange="handleFileChange(event)">
                            <div class="form-text">Formato: JPG, PNG, GIF</div>
                        </div>
                        <div id="preview" class="text-center mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitModalForm()">Subir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleFileChange(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" class="img-fluid" style="max-height:200px;" />';
                };
                reader.readAsDataURL(file);
                console.log('Archivo seleccionado:', file.name, file.size, file.type);
            } else {
                preview.innerHTML = '';
            }
        }

        function submitModalForm() {
            const form = document.getElementById('formModal');
            const file = document.getElementById('testModal_file').files[0];
            if (!file) {
                alert('Selecciona un archivo primero');
                return;
            }
            alert('Archivo listo para enviar: ' + file.name);
        }

        // Test 1: Detectar cambio
        document.getElementById('test1').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('result1').innerHTML = '<div class="alert alert-success">✓ Archivo detectado: ' + file.name + ' (' + (file.size/1024).toFixed(2) + ' KB)</div>';
            }
        });

        // Test 3: Detectar cambio
        document.getElementById('test3').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('result3').innerHTML = '<div class="alert alert-success">✓ Archivo detectado: ' + file.name + ' (' + (file.size/1024).toFixed(2) + ' KB) - ' + file.type + '</div>';
            }
        });

        console.log('Página cargada. Input file disponibles:', document.querySelectorAll('input[type="file"]').length);
    </script>
</body>
</html>
