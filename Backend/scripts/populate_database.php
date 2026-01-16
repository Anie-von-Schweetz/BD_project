<?php
require_once __DIR__ . '/../includes/ApiClient.php';
require_once __DIR__ . '/../includes/Database.php';

$config = [
    'api_key' => '8864d0ddbee51acdf5f923f5cab025bd665f26e5c67a07e44d81af5eb8a7b29e',
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'culture_navigator'
];

echo "🚀 Запуск исправленного скрипта...\n";

// ТЕСТ: Получаем 3 события для проверки
$api = new ApiClient($config['api_key']);
$testEvents = $api->fetchEvents(3, 0);

echo "📊 Тест API: получено " . count($testEvents) . " событий\n";

if (!empty($testEvents)) {
    echo "✅ Структура правильная!\n";
    echo "Первый event ID: " . ($testEvents[0]['general']['id'] ?? 'НЕТ') . "\n";
    echo "Название: " . ($testEvents[0]['general']['name'] ?? 'НЕТ') . "\n";
    
    // Полная загрузка
    $db = new Database($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    
    $total = 0;
    for ($i = 0; $i < 10; $i++) { // 10 × 100 = 1000 событий
        $events = $api->fetchEvents(100, $i * 100);
        if (empty($events)) break;
        
        foreach ($events as $event) {
            if ($db->saveEvent($event)) {
                $total++;
            }
        }
        echo "Страница {$i}: добавлено " . count($events) . " событий\n";
    }
    
    echo "\n✅ Всего загружено: {$total} событий\n";
} else {
    echo "❌ Проблема с API\n";
}
?>