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
            background-color: #562870;
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
            background-color:#a148ad;
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
            background-color: rgba(237, 228, 245, 0.9);
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
            color: #562870;
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
            background-color: #562870;
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
            background-color:#6d338f;
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
            background-color: rgba(235, 230, 249, 0.9);
            color:rgb(109, 20, 182);
            border: 1px solid rgb(180, 165, 214)
        }
        .chart-container {
            display: flex;
            justify-content: center;   /* Centra horizontalmente */
            align-items: center;       /* Centra verticalmente (si lo necesitás) */
            flex-direction: column;
            width: 100%;
            min-height: 400px;
            margin-top: 2rem;
            margin-bottom: 2rem;
            margin-right: 200px;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <img src="../UI/assets/logodefault.png" alt="Logo" style="width: 115%; max-width: 500px; height: auto;">
            </div>
            <nav class="sidebar-nav">
                <a href="#" id="linkSuppliers"><i class="fas fa-truck"></i> Gestión Proveedores</a>
                <a href="#" id="linkProducts"><i class="fas fa-box"></i> Gestión Productos</a>
                <a href="#" id="linkOrders"><i class="fas fa-shopping-cart"></i> Órdenes de Compra</a>
                <a href="#" id="linkInventory"><i class="fas fa-chart-line"></i> Inventario</a>
                <a href="#" id="linkUserManagement"><i class="fas fa-users-cog"></i> Gestión Usuarios</a>
                <a href="#" id="linkMyCompany"><i class="fas fa-users-cog"></i> Mi Empresa</a>
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
                <button id="btnSuppliers"> Gestión Proveedores</button>
                <button id="btnProducts">Gestión Productos</button>
                <button id="btnOrders">Órdenes de Compra</button>
                <button id="btnInventory">Inventario</button>
                <button id="btnUserManagement">Gestión Usuarios</button>
                <button id="btnMyCompany">Mi Empresa</button>
            </div>

            <div id="message" class="message success" style="display: none;">
                ¡Bienvenido a tu panel de control!
            </div>
             <div style="margin-top: 10px; max-width: 500px;">
    <h2 style="color: #562870; margin-bottom: 15px;">Órdenes por mes</h2>
    
    <div class="chart-container">
    <div style="width: 100%; max-width: 800px; height: 400px;">
        <canvas id="ordersChart"></canvas>
    </div>
</div>
</div>

        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 FLUß - Sistema de Gestión de Ordenes de Compra. Todos los derechos reservados</p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            if (userRole === 'Administrador' || userRole === 'Supervisor') {
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
            if (userRole === 'Administrador' || userRole === 'Comprador' || userRole === 'Supervisor') {
                window.location.href = "orders.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnOrders").addEventListener('click', handleOrdersAccess);
        document.getElementById("linkOrders").addEventListener('click', (e) => {
            e.preventDefault();
            handleOrdersAccess();
        });
        
        // Gestión Inventario
        const handleInventoryAccess = () => {
            if (userRole === 'Administrador' || userRole === 'Comprador') {
                window.location.href = "inventory.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnInventory").addEventListener('click', handleInventoryAccess);
        document.getElementById("linkInventory").addEventListener('click', (e) => {
            e.preventDefault();
            handleInventoryAccess();
        });

        const handleMyCompany = () => {
            if (userRole === 'Administrador') {
                window.location.href = "mycompany.html";
            } else {
                window.location.href = "accessdenied.html";
            }
        };

        document.getElementById("btnMyCompany").addEventListener('click', handleMyCompany);
        document.getElementById("linkMyCompany").addEventListener('click', (e) => {
            e.preventDefault();
            handleMyCompany();
        });



        const ctx = document.getElementById('ordersChart').getContext('2d');

        const ordersChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [], // ← se llenarán dinámicamente
                datasets: [{
                    label: 'Órdenes',
                    data: [],
                    backgroundColor: 'rgba(86, 40, 112, 0.54)', // azul suave
                    borderColor: 'rgb(73, 33, 165)', // borde más oscuro
                    borderWidth: 2,
                    borderRadius: 8,  // esquinas redondeadas
                    barThickness: 30  // barras más delgadas
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#222',
                        titleColor: '#fff',
                        bodyColor: '#ddd',
                        cornerRadius: 6,
                        padding: 10
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10,
                        left: 20,
                        right: 20
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)'
                        },
                        ticks: {
                            color: '#666',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#666',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Llama al backend para obtener los datos reales
        fetch('../actions/orders.php?action=getMonthlyOrders')
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    ordersChart.data.labels = json.labels;
                    ordersChart.data.datasets[0].data = json.data;
                    ordersChart.update();
                } else {
                    console.error('Error al obtener datos del gráfico:', json.message);
                }
            })
            .catch(error => {
                console.error('Error en la petición fetch:', error);
            });

    </script>


    
</body>
</html>
