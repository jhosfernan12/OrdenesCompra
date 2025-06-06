<?php
session_start();

// Solo permitir acceso si está logueado y es Administrador
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Solo administradores.']);
    exit();
}

include('../includes/db.php');
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_GET['action'] ?? '';

    if ($action === 'list') {
        $stmt = $pdo->query("SELECT IDUsuario AS id, Nombre AS name, Rol AS role FROM Usuarios");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'users' => $users]);
        exit;
    }

    // Leer JSON de POST
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        $name = trim($input['name'] ?? '');
        $role = trim($input['role'] ?? '');
        $password = $input['password'] ?? '';

        if (!$name || !$role || !$password) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre, rol y contraseña son requeridos']);
            exit;
        }

        // Solo permitimos Comprador y Supervisor (no Administrador)
        $rolesValidos = ['Comprador', 'Supervisor'];
        if (!in_array($role, $rolesValidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Rol no válido. No se puede asignar Administrador.']);
            exit;
        }

        $hashedPass = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO Usuarios (Nombre, Rol, Contrasena) VALUES (?, ?, ?)");
        $stmt->execute([$name, $role, $hashedPass]);

        echo json_encode(['status' => 'ok', 'message' => 'Usuario creado correctamente']);
        exit;
    }

    if ($action === 'update') {
    $id = $input['id'] ?? null;
    $name = trim($input['name'] ?? '');
    $role = trim($input['role'] ?? '');
    $password = $input['password'] ?? '';

    if (!$id || !$name || !$role) {
        echo json_encode(['status' => 'error', 'message' => 'ID, nombre y rol son requeridos']);
        exit;
    }

    // Primero, verificamos que el usuario a editar existe
    $stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE IDUsuario = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Impedir edición si el usuario es administrador
    if (strtolower($user['Rol']) === 'administrador') {
        echo json_encode(['status' => 'error', 'message' => 'No se puede editar un usuario Administrador']);
        exit;
    }

    // Solo permitimos Comprador y Supervisor (no Administrador)
    $rolesValidos = ['Comprador', 'Supervisor'];
    if (!in_array($role, $rolesValidos)) {
        echo json_encode(['status' => 'error', 'message' => 'Rol no válido. No se puede asignar Administrador.']);
        exit;
    }

    if (trim($password) !== '') {
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE Usuarios SET Nombre = ?, Rol = ?, Contrasena = ? WHERE IDUsuario = ?";
        $params = [$name, $role, $hashedPass, $id];
    } else {
        $sql = "UPDATE Usuarios SET Nombre = ?, Rol = ? WHERE IDUsuario = ?";
        $params = [$name, $role, $id];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['status' => 'ok', 'message' => 'Usuario actualizado correctamente']);
    exit;
    }


    if ($action === 'delete') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID requerido']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT Rol FROM Usuarios WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            exit;
        }

        if (strtolower($user['Rol']) === 'administrador') {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar un administrador']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM Usuarios WHERE IDUsuario = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'ok', 'message' => 'Usuario eliminado correctamente']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
