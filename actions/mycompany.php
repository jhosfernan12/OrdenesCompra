<?php
header('Content-Type: application/json');
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

    if (strlen($ruc) < 11) {
        echo json_encode(['success' => false, 'message' => 'El RUC debe tener 11 dígitos.']);
        exit;
    }

    // Verifica si ya hay un registro
    $result = $mysqli->query("SELECT COUNT(*) as total FROM infoempresa");
    $row = $result->fetch_assoc();

    if ($row['total'] == 0) {
        // No hay empresa aún, se inserta
        $stmt = $mysqli->prepare("INSERT INTO infoempresa (RUC, Nombre, Correo, Direccion, Celular) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ruc, $nombre, $correo, $direccion, $celular);
    } else {
        // Ya hay una empresa, se actualiza (independientemente del RUC anterior)
        $stmt = $mysqli->prepare("UPDATE infoempresa SET RUC = ?, Nombre = ?, Correo = ?, Direccion = ?, Celular = ? LIMIT 1");
        $stmt->bind_param("sssss", $ruc, $nombre, $correo, $direccion, $celular);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $stmt->error]);
    }

    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción inválida']);
