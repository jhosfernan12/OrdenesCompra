<?php
header('Content-Type: application/json');
$host = "localhost";
$user = "root";
$pass = "";
$db = "ordenescompra";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida"]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $sql = "SELECT * FROM Proveedores WHERE Nombre LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode($data);
    exit;

} elseif ($action === 'get') {
    $ruc = $_GET['ruc'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE RUC = ?");
    $stmt->bind_param("s", $ruc);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
    exit;

} elseif ($action === 'update') {
    $originalRUC = $_POST['originalRUC'] ?? null; // RUC actual en la base
    $newRUC = $_POST['RUC'] ?? null; // Nuevo RUC que puede reemplazar al original
    $nombre = $_POST['Nombre'] ?? '';
    $direccion = $_POST['Direccion'] ?? '';
    $telefono = $_POST['Telefono'] ?? '';
    $correo = $_POST['Correo'] ?? '';

    if (!$originalRUC || !$newRUC) {
        echo json_encode(["status" => "Error", "message" => "RUC faltante"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE Proveedores SET RUC=?, Nombre=?, Direccion=?, Telefono=?, Correo=? WHERE RUC=?");
    $stmt->bind_param("ssssss", $newRUC, $nombre, $direccion, $telefono, $correo, $originalRUC);
    $success = $stmt->execute();

    echo json_encode(["status" => $success ? "OK" : "Error", "message" => $stmt->error]);
    exit;
}

 elseif ($action === 'add') {
    $ruc = $_POST['RUC'] ?? '';
    $nombre = $_POST['Nombre'] ?? '';
    $direccion = $_POST['Direccion'] ?? '';
    $telefono = $_POST['Telefono'] ?? '';
    $correo = $_POST['Correo'] ?? '';

    if (empty($ruc)) {
        echo json_encode(["error" => "RUC faltante"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Proveedores (RUC, Nombre, Direccion, Telefono, Correo, FechaRegistro) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $ruc, $nombre, $direccion, $telefono, $correo);
    $success = $stmt->execute();

if (!$success) {
    echo json_encode([
        "status" => "Error",
        "message" => $stmt->error  // Aquí verás qué falló
    ]);
} else {
    echo json_encode(["status" => "OK"]);
}
exit;

}

elseif ($action === 'delete') {
    $ruc = $_POST['RUC'] ?? null;
    if (!$ruc) {
        echo json_encode(["status" => "Error", "message" => "RUC faltante"]);
        exit;
    }

    // Verificar si tiene productos asociados
    $stmtCheckProducts = $conn->prepare("SELECT COUNT(*) as count FROM productos WHERE RUC = ?");
    $stmtCheckProducts->bind_param("s", $ruc);
    $stmtCheckProducts->execute();
    $resultProducts = $stmtCheckProducts->get_result()->fetch_assoc();

    if ($resultProducts['count'] > 0) {
        echo json_encode(["status" => "Error", "message" => "No se puede eliminar: el proveedor tiene productos asociados"]);
        exit;
    }

    // Verificar si tiene órdenes de compra asociadas
    $stmtCheckOrders = $conn->prepare("SELECT COUNT(*) as count FROM ordenescompra WHERE RUCProveedor = ?");
    $stmtCheckOrders->bind_param("s", $ruc);
    $stmtCheckOrders->execute();
    $resultOrders = $stmtCheckOrders->get_result()->fetch_assoc();

    if ($resultOrders['count'] > 0) {
        echo json_encode(["status" => "Error", "message" => "No se puede eliminar: el proveedor tiene órdenes de compra asociadas"]);
        exit;
    }

    // Si no hay dependencias, eliminar
    $stmt = $conn->prepare("DELETE FROM Proveedores WHERE RUC = ?");
    $stmt->bind_param("s", $ruc);
    $success = $stmt->execute();

    echo json_encode([
        "status" => $success ? "OK" : "Error",
        "message" => $success ? "Proveedor eliminado correctamente" : $stmt->error
    ]);
    exit;
}


echo json_encode(["error" => "Acción no válida"]);
$conn->close();
?>
