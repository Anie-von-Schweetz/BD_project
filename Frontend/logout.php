<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
session_destroy();

// Перенаправляем на главную
header('Location: login.php');
exit();
?>