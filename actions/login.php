<?php
include('../includes/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['usuario']) && isset($_POST['contrasena'])) {
        $usuario = $_POST['usuario'];
        $contrasena = $_POST['contrasena'];

        $sql = "SELECT * FROM Usuarios WHERE Nombre = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($contrasena, $user['Contrasena'])) {
                // Guardar datos en sesión
                $_SESSION['usuario'] = $user['Nombre'];
                $_SESSION['rol'] = $user['Rol'];

                // DEBUG: Mostrar sesión y detener
                //var_dump($_SESSION);
                //exit();

                // Redirigir al dashboard
                header("Location: ../screens/dashboard.php");
                exit();
            } else {
                header("Location: ../screens/login.html?error=Usuario y/o Contraseña incorrecta.");
                exit();
            }
        } else {
            header("Location: ../screens/login.html?error=Usuario y/o Contraseña incorrecta");
            exit();
        }
    } else {
        header("Location: ../screens/login.html?error=Por favor, ingresa todos los campos.");
        exit();
    }
} else {
    // Acceso directo a login.php sin POST
    header("Location: ../screens/login.html");
    exit();
}
?>
