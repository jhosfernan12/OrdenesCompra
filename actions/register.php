<?php
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['usuario']) && isset($_POST['contrasena']) && isset($_POST['correo'])) {
        $usuario = $_POST['usuario'];
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $correo = $_POST['correo'];
        $rol = 'Administrador';

        // Verificar si el usuario ya existe
        $sql_check = "SELECT * FROM Usuarios WHERE Nombre = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            header("Location: ../screens/register.html?mensaje=El%20usuario%20ya%20existe.&tipo=error");
            exit();
        }

        // Insertar nuevo usuario
        $sql = "INSERT INTO Usuarios (Nombre, Contrasena, Correo, Rol) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $usuario, $contrasena, $correo, $rol);
        if ($stmt->execute()) {
            header("Location: ../screens/register.html?mensaje=Usuario%20registrado%20correctamente.&tipo=exito");
            exit();
        } else {
            header("Location: ../screens/register.html?mensaje=Error%20al%20registrar%20usuario.&tipo=error");
            exit();
        }
    } else {
        header("Location: ../screens/register.html?mensaje=Completa%20todos%20los%20campos.&tipo=error");
        exit();
    }
}

?>
