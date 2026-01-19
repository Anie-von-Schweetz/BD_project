<?php
session_start();

// Включаем ВСЕ выводы ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('html_errors', 1);

echo "<!DOCTYPE html><html><body style='padding:20px;'>";
echo "<h2>DEBUG MODE - account.php</h2>";

// Подключаем файлы
require_once __DIR__ . '/../Backend/database.php';
require_once __DIR__ . '/../Backend/config.php';
require_once __DIR__ . '/../Backend/includes/ApiClient.php';

// Тестовый пользователь
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'Тест';

$connection = connectDB();

// ================== ВЫВОД ВСЕХ POST ДАННЫХ ==================
echo "<h3 style='color:blue;'>1. POST данные:</h3>";
echo "<pre style='background:#f0f0f0; padding:10px;'>";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    print_r($_POST);
} else {
    echo "Нет POST данных";
}
echo "</pre>";

// ================== ОБРАБОТКА ФОРМЫ ==================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_subscription'])) {
    echo "<h3 style='color:green;'>2. Обработчик create_subscription сработал!</h3>";
    
    $subname = $_POST['subname'] ?? '';
    $event_categories = $_POST['event_categories'] ?? '';
    
    echo "<p><strong>subname:</strong> " . htmlspecialchars($subname) . "</p>";
    echo "<p><strong>event_categories из POST:</strong> '" . htmlspecialchars($event_categories) . "'</p>";
    echo "<p><strong>Длина event_categories:</strong> " . strlen($event_categories) . " символов</p>";
    echo "<p><strong>Hex event_categories:</strong> " . bin2hex($event_categories) . "</p>";
    
    // Проверяем переменные перед записью
    echo "<h3 style='color:orange;'>3. Переменные для записи:</h3>";
    echo "<p>user_id: " . $_SESSION['user_id'] . "</p>";
    echo "<p>subname: '$subname'</p>";
    echo "<p>event_categories: '$event_categories'</p>";
    
    // Пробуем записать
    $stmt = $connection->prepare("INSERT INTO subscriptions (user_id, subname, event_categories) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $_SESSION['user_id'], $subname, $event_categories);
    
    echo "<h3 style='color:purple;'>4. Выполнение SQL:</h3>";
    echo "<p>SQL: INSERT INTO subscriptions (user_id, subname, event_categories) VALUES (" . 
         $_SESSION['user_id'] . ", '" . $subname . "', '" . $event_categories . "')</p>";
    
    if ($stmt->execute()) {
        $last_id = $connection->insert_id;
        echo "<p style='color:green; font-weight:bold;'>✓ УСПЕХ! Записано в БД. ID: $last_id</p>";
        
        // Проверяем что записалось
        $check = $connection->query("SELECT * FROM subscriptions WHERE id = $last_id");
        if ($row = $check->fetch_assoc()) {
            echo "<h3 style='color:blue;'>5. Проверка записи в БД:</h3>";
            echo "<pre>";
            print_r($row);
            echo "</pre>";
            
            echo "<p><strong>Записано в event_categories:</strong> '" . htmlspecialchars($row['event_categories']) . "'</p>";
        }
    } else {
        echo "<p style='color:red; font-weight:bold;'>✗ ОШИБКА: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

// Показываем последние записи в БД
echo "<h3 style='color:brown;'>6. Последние 5 записей в таблице subscriptions:</h3>";
$result = $connection->query("SELECT id, subname, event_categories, created_at FROM subscriptions ORDER BY id DESC LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Название</th><th>Категория</th><th>Дата</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['subname']) . "</td>";
    echo "<td style='color:" . ($row['event_categories'] == '0' ? 'red' : 'green') . ";'>" . 
         htmlspecialchars($row['event_categories']) . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$connection->close();

// ================== ФОРМА ==================
echo "<hr><h2>Тестовая форма</h2>";
?>
<form method="POST" style="border:2px solid blue; padding:20px;">
    <div style="margin-bottom:10px;">
        <label>Название подписки:</label><br>
        <input type="text" name="subname" required style="width:300px;">
    </div>
    
    <div style="margin-bottom:10px;">
        <label>Категория мероприятия:</label><br>
        <select name="event_categories" required style="width:300px;">
            <option value="">Выберите категорию</option>
            <option value="Кино">Кино</option>
            <option value="Театр">Театр</option>
            <option value="Выставка">Выставка</option>
        </select>
    </div>
    
    <button type="submit" name="create_subscription">Тест создать подписку</button>
</form>

</body></html>