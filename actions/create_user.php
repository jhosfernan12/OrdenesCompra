<?php
include('../includes/db.php');
session_start(); // Iniciar sesión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nombre_usuario = $_POST['nombre_usuario'];
    $contrasena_usuario = $_POST['contrasena_usuario'];
    $rol_usuario = $_POST['rol_usuario'];

    // Encriptar la contraseña
    $hashed_password = password_hash($contrasena_usuario, PASSWORD_DEFAULT);

    // Insertar el nuevo usuario (empleado) en la base de datos
    $sql_insert = "INSERT INTO Usuarios (Nombre, Contrasena, Rol) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $nombre_usuario, $hashed_password, $rol_usuario);

    if ($stmt_insert->execute()) {
        echo "Nuevo usuario creado exitosamente.";
    } else {
        echo "Error al crear el usuario.";
    }
}
?>
