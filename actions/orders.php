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
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
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
            JOIN Proveedores p ON o.IDProveedor = p.IDProveedor
            JOIN Usuarios u ON o.IDUsuario = u.IDUsuario";
    
    if (!empty($filter)) {
        $sql .= " WHERE p.Nombre LIKE '%" . $conn->real_escape_string($filter) . "%'";
    }
    
    $result = $conn->query($sql);
    
    $orders = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    
    echo json_encode($orders);
}

function getProviders($conn) {
    $sql = "SELECT IDProveedor, Nombre FROM Proveedores";
    $result = $conn->query($sql);
    
    $providers = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }
    }
    
    echo json_encode($providers);
}

function getProducts($conn) {
    $providerId = $_GET['providerId'] ?? 0;
    
    if (!$providerId) {
        echo json_encode([]);
        return;
    }
    
    $sql = "SELECT IDProducto, Nombre, Precio, Moneda 
            FROM Productos 
            WHERE IDProveedor = " . intval($providerId);
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    echo json_encode($products);
}

function saveOrder($conn) {
    header('Content-Type: application/json'); // Asegurar cabecera JSON
    
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        return;
    }
    
    if (empty($data['providerId']) || empty($data['userId']) || empty($data['products'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    // Validar consistencia de monedas
    $monedaReferencia = $data['moneda'] ?? 'USD';
    foreach ($data['products'] as $product) {
        if (($product['moneda'] ?? 'USD') !== $monedaReferencia) {
            echo json_encode(['success' => false, 'message' => 'No se pueden mezclar monedas en una misma orden']);
            return;
        }
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Insertar la orden
        $sql = "INSERT INTO OrdenesCompra (FechaOrden, Estado, IDProveedor, IDUsuario, Moneda) 
                VALUES (CURDATE(), ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $status = $data['status'] ?? 'Pendiente';
        $stmt->bind_param("siis", $status, $data['providerId'], $data['userId'], $monedaReferencia);
        $stmt->execute();
        
        $orderId = $conn->insert_id;
        
        // Insertar los detalles de la orden
        foreach ($data['products'] as $product) {
            $sql = "INSERT INTO DetallesOrdenesCompra (IDOrden, IDProducto, Cantidad, PrecioUnitario)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $orderId, $product['id'], $product['quantity'], $product['price']);
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        echo json_encode(['success' => true, 'orderId' => $orderId]);
    } catch (Exception $e) {
        // Revertir en caso de error
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
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Eliminar detalles primero
        $sql = "DELETE FROM DetallesOrdenesCompra WHERE IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // Luego eliminar la orden
        $sql = "DELETE FROM OrdenesCompra WHERE IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // Confirmar transacción
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la orden: ' . $e->getMessage()]);
    }
}

function printOrder($conn) {
    // Implementación para generar PDF (pendiente)
    echo json_encode(['success' => false, 'message' => 'Función de impresión no implementada']);
}

function getOrderDetails($conn) {
    $orderId = $_GET['orderId'] ?? 0;
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido']);
        return;
    }
    
    try {
        // Obtener información básica de la orden
        $sql = "SELECT o.*, p.Nombre AS ProveedorNombre 
                FROM OrdenesCompra o
                JOIN Proveedores p ON o.IDProveedor = p.IDProveedor
                WHERE o.IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            throw new Exception("Orden no encontrada");
        }
        
        // Obtener productos de la orden
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
    
    if (empty($data['orderId']) || empty($data['providerId']) || empty($data['status']) || empty($data['products'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    // Validar consistencia de monedas
    $monedaReferencia = $data['moneda'] ?? 'USD';
    foreach ($data['products'] as $product) {
        if (($product['moneda'] ?? 'USD') !== $monedaReferencia) {
            echo json_encode(['success' => false, 'message' => 'No se pueden mezclar monedas en una misma orden']);
            return;
        }
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        $orderId = $data['orderId'];
        
        // 1. Eliminar los detalles actuales
        $sql = "DELETE FROM DetallesOrdenesCompra WHERE IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // 2. Actualizar la orden principal
        $sql = "UPDATE OrdenesCompra SET IDProveedor = ?, Estado = ? WHERE IDOrden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $data['providerId'], $data['status'], $orderId);
        $stmt->execute();
        
        // 3. Insertar los nuevos detalles
        foreach ($data['products'] as $product) {
            $sql = "INSERT INTO DetallesOrdenesCompra (IDOrden, IDProducto, Cantidad, PrecioUnitario)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $orderId, $product['id'], $product['quantity'], $product['price']);
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        echo json_encode(['success' => true, 'orderId' => $orderId]);
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la orden: ' . $e->getMessage()]);
    }
}

function getFullOrder($conn) {
    $orderId = $_GET['orderId'] ?? 0;
    
    try {
        // 1. Datos de la orden
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
        
        // 2. Datos del proveedor
        $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE IDProveedor = ?");
        $stmt->bind_param("i", $order['IDProveedor']);
        $stmt->execute();
        $provider = $stmt->get_result()->fetch_assoc();
        
        // 3. Productos de la orden - Agrupados por IDProducto y PrecioUnitario
        $stmt = $conn->prepare("
            SELECT 
                d.IDProducto, 
                p.Nombre, 
                SUM(d.Cantidad) AS Cantidad, 
                d.PrecioUnitario,
                p.Moneda
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