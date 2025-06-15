<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Comprador'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

include('../includes/db.php');
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_GET['action'] ?? '';

    if ($action === 'list') {
        $stmt = $pdo->query("
            SELECT P.IDProducto AS id, P.Nombre AS name, P.Descripcion AS description,
                   P.Precio AS price, P.Moneda AS currency, P.RUC AS provider_id,
                   PR.Nombre AS provider_name
            FROM productos P
            JOIN proveedores PR ON P.RUC = PR.RUC
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'products' => $products]);
        exit;
    }

    if ($action === 'providers') {
        $stmt = $pdo->query("SELECT RUC AS id, Nombre AS name FROM proveedores");
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'providers' => $providers]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $price = $input['price'] ?? '';
        $provider_id = $input['provider_id'] ?? null;
        $currency = strtoupper(trim($input['currency'] ?? 'USD'));

        if (!$name || !$price || !$provider_id || !in_array($currency, ['USD', 'PEN'])) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o moneda inválida']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO productos (Nombre, Descripcion, Precio, Moneda, RUC) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $currency, $provider_id]);

        echo json_encode(['status' => 'ok', 'message' => 'Producto creado correctamente']);
        exit;
    }

    if ($action === 'update') {
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $price = $input['price'] ?? '';
        $provider_id = $input['provider_id'] ?? null;
        $currency = strtoupper(trim($input['currency'] ?? 'USD'));

        if (!$id || !$name || !$price || !$provider_id || !in_array($currency, ['USD', 'PEN'])) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o moneda inválida']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE productos SET Nombre = ?, Descripcion = ?, Precio = ?, Moneda = ?, RUC = ? WHERE IDProducto = ?");
        $stmt->execute([$name, $description, $price, $currency, $provider_id, $id]);

        echo json_encode(['status' => 'ok', 'message' => 'Producto actualizado correctamente']);
        exit;
    }

    if ($action === 'delete') {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID requerido']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM productos WHERE IDProducto = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'ok', 'message' => 'Producto eliminado correctamente']);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar el producto porque está en uso en el inventario y/o en órdenes de compra']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error DB: ' . $e->getMessage()]);
            }
            exit;
        }
    }


    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error DB: ' . $e->getMessage()]);
}
