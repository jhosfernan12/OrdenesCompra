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
    <style>
        /* Reset some default styles */
        body, h1, h2, p {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        /* General page layout */
        .container {
            display: flex;
            min-height: 100vh;
            background-color: #f0f8f3;
        }

        /* Sidebar styles */
        .sidebar {
            background-color: rgba(45, 54, 62, 0.9); /* Dark Green with transparency */
            color: #fff;
            width: 250px;
            padding: 20px;
            position: fixed;
            height: 100%;
            box-shadow: 2px 0px 8px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            font-size: 1.5em;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #fff;
            text-decoration: none;
            margin: 15px 0;
            font-size: 1.1em;
            padding: 8px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: rgba(26, 38, 52, 0.8);
        }

        /* Main content styles */
        .main-content {
            margin-left: 250px;
            width: 100%;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9); /* Transparent white */
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .top-bar {
            background-color: rgba(234, 245, 228, 0.9); /* Light green semi-transparent */
            border-radius: 10px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .top-bar h1 {
            font-size: 1.8em;
            color: #2d6a4f; /* Green color */
        }

        .top-bar .menu-item {
            margin-left: 20px;
            font-size: 1em;
            color: #333;
            cursor: pointer;
        }

        .top-bar .menu-item a {
            color:rgb(255, 255, 255); /* Blue for links */
            text-decoration: none;
        }

        /* Dashboard button styles */
        .dashboard-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .dashboard-buttons button {
            background-color: #4caf50; /* Green button */
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-buttons button:hover {
            background-color: #388e3c; /* Darker green on hover */
        }

        /* Footer styles */
        .footer {
            background-color: rgba(45, 54, 62, 0.9); /* Dark footer */
            color: #fff;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        /* Message box styles */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.1em;
            max-width: 100%;
            display: inline-block;
        }

        .success {
            background-color: rgba(230, 249, 236, 0.9); /* Light green success message */
            color: #2d6a4f; /* Dark green text */
            border: 1px solid #a5d6a7; /* Light green border */
        }

        .error {
            background-color: rgba(253, 236, 234, 0.9); /* Light red error message */
            color: #d32f2f; /* Red text */
            border: 1px solid #f5c6cb; /* Light red border */
        }
    </style>
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
                    <span class="menu-item"><a href="../actions/logout.php">Cerrar Sesión</a></span>
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

            <!-- Success or Error Message -->
            <div id="message" class="message success" style="display: none;">
                ¡Bienvenido a tu panel de control! Tienes acceso completo a las funciones del sistema.
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Sistema de Gestión. Todos los derechos reservados.</p>
    </div>

    <script>
        // Simulamos un mensaje de bienvenida
        setTimeout(() => {
            document.getElementById("message").style.display = "block";
        }, 500);
    </script>
</body>
</html>
