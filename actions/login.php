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
                // ✅ Guardar datos en la sesión
                $_SESSION['usuario'] = $user['Nombre'];
                $_SESSION['rol'] = $user['Rol'];

                // ✅ Esto es lo que necesita tu frontend
                $_SESSION['user'] = [
                    'id' => $user['IDUsuario'],
                    'name' => $user['Nombre']
                ];

                // ✅ Redirigir al dashboard
                header("Location: ../screens/dashboard.php");
                exit();
            } else {
                // ❌ Contraseña incorrecta
                header("Location: ../screens/login.html?error=Usuario y/o Contraseña incorrecta.");
                exit();
            }
        } else {
            // ❌ Usuario no encontrado
            header("Location: ../screens/login.html?error=Usuario y/o Contraseña incorrecta");
            exit();
        }
    } else {
        // ❌ Campos vacíos
        header("Location: ../screens/login.html?error=Por favor, ingresa todos los campos.");
        exit();
    }
} else {
    // ❌ Acceso directo sin POST
    header("Location: ../screens/login.html");
    exit();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <form method="POST" action="login.php">
        <input type="text" name="usuario" placeholder="Usuario" required><br>
        <input type="password" name="contrasena" placeholder="Contraseña" required><br>
        <input type="submit" value="Iniciar sesión"><br>
    </form>
    
    <!-- Enlace para recuperar la contraseña -->
    <p><a href="recoverpass.php">¿Olvidaste tu contraseña?</a></p>
    
    <?php
    if (isset($_GET['error'])) {
        echo "<p style='color:red;'>".$_GET['error']."</p>";
    }
    ?>
</body>
</html>
