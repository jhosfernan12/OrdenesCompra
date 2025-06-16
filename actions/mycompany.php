<?php
ob_clean();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para convertir color HEX a RGB
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

// Manejo global de errores
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Excepción: ' . $e->getMessage()]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Error: $message en $file línea $line"]);
    exit;
});

// Conexión a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'ordenescompra');
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $result = $mysqli->query("SELECT * FROM infoempresa LIMIT 1");
    $empresa = $result->fetch_assoc();
    echo json_encode(['success' => true, 'empresa' => $empresa]);
    exit;
}

if ($action === 'save') {
    $ruc = $_POST['ruc'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $celular = $_POST['celular'] ?? '';

    // 🔵 Color recibido desde el formulario (no se guarda en la BD)
    $colorHex = $_POST['colorPrimario'] ?? '#6b5b95';  // Valor por defecto
    $colorRGB = hexToRgb($colorHex);

    if (strlen($ruc) < 11) {
        echo json_encode(['success' => false, 'message' => 'El RUC debe tener 11 dígitos.']);
        exit;
    }

    // Verifica si hay registro previo
    $result = $mysqli->query("SELECT COUNT(*) as total FROM infoempresa");
    $row = $result->fetch_assoc();

    if ($row['total'] == 0) {
        $stmt = $mysqli->prepare("INSERT INTO infoempresa (RUC, Nombre, Correo, Direccion, Celular) VALUES (?, ?, ?, ?, ?)");
    } else {
        $stmt = $mysqli->prepare("UPDATE infoempresa SET RUC = ?, Nombre = ?, Correo = ?, Direccion = ?, Celular = ? LIMIT 1");
    }

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta.']);
        exit;
    }

    $stmt->bind_param("sssss", $ruc, $nombre, $correo, $direccion, $celular);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Procesar el logo si se subió
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['logo']['tmp_name'];
        $fileName = 'logo.png';
        $destPath = '../UI/assets/logos/' . $fileName;

        $mimeType = mime_content_type($fileTmpPath);
        if (in_array($mimeType, ['image/png', 'image/jpeg'])) {
            $originalImage = null;
            if ($mimeType === 'image/jpeg') {
                $originalImage = imagecreatefromjpeg($fileTmpPath);
            } elseif ($mimeType === 'image/png') {
                $originalImage = imagecreatefrompng($fileTmpPath);
            }

            if ($originalImage) {
                $originalWidth = imagesx($originalImage);
                $originalHeight = imagesy($originalImage);

                // Medidas del cuadrado final
                $finalSize = 200;

                // Crear lienzo cuadrado blanco
                $squareCanvas = imagecreatetruecolor($finalSize, $finalSize);
                $white = imagecolorallocate($squareCanvas, 255, 255, 255);
                imagefill($squareCanvas, 0, 0, $white);

                // Calcular nueva escala proporcional
                if ($originalWidth > $originalHeight) {
                    $newWidth = $finalSize;
                    $newHeight = intval(($originalHeight / $originalWidth) * $finalSize);
                } else {
                    $newHeight = $finalSize;
                    $newWidth = intval(($originalWidth / $originalHeight) * $finalSize);
                }

                // Redimensionar imagen
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled(
                    $resizedImage, $originalImage,
                    0, 0, 0, 0,
                    $newWidth, $newHeight,
                    $originalWidth, $originalHeight
                );

                // Copiar al centro
                $xOffset = intval(($finalSize - $newWidth) / 2);
                $yOffset = intval(($finalSize - $newHeight) / 2);
                imagecopy($squareCanvas, $resizedImage, $xOffset, $yOffset, 0, 0, $newWidth, $newHeight);

                imagepng($squareCanvas, $destPath, 6);
                imagedestroy($originalImage);
                imagedestroy($resizedImage);
                imagedestroy($squareCanvas);

                echo json_encode([
                    'success' => true,
                    'message' => 'Logo redimensionado correctamente.',
                    'colorRGB' => $colorRGB
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al procesar la imagen.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Solo se aceptan imágenes PNG o JPG.']);
            exit;
        }
    }

    // Si no se subió logo, igual mostramos el color para usarlo en PDF o CSS dinámico
    echo json_encode([
        'success' => true,
        'message' => 'Datos guardados correctamente.',
        'colorRGB' => $colorRGB
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción inválida']);
exit;
