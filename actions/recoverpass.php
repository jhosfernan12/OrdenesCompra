<?php
include('../includes/db.php');

// Incluye PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../includes/phpmailer/PHPMailer.php';
require '../includes/phpmailer/SMTP.php';
require '../includes/phpmailer/Exception.php';

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['correo'])) {
        $correo = $_POST['correo'];

        // Verificar si el correo existe en la base de datos
        $sql = "SELECT * FROM Usuarios WHERE Correo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Generar un token único
            $token = bin2hex(random_bytes(16));

            // Guardar token en la base de datos
            $sql = "UPDATE Usuarios SET token = ? WHERE Correo = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $token, $correo);
            $stmt->execute();

            // Enlace de recuperación
            $url = "http://localhost/OrdenesCompra/actions/changepass.php?token=" . $token;

            // Configurar PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'jhosfernan12@gmail.com';
                $mail->Password   = 'wnep orxg bcaf wlnw';      
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('TU_CORREO@gmail.com', 'Sistema de Órdenes');
                $mail->addAddress($correo);

                $mail->Subject = 'RECUPERACION DE CONTRASENA';
                $mail->Body    = "Haz clic en el siguiente enlace para recuperar tu contraseña :)\n\n$url";

                $mail->send();
                
                // Mensaje de éxito
                $successMessage = "✅ Te hemos enviado un correo para recuperar tu contraseña.";
            } catch (Exception $e) {
                $errorMessage = "Error al enviar el correo: {$mail->ErrorInfo}";
            }
        } else {
            $errorMessage = "No se encontró un usuario con ese correo.";
        }
    } else {
        $errorMessage = "Por favor, ingresa tu correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Recuperar Contraseña</title>
  <link rel="stylesheet" href="../UI/styles.css" />
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

    .success, .exito {
      background-color: #e6f9ec;
      color:rgba(27, 94, 31, 0.7);
      border: 1px solid #a5d6a7;
    }
  </style>
</head>
<body>
  <div class="wrapper animate">
    <h2>Recuperar Contraseña</h2>

    <!-- Mensaje de éxito (si lo hay) -->
    <?php if ($successMessage): ?>
      <div id="response-message" class="message success">
        <?php echo $successMessage; ?>
      </div>
    <?php endif; ?>

    <!-- Mensaje de error (si lo hay) -->
    <?php if ($errorMessage): ?>
      <div id="response-message" class="message error">
        <?php echo $errorMessage; ?>
      </div>
    <?php endif; ?>

    <!-- Formulario de recuperación -->
    <form method="POST" action="recoverpass.php">
      <input type="email" name="correo" placeholder="Correo electrónico" required />
      <input type="submit" value="Recuperar contraseña" />
    </form>

    <p class="forgot-password-link"><a href="login.php">Volver al login</a></p>
  </div>

  <script>
    // Mostrar mensaje dinámico
    const params = new URLSearchParams(window.location.search);
    const mensaje = params.get("mensaje");
    const tipo = params.get("tipo"); // 'exito' o 'error'
    const msgDiv = document.getElementById("response-message");

    if (mensaje && tipo) {
      msgDiv.textContent = decodeURIComponent(mensaje);
      msgDiv.classList.add(tipo === "exito" ? "success" : "error");
      msgDiv.style.display = "block";

      setTimeout(() => {
        msgDiv.style.animation = "fadeOutDown 0.6s ease-out forwards";
      }, 4000);
    }
  </script>
</body>
</html>
