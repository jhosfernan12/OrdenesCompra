<?php
session_start();

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
        body, h1, h2, p {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            display: flex;
            min-height: 100vh;
            background-color: #f0f8f3;
        }
        .sidebar {
            background-color:rgb(129, 156, 253);
            color: #fff;
            width: 250px;
            padding: 20px;
            position: fixed;
            height: 100%;
            box-shadow: 1px 0px 25px rgb(0, 0, 0);
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
            background-color:hsla(194, 44.90%, 34.90%, 0.48);
        }
        .main-content {
            margin-left: 250px;
            width: 100%;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .top-bar {
            background-color: rgba(234, 245, 228, 0.9);
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
            color: #3d8aa1;
        }
        .top-bar .menu-item {
            margin-left: 20px;
            font-size: 1em;
            color: #333;
            cursor: pointer;
        }
        .top-bar .menu-item a {
            color: rgb(255, 255, 255);
            text-decoration: none;
        }
        .dashboard-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        .dashboard-buttons button {
            background-color: #98AEFF;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.7);
        }
        .dashboard-buttons button:hover {
            background-color:rgb(123, 151, 253);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.7);
            font-size: 1.3em;
        }
        .footer {
            background-color: rgba(45, 54, 62, 0.1);
            color: #fff;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.1em;
            max-width: 100%;
            display: inline-block;
        }
        .success {
            background-color: rgba(230, 249, 236, 0.9);
            color: #2d6a4f;
            border: 1px solid #a5d6a7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Panel de Control</h2>
            <nav class="sidebar-nav">
                <a href="#" id="linkSuppliers"><i class="fas fa-truck"></i> Gestión Proveedores</a>
                <a href="#" id="linkProducts"><i class="fas fa-box"></i> Gestión Productos</a>
                <a href="#" id="linkOrders"><i class="fas fa-shopping-cart"></i> Órdenes de Compra</a>
                <a href=""><i class="fas fa-chart-line"></i> Inventario</a>
                <a href="#" id="linkUserManagement"><i class="fas fa-users-cog"></i> Gestión Usuarios</a>
            </nav>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h1>
                <div>
                    <span class="menu-item"><a href="../actions/logout.php">Cerrar Sesión</a></span>
                </div>
            </div>

            <div class="dashboard-buttons">
                <button id="btnSuppliers">Gestión Proveedores</button>
                <button id="btnProducts">Gestión Productos</button>
                <button id="btnOrders">Órdenes de Compra</button>
                <button>Inventario</button>
                <button id="btnUserManagement">Gestión Usuarios</button>
            </div>

            <div id="message" class="message success" style="display: none;">
                ¡Bienvenido a tu panel de control!
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 KAUFFLUSS - Sistema de Gestión de Ordenes de Compra. Todos los derechos reservados</p>
    </div>

    <script>
        const userRole = "<?php echo $_SESSION['rol']; ?>";

        setTimeout(() => {
            document.getElementById("message").style.display = "block";
        }, 500);

        // Gestión Usuarios
        const handleUserManagementAccess = () => {
            if (userRole === 'Administrador') {
                window.location.href = "usermanagement.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnUserManagement").addEventListener('click', handleUserManagementAccess);
        document.getElementById("linkUserManagement").addEventListener('click', (e) => {
            e.preventDefault(); 
            handleUserManagementAccess();
        });

        // Gestión Productos
        const handleProductsAccess = () => {
           
            if (userRole === 'Administrador' || userRole === 'Comprador') {
                window.location.href = "products.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnProducts").addEventListener('click', handleProductsAccess);
        document.getElementById("linkProducts").addEventListener('click', (e) => {
            e.preventDefault();
            handleProductsAccess();
        });

        // Gestión Proveedores
        const handleSuppliersAccess = () => {
            if (userRole === 'Administrador') {
                window.location.href = "suppliers.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnSuppliers").addEventListener('click', handleSuppliersAccess);
        document.getElementById("linkSuppliers").addEventListener('click', (e) => {
            e.preventDefault();
            handleSuppliersAccess();
        });

        // Gestión Ordenes de Compra
        const handleOrdersAccess = () => {
            if (userRole === 'Administrador' || userRole === 'Comprador') {
                window.location.href = "Orders.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnOrders").addEventListener('click', handleOrdersAccess);
        document.getElementById("linkOrders").addEventListener('click', (e) => {
            e.preventDefault();
            handleOrdersAccess();
        });


        
    </script>


    
</body>
</html>
