CREATE DATABASE IF NOT EXISTS OrdenesCompra;
USE OrdenesCompra;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS Usuarios (
    IDUsuario INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Correo VARCHAR(100) NOT NULL,
    Rol VARCHAR(50) NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    token VARCHAR(255) DEFAULT NULL
);

-- Tabla de Proveedores con RUC como PRIMARY KEY
CREATE TABLE IF NOT EXISTS Proveedores (
    RUC VARCHAR(20) PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Direccion VARCHAR(200) NOT NULL,
    Telefono VARCHAR(20) NOT NULL,
    Correo VARCHAR(100) NOT NULL,
    FechaRegistro DATE NOT NULL
);

-- Tabla de Productos
CREATE TABLE IF NOT EXISTS Productos (
    IDProducto INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    Moneda ENUM('PEN', 'USD') NOT NULL DEFAULT 'PEN',
    RUC VARCHAR(20),
    FOREIGN KEY (RUC) REFERENCES Proveedores(RUC)
);

-- Tabla de Inventario
CREATE TABLE IF NOT EXISTS Inventario (
    IDInventario INT AUTO_INCREMENT PRIMARY KEY,
    IDProducto INT NOT NULL,
    StockActual INT NOT NULL,
    StockMinimo INT NOT NULL,
    FechaUltimaActualizacion DATE NOT NULL,
    FOREIGN KEY (IDProducto) REFERENCES Productos(IDProducto)
);

-- Tabla de Informacion de Empresa
CREATE TABLE IF NOT EXISTS InfoEmpresa (
    RUC VARCHAR(20) PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    Correo VARCHAR(100) NOT NULL,
    Direccion VARCHAR(200) NOT NULL,
    Celular VARCHAR(20) NOT NULL
);

-- Tabla de OrdenesCompra
CREATE TABLE IF NOT EXISTS OrdenesCompra (
    IDOrden INT AUTO_INCREMENT PRIMARY KEY,
    FechaOrden DATE NOT NULL,
    Estado ENUM('Pendiente', 'Entregada', 'Cancelada', 'Rechazada') NOT NULL,
    Moneda VARCHAR(3) NOT NULL DEFAULT 'PEN',
    RUCProveedor VARCHAR(20),
    RUCMiEmpresa VARCHAR(20) NOT NULL,
    IDUsuario INT,
    FOREIGN KEY (RUCProveedor) REFERENCES Proveedores(RUC),
    FOREIGN KEY (RUCMiEmpresa) REFERENCES InfoEmpresa(RUC),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(IDUsuario)
);


-- Tabla de DetallesOrdenesCompra
CREATE TABLE IF NOT EXISTS DetallesOrdenesCompra (
    IDDetalle INT AUTO_INCREMENT PRIMARY KEY,
    IDOrden INT,
    IDProducto INT NOT NULL,
    Cantidad INT NOT NULL,
    PrecioUnitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (IDOrden) REFERENCES OrdenesCompra(IDOrden),
    FOREIGN KEY (IDProducto) REFERENCES Productos(IDProducto)
);


