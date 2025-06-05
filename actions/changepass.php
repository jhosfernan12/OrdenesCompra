<?php
include('../includes/db.php');

$tokenValido = false;
$mensajeExito = "";
$mensajeError = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $sql = "SELECT * FROM Usuarios WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tokenValido = true;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nuevaContrasena = $_POST['contrasena'];
            $hashContrasena = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

            $sql = "UPDATE Usuarios SET Contrasena = ?, token = NULL WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $hashContrasena, $token);
            $stmt->execute();

            $mensajeExito = "✅ Tu contraseña ha sido cambiada exitosamente. Puedes <a href='login.php'>iniciar sesión</a> ahora.";
            $tokenValido = false;
        }
    } else {
        $mensajeError = "❌ El enlace de recuperación de contraseña no es válido o ha expirado.";
    }
} else {
    $mensajeError = "❌ No se ha proporcionado un token.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="../UI/styles.css">
    <style>
        @keyframes fadeOutDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(30px);
            }
        }

        .message {
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            animation: fadeInUp 0.6s ease-out;
            transition: opacity 0.3s ease;
            font-size: 0.95em;
            max-width: 100%;
            word-wrap: break-word;
        }

        .error {
            background-color: #fdecea;
            color: #b71c1c;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #e6f9ec;
            color:rgba(27, 94, 31, 0.53);
            border: 1px solid #a5d6a7;
        }
    </style>
</head>
<body>
    <div class="wrapper animate">
        <h2>Cambiar Contraseña</h2>

        <!-- Mostrar mensaje de éxito si la contraseña fue cambiada -->
        <?php if ($mensajeExito): ?>
            <div class="message success"><?php echo $mensajeExito; ?></div>
        <?php elseif ($mensajeError): ?>
            <div class="message error"><?php echo $mensajeError; ?></div>
        <?php elseif ($tokenValido): ?>
            <!-- Formulario para cambiar la contraseña si el token es válido -->
            <form method="POST" action="changepass.php?token=<?php echo htmlspecialchars($_GET['token']); ?>">
                <div class="password-container">
                    <input type="password" name="contrasena" id="contrasena" placeholder="Nueva Contraseña" required>
                    <img src="../UI/assets/eyeclosed.png" id="toggle-password" style="width:24px; position:absolute; right:10px; top:12px; cursor:pointer;" alt="Mostrar/Ocultar">
                </div>
                <input type="submit" value="Cambiar Contraseña">
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Mostrar/Ocultar contraseña
        const passwordInput = document.getElementById('contrasena');
        const togglePassword = document.getElementById('toggle-password');
        let isPasswordVisible = false;

        togglePassword.addEventListener('click', () => {
            isPasswordVisible = !isPasswordVisible;
            passwordInput.type = isPasswordVisible ? 'text' : 'password';
            togglePassword.src = isPasswordVisible 
                ? '../UI/assets/eyeallowed.png' 
                : '../UI/assets/eyeclosed.png';
        });
    </script>
</body>
</html>
