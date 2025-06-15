<?php
header('Content-Type: application/json');

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "OrdenesCompra";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Conexión fallida: ' . $conn->connect_error]));
}

// Obtener la acción solicitada
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getOrders':
        getOrders($conn);
        break;
    case 'getProviders':
        getProviders($conn);
        break;
    case 'getProducts':
        getProducts($conn);
        break;
    case 'saveOrder':
        saveOrder($conn);
        break;
    case 'deleteOrder':
        deleteOrder($conn);
        break;
    case 'printOrder':
        printOrder($conn);
        break;
    case 'getOrderDetails':
        getOrderDetails($conn);
        break;
    case 'updateOrder':
        updateOrder($conn);
        break;
    case 'getFullOrder':
        getFullOrder($conn);
        break;
    case 'getCurrentUser':
        getCurrentUser();
        break;
    case 'getMonthlyOrders':
        getMonthlyOrders($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function getMonthlyOrders($conn) {
    $sql = "SELECT 
                MONTH(FechaOrden) AS mes, 
                COUNT(*) AS cantidad
            FROM OrdenesCompra
            WHERE YEAR(FechaOrden) = YEAR(CURDATE())
            GROUP BY mes
            ORDER BY mes";

    $result = $conn->query($sql);
    $data = array_fill(1, 12, 0);

    while ($row = $result->fetch_assoc()) {
        $data[intval($row['mes'])] = intval($row['cantidad']);
    }

    echo json_encode([
        'success' => true,
        'labels' => [
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        ],
        'data' => array_values($data)
    ]);
}

function getCurrentUser() {
    session_start();
    if (isset($_SESSION['user'])) {
        echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay usuario en sesión']);
    }
}

function getOrders($conn) {
    $filter = $_GET['filter'] ?? '';
    $sql = "SELECT o.IDOrden, o.FechaOrden, o.Estado, 
                   p.Nombre AS Proveedor, u.Nombre AS Solicitante
            FROM OrdenesCompra o
            JOIN Proveedores p ON o.RUCProveedor = p.RUC
            JOIN Usuarios u ON o.IDUsuario = u.IDUsuario";

    if (!empty($filter)) {
        $sql .= " WHERE p.Nombre LIKE '%" . $conn->real_escape_string($filter) . "%'";
    }

    $result = $conn->query($sql);
    $orders = [];
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode($orders);
}

function getProviders($conn) {
    $sql = "SELECT RUC, Nombre FROM Proveedores";
    $result = $conn->query($sql);
    $providers = [];
    while($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
    echo json_encode($providers);
}

function getProducts($conn) {
    $ruc = $_GET['ruc'] ?? 0;
    if (!$ruc) {
        echo json_encode([]);
        return;
    }

    $sql = "SELECT IDProducto, Nombre, Precio, Moneda 
            FROM Productos 
            WHERE RUC = " . intval($ruc);
    $result = $conn->query($sql);
    $products = [];
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);
}

function saveOrder($conn) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        return;
    }

    if (empty($data['ruc']) || empty($data['userId']) || empty($data['products'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }

    $monedaReferencia = $data['moneda'] ?? 'USD';
    foreach ($data['products'] as $product) {
        if (($product['moneda'] ?? 'USD') !== $monedaReferencia) {
            echo json_encode(['success' => false, 'message' => 'No se pueden mezclar monedas en una misma orden']);
            return;
        }
    }

    $conn->begin_transaction();

    try {
        $status = $data['status'] ?? 'Pendiente';
        $sql = "INSERT INTO OrdenesCompra (FechaOrden, Estado, RUCProveedor, IDUsuario, Moneda)
                VALUES (CURDATE(), ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $status, $data['ruc'], $data['userId'], $monedaReferencia);
        $stmt->execute();
        $orderId = $conn->insert_id;

        foreach ($data['products'] as $product) {
            $sql = "INSERT INTO DetallesOrdenesCompra (IDOrden, IDProducto, Cantidad, PrecioUnitario)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $orderId, $product['id'], $product['quantity'], $product['price']);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'orderId' => $orderId]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al guardar la orden: ' . $e->getMessage()]);
    }
}

function deleteOrder($conn) {
    $orderId = $_GET['orderId'] ?? 0;

    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido']);
        return;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("DELETE FROM DetallesOrdenesCompra WHERE IDOrden = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM OrdenesCompra WHERE IDOrden = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la orden: ' . $e->getMessage()]);
    }
}

function printOrder($conn) {
    echo json_encode(['success' => false, 'message' => 'Función de impresión no implementada']);
}

function getOrderDetails($conn) {
    $orderId = $_GET['orderId'] ?? 0;
    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido']);
        return;
    }

    try {
        $sql = "SELECT o.*, p.Nombre AS ProveedorNombre 
                FROM OrdenesCompra o
                JOIN Proveedores p ON o.RUCProveedor = p.RUC
                WHERE o.IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            throw new Exception("Orden no encontrada");
        }

        $sql = "SELECT d.*, p.Nombre 
                FROM DetallesOrdenesCompra d
                JOIN Productos p ON d.IDProducto = p.IDProducto
                WHERE d.IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'products' => $products
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateOrder($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validaciones iniciales
    if (empty($data['orderId']) || empty($data['status']) || empty($data['products'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }

    // Verificar moneda consistente
    $monedaReferencia = $data['moneda'] ?? 'USD';
    foreach ($data['products'] as $product) {
        if (($product['moneda'] ?? 'USD') !== $monedaReferencia) {
            echo json_encode(['success' => false, 'message' => 'No se pueden mezclar monedas en una misma orden']);
            return;
        }
    }

    $conn->begin_transaction();

    try {
        $orderId = $data['orderId'];

        // 1. Eliminar detalles existentes
        $stmt = $conn->prepare("DELETE FROM DetallesOrdenesCompra WHERE IDOrden = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        // 2. Actualizar SOLO el estado (no el RUC del proveedor)
        $stmt = $conn->prepare("UPDATE OrdenesCompra SET Estado = ? WHERE IDOrden = ?");
        $stmt->bind_param("si", $data['status'], $orderId);
        $stmt->execute();

        // 3. Insertar nuevos detalles
        foreach ($data['products'] as $product) {
            $stmt = $conn->prepare("INSERT INTO DetallesOrdenesCompra (IDOrden, IDProducto, Cantidad, PrecioUnitario)
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $orderId, $product['id'], $product['quantity'], $product['price']);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'orderId' => $orderId]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la orden: ' . $e->getMessage()]);
    }
}

function getFullOrder($conn) {
    $orderId = $_GET['orderId'] ?? 0;

    try {
        $stmt = $conn->prepare("
            SELECT o.*, u.Nombre AS UsuarioNombre, u.Correo AS UsuarioCorreo 
            FROM OrdenesCompra o
            JOIN Usuarios u ON o.IDUsuario = u.IDUsuario
            WHERE o.IDOrden = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) throw new Exception("Orden no encontrada");

        $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE RUC = ?");
        $stmt->bind_param("i", $order['RUCProveedor']);
        $stmt->execute();
        $provider = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("
            SELECT d.IDProducto, p.Nombre, SUM(d.Cantidad) AS Cantidad, 
                   d.PrecioUnitario, p.Moneda
            FROM DetallesOrdenesCompra d
            JOIN Productos p ON d.IDProducto = p.IDProducto
            WHERE d.IDOrden = ?
            GROUP BY d.IDProducto, d.PrecioUnitario
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'provider' => $provider,
            'user' => [
                'Nombre' => $order['UsuarioNombre'],
                'Correo' => $order['UsuarioCorreo']
            ],
            'products' => $products
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>
