<?php
require_once __DIR__.'/../includes/fpdf/fpdf.php';

// Configurar conexión a BD
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "OrdenesCompra";

// Obtener ID de orden
$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

if ($orderId <= 0) {
    die('ID de orden no válido');
}

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset utf8 para la conexión
$conn->set_charset("utf8");

// Obtener datos
$orderData = getOrderData($conn, $orderId);
$conn->close();

// Crear PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Configurar márgenes
$pdf->SetMargins(15, 15, 15);

// --- LOGO ---
$logoPath = __DIR__.'/../UI/assets/logo.png';
if(file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 55); // Ancho 55mm
    $pdf->SetY(10); // Asegura posición del texto
} else {
    $pdf->SetY(20);
}

// --- TÍTULO EN ESQUINA DERECHA ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(135, 10);
$pdf->Cell(60, 20, utf8_decode('ORDEN DE COMPRA N°'.str_pad($orderId, 4, '0', STR_PAD_LEFT)), 1, 0, 'C', false);

// Posicionarse debajo del logo o título
$pdf->SetY(30);

// --- INFORMACIÓN BÁSICA ---
$pdf->SetFont('Arial', '', 10);
$pdf->Ln(10);
$pdf->Cell(40, 7, utf8_decode('Fecha:'), 0, 0);
$pdf->Cell(0, 7, $orderData['order']['FechaOrden'], 0, 1);
$pdf->Cell(40, 7, utf8_decode('Estado:'), 0, 0);
$pdf->Cell(0, 7, utf8_decode($orderData['order']['Estado']), 0, 1);
$pdf->Ln(8);

// --- INFORMACIÓN PROVEEDOR ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('PROVEEDOR'), 0, 1);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(0, 1, '', 'B', 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(30, 7, utf8_decode('Nombre:'), 0, 0);
$pdf->Cell(0, 7, utf8_decode($orderData['provider']['Nombre']), 0, 1);
$pdf->Cell(30, 7, utf8_decode('Dirección:'), 0, 0);
$pdf->Cell(0, 7, utf8_decode($orderData['provider']['Direccion']), 0, 1);
$pdf->Cell(30, 7, utf8_decode('Teléfono:'), 0, 0);
$pdf->Cell(0, 7, $orderData['provider']['Telefono'], 0, 1);
$pdf->Cell(30, 7, utf8_decode('Email:'), 0, 0);
$pdf->Cell(0, 7, $orderData['provider']['Correo'], 0, 1);
$pdf->Ln(10);

// --- TABLA DE PRODUCTOS ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('DETALLE DE PRODUCTOS'), 0, 1);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(0, 1, '', 'B', 1);
$pdf->Ln(5);

// Encabezados de tabla
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(15, 10, utf8_decode('Cód.'), 1, 0, 'C', true);
$pdf->Cell(75, 10, utf8_decode('Descripción'), 1, 0, 'C', true);
$pdf->Cell(25, 10, utf8_decode('Cantidad'), 1, 0, 'C', true);
$pdf->Cell(35, 10, utf8_decode('P. Unitario'), 1, 0, 'C', true);
$pdf->Cell(35, 10, utf8_decode('Total'), 1, 1, 'C', true);

// Agrupar productos por IDProducto
$productosAgrupados = [];

foreach ($orderData['products'] as $producto) {
    $id = $producto['IDProducto'];
    
    if (!isset($productosAgrupados[$id])) {
        $productosAgrupados[$id] = [
            'IDProducto' => $id,
            'Nombre' => $producto['Nombre'],
            'Cantidad' => $producto['Cantidad'],
            'PrecioUnitario' => $producto['PrecioUnitario'],
            'Moneda' => $producto['Moneda']
        ];
    } else {
        $productosAgrupados[$id]['Cantidad'] += $producto['Cantidad'];
    }
}

// Mostrar productos agrupados
$pdf->SetFont('Arial', '', 9);
$total = 0;

foreach ($productosAgrupados as $producto) {
    $subtotal = $producto['Cantidad'] * $producto['PrecioUnitario'];
    $total += $subtotal;

    $pdf->Cell(15, 8, $producto['IDProducto'], 1, 0, 'C');
    $pdf->Cell(75, 8, utf8_decode($producto['Nombre']), 1, 0);
    $pdf->Cell(25, 8, $producto['Cantidad'], 1, 0, 'C');
    $pdf->Cell(35, 8, number_format($producto['PrecioUnitario'], 2).' '.$producto['Moneda'], 1, 0, 'R');
    $pdf->Cell(35, 8, number_format($subtotal, 2).' '.$producto['Moneda'], 1, 1, 'R');
}

// Total general
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(150, 8, utf8_decode('TOTAL:'), 1, 0, 'R', true);
$pdf->Cell(35, 8, number_format($total, 2).' '.$orderData['order']['Moneda'], 1, 1, 'R', true);

// Pie de página
$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, utf8_decode('Documento generado el ').date('d/m/Y H:i'), 0, 1, 'R');

// Salida del PDF
$pdf->Output('I','Orden_Compra_'.$orderId.'.pdf');

// --- FUNCIÓN PARA OBTENER DATOS DE LA ORDEN ---
function getOrderData($conn, $orderId) {
    // Datos de la orden
    $stmt = $conn->prepare("SELECT o.*, u.Nombre AS UsuarioNombre FROM OrdenesCompra o JOIN Usuarios u ON o.IDUsuario = u.IDUsuario WHERE o.IDOrden = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) die('Orden no encontrada');

    // Datos del proveedor
    $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE IDProveedor = ?");
    $stmt->bind_param("i", $order['IDProveedor']);
    $stmt->execute();
    $provider = $stmt->get_result()->fetch_assoc();
    if (!$provider) die('Proveedor no encontrado');

    // Productos de la orden
    $stmt = $conn->prepare("
        SELECT d.*, p.Nombre, p.Moneda 
        FROM DetallesOrdenesCompra d
        JOIN Productos p ON d.IDProducto = p.IDProducto
        WHERE d.IDOrden = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'order' => $order,
        'provider' => $provider,
        'products' => $products
    ];
}
?>
