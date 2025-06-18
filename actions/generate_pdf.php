<?php

require_once __DIR__.'/../includes/fpdf/fpdf.php';
if (isset($_GET['check']) && $_GET['check'] == '1') {
    header('Content-Type: application/json');

    $conn = new mysqli("localhost", "root", "", "OrdenesCompra");
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión']);
        exit;
    }
    $conn->set_charset("utf8");

    $orderId = intval($_GET['orderId'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido']);
        exit;
    }

    $res = $conn->query("SELECT * FROM InfoEmpresa LIMIT 1");
    $infoEmpresa = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : [];

    $stmt = $conn->prepare("SELECT * FROM OrdenesCompra WHERE IDOrden = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    $provider = null;
    if ($order) {
        $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE RUC = ?");
        $stmt->bind_param("s", $order['RUCProveedor']);
        $stmt->execute();
        $provider = $stmt->get_result()->fetch_assoc();
    }

    $conn->close();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
    } elseif (empty($infoEmpresa['RUC'])) {
        echo json_encode(['success' => false, 'message' => 'Debe registrar el RUC en InfoEmpresa']);
    } elseif (!$provider) {
        echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}
// Configuración de la base de datos
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
$conn->set_charset("utf8");

// Obtener datos de la orden
$orderData = getOrderData($conn, $orderId);

// Obtener datos de la empresa
$infoEmpresa = [];
$result = $conn->query("SELECT * FROM InfoEmpresa LIMIT 1");
if ($result && $result->num_rows > 0) {
    $infoEmpresa = $result->fetch_assoc();
}
$conn->close();

if (empty($infoEmpresa) || empty($infoEmpresa['RUC'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró información válida de la empresa. Por favor registre el RUC en InfoEmpresa.'
    ]);
    exit;
}

// Obtener color personalizado desde la URL
$hexColor = isset($_GET['color']) ? $_GET['color'] : '6b5b95';

// Eliminar # si existe y validar formato
$hexColor = ltrim($hexColor, '#');
if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hexColor)) {
    $hexColor = '6b5b95'; // Valor por defecto si no es válido
}

// Función para convertir HEX a RGB
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    return [$r, $g, $b];
}



// Configurar colores para el PDF
$colorPrimario = hexToRgb($hexColor);
$colorFondoClaro = [
    min($colorPrimario[0] + 85, 255),
    min($colorPrimario[1] + 85, 255),
    min($colorPrimario[2] + 85, 255),
];


// Clase personalizada de PDF
class PDF extends FPDF {
    public $logoPath;
    public $colorPrimario = [107, 91, 149];
    public $colorFondoClaro = [236, 232, 241];
    public $orderId;
    public $empresaRUC;
    public $infoEmpresa = [];

    function Header() {
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 15, 10, 45, 0); 
            $this->SetY(10);
        } else {
            $this->SetY(20);
        }

        $this->SetFont('Arial', 'B', 14);
        $this->SetDrawColor(...$this->colorPrimario);
        $this->SetFillColor(...$this->colorFondoClaro);
         $this->SetTextColor(0, 0, 0);
        $this->SetXY(120, 10);
        $this->Cell(70, 10, utf8_decode('ORDEN DE COMPRA N°'.str_pad($this->orderId, 4, '0', STR_PAD_LEFT)), 1, 2, 'C', true);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(70, 10, utf8_decode('RUC: '.$this->empresaRUC), 1, 0, 'C', true);
        $this->Ln(25);
    }

    function Footer() {
        $this->SetY(-30);
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(100, 100, 100);

        if (!empty($this->infoEmpresa)) {
            $linea1 = "{$this->infoEmpresa['Nombre']} | RUC: {$this->infoEmpresa['RUC']}";
            $linea2 = "{$this->infoEmpresa['Direccion']} | {$this->infoEmpresa['Celular']} | {$this->infoEmpresa['Correo']}";
            $this->MultiCell(0, 5, utf8_decode($linea1."\n".$linea2), 0, 'C');
        }

        $this->Ln(2);
        $this->Cell(0, 5, utf8_decode('Página ').$this->PageNo().'/{nb}', 0, 0, 'L');
        $this->Cell(0, 5, utf8_decode('Creado el ').date('d/m/Y '), 0, 0, 'R');
    }
}


// Crear PDF
$pdf = new PDF('P', 'mm', 'A4');

$pdf->colorPrimario = $colorPrimario;
$pdf->colorFondoClaro = $colorFondoClaro;

$logoSubido = __DIR__.'/../UI/assets/logos/logo.png';
$logoPorDefecto = __DIR__.'/../UI/assets/logos/logodefault.png';



$pdf->logoPath = file_exists($logoSubido) ? $logoSubido : $logoPorDefecto;

$pdf->orderId = $orderId;
$pdf->empresaRUC = $infoEmpresa['RUC'];
$pdf->infoEmpresa = $infoEmpresa;
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetMargins(15, 15, 15);
$pdf->SetTextColor(0, 0, 0);

// Información básica
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 7, utf8_decode('Fecha:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, $orderData['order']['FechaOrden'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFont('Arial', '', 12);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 7, utf8_decode('Solicitante:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, utf8_decode($orderData['order']['UsuarioNombre']), 0, 1);
$pdf->Ln(8);

// Información del proveedor
$pdf->SetFont('Arial', 'B', 15);
$pdf->SetTextColor(...$pdf->colorPrimario);
$pdf->Cell(0, 8, utf8_decode('PROVEEDOR'), 0, 1);
$pdf->SetDrawColor(...$pdf->colorPrimario);
$pdf->Cell(0, 1, '', 'B', 1);
$pdf->Ln(5);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 7, utf8_decode('Nombre:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, utf8_decode($orderData['provider']['Nombre']), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 7, utf8_decode('RUC:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, $orderData['provider']['RUC'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 7, utf8_decode('Dirección:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, utf8_decode($orderData['provider']['Direccion']), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 7, utf8_decode('Teléfono:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, $orderData['provider']['Telefono'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 7, utf8_decode('Email:'), 0, 0);
$pdf->SetFont('Arial', '', 12);
$correoProveedor = !empty($orderData['provider']['Correo']) ? $orderData['provider']['Correo'] : $orderData['admin']['Correo'];
$pdf->Cell(0, 7, $correoProveedor, 0, 1);
$pdf->Ln(10);

// Texto introductorio
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 7, utf8_decode('Agradecemos atender la siguiente orden de compra:'), 0, 'L');
$pdf->Ln(5);

// Detalle de productos
$pdf->SetFont('Arial', 'B', 15);
$pdf->SetTextColor(...$pdf->colorPrimario);
$pdf->Cell(0, 8, utf8_decode('DETALLE DE PRODUCTOS'), 0, 1);
$pdf->SetDrawColor(...$pdf->colorPrimario);
$pdf->Cell(0, 1, '', 'B', 1);
$pdf->Ln(5);

// Encabezados
$pdf->SetFillColor(...$pdf->colorFondoClaro);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 13, utf8_decode('Nombre'), 1, 0, 'C', true);
$pdf->Cell(25, 13, utf8_decode('Cantidad'), 1, 0, 'C', true);
$pdf->Cell(35, 13, utf8_decode('P. Unitario'), 1, 0, 'C', true);
$pdf->Cell(40, 13, utf8_decode('Total'), 1, 1, 'C', true);

// Productos
$pdf->SetFont('Arial', '', 11);
$total = 0;
foreach ($orderData['products'] as $producto) {
    if ($pdf->GetY() > ($pdf->GetPageHeight() - 50)) {
        $pdf->AddPage();
        $pdf->Ln(15);
    }
    $subtotalItem = $producto['Cantidad'] * $producto['PrecioUnitario'];
    $total += $subtotalItem;

    $pdf->Cell(80, 10, utf8_decode($producto['Nombre']), 1, 0);
    $pdf->Cell(25, 10, $producto['Cantidad'], 1, 0, 'C');
    $pdf->Cell(35, 10, number_format($producto['PrecioUnitario'], 2).' '.$producto['Moneda'], 1, 0, 'R');
    $pdf->Cell(40, 10, number_format($subtotalItem, 2).' '.$producto['Moneda'], 1, 1, 'R');
}

// Calcular IGV y Total
$subtotal = $total;
$igv = round($subtotal * 0.18, 2);
$totalFinal = round($subtotal + $igv, 2);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(...$pdf->colorFondoClaro);

// Subtotal
$pdf->Cell(140, 8, utf8_decode('SUBTOTAL:'), 1, 0, 'R', true);
$pdf->Cell(40, 8, number_format($subtotal, 2).' '.$orderData['order']['Moneda'], 1, 1, 'R', true);

// IGV
$pdf->Cell(140, 8, utf8_decode('IGV (18%):'), 1, 0, 'R');
$pdf->Cell(40, 8, number_format($igv, 2).' '.$orderData['order']['Moneda'], 1, 1, 'R');

// Total final
$pdf->Cell(140, 8, utf8_decode('TOTAL '.$orderData['order']['Moneda'].':'), 1, 0, 'R', true);
$pdf->Cell(40, 8, number_format($totalFinal, 2).' '.$orderData['order']['Moneda'], 1, 1, 'R', true);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 7, utf8_decode('Saludos cordiales.
Atentamente,'), 0, 'L');
$pdf->Ln(5);

// Obtener el ancho total de la página menos los márgenes
$anchoPagina = $pdf->GetPageWidth();
$margenIzquierdo = $pdf->GetX();
$margenDerecho = $anchoPagina - $pdf->GetX();

// Definir ancho de la línea (por ejemplo 60 mm)
$anchoLinea = 60;

// Calcular posición X para centrar la línea
$xLinea = ($anchoPagina - $anchoLinea) / 2;

// Posición Y actual
$yLinea = $pdf->GetY();

// Dibujar línea horizontal para la firma
$pdf->Line($xLinea, $yLinea, $xLinea + $anchoLinea, $yLinea);




// Salida del PDF

$pdf->Output('I', 'Orden_Compra_N000'.$orderId.'.pdf');
ob_end_flush(); // Fin del buffer

// Función para obtener datos
function getOrderData($conn, $orderId) {
    // Datos de la orden
    $stmt = $conn->prepare("
        SELECT o.*, u.Nombre AS UsuarioNombre 
        FROM OrdenesCompra o
        JOIN Usuarios u ON o.IDUsuario = u.IDUsuario
        WHERE o.IDOrden = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) die('Orden no encontrada');

    // Proveedor
    $stmt = $conn->prepare("SELECT * FROM Proveedores WHERE RUC = ?");
    $stmt->bind_param("s", $order['RUCProveedor']);
    $stmt->execute();
    $provider = $stmt->get_result()->fetch_assoc();
    if (!$provider) die('Proveedor no encontrado con RUC: ' . $order['RUCProveedor']);

    // Admin de respaldo
    $adminCorreo = '';
    $res = $conn->query("SELECT Correo FROM Usuarios WHERE Rol = 'admin' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $adminCorreo = $res->fetch_assoc()['Correo'];
    }

    // Productos
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
        'products' => $products,
        'admin' => ['Correo' => $adminCorreo]
    ];
}
?>
