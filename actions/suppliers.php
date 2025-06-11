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
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE IDProveedor = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
    exit;

} elseif ($action === 'update') {
    $id = $_POST['IDProveedor'] ?? null;
    $nombre = $_POST['Nombre'] ?? '';
    $direccion = $_POST['Direccion'] ?? '';
    $telefono = $_POST['Telefono'] ?? '';
    $correo = $_POST['Correo'] ?? '';

    if (!$id) {
        echo json_encode(["error" => "ID faltante"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE Proveedores SET Nombre=?, Direccion=?, Telefono=?, Correo=? WHERE IDProveedor=?");
    $stmt->bind_param("ssssi", $nombre, $direccion, $telefono, $correo, $id);
    $success = $stmt->execute();
    echo json_encode(["status" => $success ? "OK" : "Error"]);
    exit;

} elseif ($action === 'add') {
    $nombre = $_POST['Nombre'] ?? '';
    $direccion = $_POST['Direccion'] ?? '';
    $telefono = $_POST['Telefono'] ?? '';
    $correo = $_POST['Correo'] ?? '';

    $stmt = $conn->prepare("INSERT INTO Proveedores (Nombre, Direccion, Telefono, Correo, FechaRegistro) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $nombre, $direccion, $telefono, $correo);
    $success = $stmt->execute();
    echo json_encode(["status" => $success ? "OK" : "Error"]);
    exit;

} elseif ($action === 'delete') {
    $id = $_POST['IDProveedor'] ?? null;
    if (!$id) {
        echo json_encode(["error" => "ID faltante"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Proveedores WHERE IDProveedor = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    echo json_encode(["status" => $success ? "OK" : "Error"]);
    exit;
}

echo json_encode(["error" => "Acción no válida"]);
$conn->close();
?>
