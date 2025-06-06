-- Esquema de la base de datos

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

-- Tabla de Proveedores
CREATE TABLE IF NOT EXISTS Proveedores (
    IDProveedor INT AUTO_INCREMENT PRIMARY KEY,
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
    IDProveedor INT,
    FOREIGN KEY (IDProveedor) REFERENCES Proveedores(IDProveedor)
);

-- Tabla de OrdenesCompra
CREATE TABLE IF NOT EXISTS OrdenesCompra (
    IDOrden INT AUTO_INCREMENT PRIMARY KEY,
    FechaOrden DATE NOT NULL,
    Estado VARCHAR(50) NOT NULL,
    IDProveedor INT,
    IDUsuario INT,
    FOREIGN KEY (IDProveedor) REFERENCES Proveedores(IDProveedor),
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
