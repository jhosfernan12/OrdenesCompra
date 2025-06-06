<?php
include('../includes/db.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$name || !$role) {
        echo json_encode(['status' => 'error', 'message' => 'Nombre y rol son requeridos']);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO Usuarios (Nombre, Rol) VALUES (?, ?)");
        $stmt->execute([$name, $role]);

        echo json_encode(['status' => 'ok', 'message' => 'Usuario creado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Crear Usuario</title>
</head>
<body>
    <h1>Crear Usuario</h1>
    <form id="createUserForm">
        <label>Nombre:<br />
            <input type="text" name="name" required />
        </label><br /><br />
        <label>Rol:<br />
            <select name="role" required>
                <option value="">Selecciona rol</option>
                <option value="Usuario">Usuario</option>
                <option value="Administrador">Administrador</option>
            </select>
        </label><br /><br />
        <button type="submit">Crear</button>
    </form>

    <script>
        document.getElementById('createUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
            const res = await fetch('', { method: 'POST', body: data });
            const result = await res.json();
            alert(result.message);
            if (result.status === 'ok') {
                window.opener.location.reload(); // Refresca la página de usuarios
                window.close(); // Cierra esta ventana
            }
        });
    </script>
</body>
</html>
