<?php
// test_pagination.php
require_once __DIR__ . '/../Backend/config.php';
require_once __DIR__ . '/../Backend/includes/ApiClient.php';

$api = new ApiClient(API_KEY);

echo "<h2>Тест пагинации и фильтрации</h2>";

// Тест 1: Сколько всего событий из API
$testUrl = "https://opendata.mkrf.ru/v2/events/15?l=50";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "X-API-KEY: " . API_KEY,
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "<p>API вернуло: " . count($data['data'] ?? []) . " событий всего</p>";
    
    // Посчитаем сколько подходят по возрасту (0-16)
    $childEvents = 0;
    foreach ($data['data'] ?? [] as $item) {
        if (!isset($item['data'])) continue;
        $general = $item['data']['general'] ?? [];
        $age = $general['ageRestriction'] ?? 99;
        if ($age <= 16) $childEvents++;
    }
    echo "<p>Детских событий (0-16 лет): " . $childEvents . "</p>";
}

// Тест 2: Что возвращает getEvents()
echo "<h3>getEvents() результат:</h3>";
$result = $api->getEvents(1, []);
echo "<pre>";
print_r([
    'total' => $result['total'] ?? 0,
    'events_count' => count($result['events'] ?? []),
    'page' => $result['page'] ?? 1,
    'pages' => $result['pages'] ?? 1,
    'limit' => $result['limit'] ?? 12
]);
echo "</pre>";

// Тест 3: Вывести первые 3 события
echo "<h3>Первые события:</h3>";
if (!empty($result['events'])) {
    echo "<ol>";
    foreach ($result['events'] as $event) {
        echo "<li>";
        echo "<strong>" . htmlspecialchars($event['title']) . "</strong><br>";
        echo "Возраст: " . ($event['ageCategory'] ?? '?') . "<br>";
        echo "Город: " . htmlspecialchars($event['city']) . "<br>";
        echo "Дата: " . ($event['startDate'] ?? 'нет');
        echo "</li>";
    }
    echo "</ol>";
} else {
    echo "<p style='color: red;'>Нет событий!</p>";
}

// Тест 4: Проверить фильтры
echo "<h3>Фильтр по возрасту 0+:</h3>";
$result = $api->getEvents(1, ['age' => '0']);
echo "Событий для 0+: " . ($result['total'] ?? 0);

// Тест 5: Уникальные города
echo "<h3>Уникальные города:</h3>";
$cities = $api->getUniqueCities();
echo "Всего городов: " . count($cities) . "<br>";
echo "Примеры: " . implode(', ', array_slice($cities, 0, 10));
?>