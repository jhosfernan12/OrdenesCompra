<?php
header('Content-Type: application/json');

// Configuración de la base de datos
require_once('../includes/db.php');

// Obtener acción a realizar
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_inventory':
        getInventory();
        break;
    case 'get_inventory_item':
        getInventoryItem();
        break;
    case 'get_products':
        getProducts();
        break;
    case 'add_inventory':
        addInventory();
        break;
    case 'update_inventory':
        updateInventory();
        break;
    case 'delete_inventory':
        deleteInventory();
        break;
    case 'update_inventory_existing':
        updateInventoryExisting();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function updateInventoryExisting() {
    global $conn;
    
    $productId = intval($_POST['IDProducto'] ?? 0);
    $additionalStock = intval($_POST['StockActual'] ?? 0);
    $newMinStock = intval($_POST['StockMinimo'] ?? 0);
    $currentDate = date('Y-m-d');
    
    // Primero obtenemos el stock actual para devolver el nuevo total
    $getQuery = "SELECT StockActual FROM Inventario WHERE IDProducto = ?";
    $getStmt = $conn->prepare($getQuery);
    $getStmt->bind_param("i", $productId);
    $getStmt->execute();
    $currentStock = $getStmt->get_result()->fetch_assoc()['StockActual'];
    $getStmt->close();
    
    $query = "UPDATE Inventario 
              SET StockActual = StockActual + ?, 
                  StockMinimo = ?,
                  FechaUltimaActualizacion = ?
              WHERE IDProducto = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisi", $additionalStock, $newMinStock, $currentDate, $productId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Stock actualizado correctamente',
            'newStock' => $currentStock + $additionalStock
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function getInventory() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    $filter = $_GET['stock'] ?? 'all';
    
    $query = "SELECT i.*, p.Nombre AS NombreProducto 
              FROM Inventario i
              JOIN Productos p ON i.IDProducto = p.IDProducto
              WHERE 1=1";
    
    // Aplicar filtro de búsqueda
    if (!empty($search)) {
        $query .= " AND p.Nombre LIKE '%" . $conn->real_escape_string($search) . "%'";
    }
    
    // Aplicar filtro de stock
    switch ($filter) {
        case 'low':
            $query .= " AND i.StockActual < i.StockMinimo AND i.StockActual > 0";
            break;
        case 'critical':
            $query .= " AND i.StockActual <= 0";
            break;
    }
    
    $query .= " ORDER BY i.StockActual ASC, p.Nombre ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener inventario: ' . $conn->error]);
        return;
    }
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }
    
    echo json_encode($inventory);
}

function getInventoryItem() {
    global $conn;
    
    $id = $_GET['id'] ?? 0;
    
    $query = "SELECT i.*, p.Nombre AS NombreProducto 
              FROM Inventario i
              JOIN Productos p ON i.IDProducto = p.IDProducto
              WHERE i.IDInventario = " . intval($id);
    
    $result = $conn->query($query);
    
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        return;
    }
    
    echo json_encode($result->fetch_assoc());
}

function getProducts() {
    global $conn;
    
    $query = "SELECT p.* FROM Productos p ORDER BY p.Nombre";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener productos: ' . $conn->error]);
        return;
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode($products);
}

function addInventory() {
    global $conn;
    
    $productId = intval($_POST['IDProducto'] ?? 0);
    $currentStock = intval($_POST['StockActual'] ?? 0);
    $minStock = intval($_POST['StockMinimo'] ?? 0);
    $currentDate = date('Y-m-d');

    // Consulta mejorada para obtener el nombre del producto
    $checkQuery = "SELECT i.IDInventario, i.StockActual, p.Nombre 
                  FROM Inventario i
                  JOIN Productos p ON i.IDProducto = p.IDProducto
                  WHERE i.IDProducto = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $row = $checkResult->fetch_assoc();
        $productName = htmlspecialchars($row['Nombre']);
        
        echo json_encode([
            'success' => false, 
            'message' => "El producto \"$productName\" ya existe en el inventario con {$row['StockActual']} unidades. ¿Deseas actualizar el stock en lugar de añadirlo de nuevo?",
            'existing' => true,
            'currentStock' => $row['StockActual'],
            'productName' => $productName
        ]);
        $checkStmt->close();
        return;
    }

    $insertQuery = "INSERT INTO Inventario (IDProducto, StockActual, StockMinimo, FechaUltimaActualizacion) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iiis", $productId, $currentStock, $minStock, $currentDate);
    
    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto añadido al inventario']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al añadir: ' . $insertStmt->error]);
    }
    $insertStmt->close();
}

function updateInventory() {
    global $conn;
    
    $id = $_POST['IDInventario'] ?? 0;
    $currentStock = $_POST['StockActual'] ?? 0;
    $minStock = $_POST['StockMinimo'] ?? 0;
    $currentDate = date('Y-m-d');
    
    $query = "UPDATE Inventario 
              SET StockActual = ?, StockMinimo = ?, FechaUltimaActualizacion = ?
              WHERE IDInventario = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisi", $currentStock, $minStock, $currentDate, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Inventario actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function deleteInventory() {
    global $conn;
    
    // Obtener el ID del inventario a eliminar
    $id = intval($_POST['IDInventario'] ?? 0);
    
    // Primero obtenemos información del producto para el mensaje de confirmación
    $infoQuery = "SELECT p.Nombre 
                 FROM Inventario i
                 JOIN Productos p ON i.IDProducto = p.IDProducto
                 WHERE i.IDInventario = ?";
    
    $infoStmt = $conn->prepare($infoQuery);
    $infoStmt->bind_param("i", $id);
    $infoStmt->execute();
    $productInfo = $infoStmt->get_result()->fetch_assoc();
    $infoStmt->close();
    
    // Consulta para eliminar el registro
    $deleteQuery = "DELETE FROM Inventario WHERE IDInventario = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $id);
    
    if ($deleteStmt->execute()) {
        // Verificamos si se afectó alguna fila
        if ($deleteStmt->affected_rows > 0) {
            $productName = $productInfo ? htmlspecialchars($productInfo['Nombre']) : 'Producto';
            echo json_encode([
                'success' => true, 
                'message' => "$productName eliminado del inventario correctamente"
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se encontró el registro a eliminar'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar: ' . $deleteStmt->error
        ]);
    }
    
    $deleteStmt->close();
}
?>