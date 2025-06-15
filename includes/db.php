<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "OrdenesCompra";

// Conectar al servidor MySQL sin seleccionar base aún
$conn = new mysqli($host, $user, $password);

// Verifica conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Crear la base si no existe
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");

// Selecciona la base
$conn->select_db($dbname);

// Crear tablas si no existen
$conn->query("
CREATE TABLE IF NOT EXISTS Usuarios (
    IDUsuario INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Correo VARCHAR(100) NOT NULL,
    Rol VARCHAR(50) NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    token VARCHAR(255) DEFAULT NULL
)");

$conn->query("
CREATE TABLE IF NOT EXISTS Proveedores (
    RUC VARCHAR(20) PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Direccion VARCHAR(200) NOT NULL,
    Telefono VARCHAR(20) NOT NULL,
    Correo VARCHAR(100) NOT NULL,
    FechaRegistro DATE NOT NULL
)");

$conn->query("
CREATE TABLE IF NOT EXISTS Productos (
    IDProducto INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    Moneda ENUM('PEN', 'USD') NOT NULL DEFAULT 'PEN',
    RUC VARCHAR(20),
    FOREIGN KEY (RUC) REFERENCES Proveedores(RUC)
)");


$conn->query("
CREATE TABLE IF NOT EXISTS Inventario (
    IDInventario INT AUTO_INCREMENT PRIMARY KEY,
    IDProducto INT NOT NULL,
    StockActual INT NOT NULL,
    StockMinimo INT NOT NULL,
    FechaUltimaActualizacion DATE NOT NULL,
    FOREIGN KEY (IDProducto) REFERENCES Productos(IDProducto)
)");

$conn->query("
CREATE TABLE IF NOT EXISTS InfoEmpresa (
    RUC VARCHAR(20) PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    Correo VARCHAR(100) NOT NULL,
    Direccion VARCHAR(200) NOT NULL,
    Celular VARCHAR(20) NOT NULL
);");

$conn->query("
CREATE TABLE IF NOT EXISTS OrdenesCompra (
    IDOrden INT AUTO_INCREMENT PRIMARY KEY,
    FechaOrden DATE NOT NULL,
    Estado ENUM('Pendiente', 'Entregada', 'Cancelada', 'Rechazada') NOT NULL,
    Moneda VARCHAR(3) NOT NULL DEFAULT 'PEN',
    RUCProveedor VARCHAR(20),
    IDUsuario INT,
    FOREIGN KEY (RUCProveedor) REFERENCES Proveedores(RUC),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(IDUsuario)
)");

$conn->query("
CREATE TABLE IF NOT EXISTS DetallesOrdenesCompra (
    IDDetalle INT AUTO_INCREMENT PRIMARY KEY,
    IDOrden INT,
    IDProducto INT NOT NULL,
    Cantidad INT NOT NULL,
    PrecioUnitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (IDOrden) REFERENCES OrdenesCompra(IDOrden),
    FOREIGN KEY (IDProducto) REFERENCES Productos(IDProducto)
)");
?>