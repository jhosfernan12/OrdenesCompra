<?php
session_start();

// DEBUG: Para ver qué hay en sesión (descomenta si quieres probar)
// var_dump($_SESSION);
// exit();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../UI/styles.css" />
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Panel de Control</h2>
            <a href="#">Gestión Proveedores</a>
            <a href="#">Gestión Productos</a>
            <a href="#">Órdenes de Compra</a>
            <a href="#">Reportes</a>
            <a href="#">Gestión Usuarios</a>
        </div>

        <!-- Main content -->
        <div class="main-content">
            <!-- Top bar -->
            <div class="top-bar">
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h1>
                <div>
                    <span class="menu-item">Ajustes</span>
                    <span class="menu-item">Notificaciones</span>
                    <span class="menu-item"><a href="../actions/logout.php" style="color: white; text-decoration: none;">Cerrar Sesión</a></span>
                </div>
            </div>

            <!-- Dashboard Buttons -->
            <div class="dashboard-buttons">
                <button>Gestión Proveedores</button>
                <button>Gestión Productos</button>
                <button>Órdenes de Compra</button>
                <button>Reportes</button>
                <button>Gestión Usuarios</button>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 Sistema de Gestión. Todos los derechos reservados.</p>
    </div>
</body>
</html>
