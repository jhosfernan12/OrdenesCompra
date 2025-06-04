<?php
session_start();
session_unset();
session_destroy();
header("Location: ../screens/login.html");
exit();
?>
