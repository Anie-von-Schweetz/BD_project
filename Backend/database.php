<?php

// Настройки подключения к БД
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASSWORD', ''); 
define('DB_NAME', 'culture_navigator'); 

// Подключение к базе данных
function connectDB() {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Проверка соединения
    if ($connection->connect_error) {
        die("Ошибка подключения к БД: " . $connection->connect_error);
    }
    
    // Устанавливаем кодировку UTF-8 и часовой пояс
    $connection->set_charset("utf8mb4");
    date_default_timezone_set('Europe/Moscow');
    
    return $connection;
}

// Функция для безопасной обработки строк
function sanitize($connection, $data) {
    return htmlspecialchars(stripslashes(trim($connection->real_escape_string($data))));
}
?>