<?php
// test_new_flow.php
require_once __DIR__ . '/../includes/EventService.php';
require_once __DIR__ . '/../includes/DataBase.php';

echo "🧪 Тестирование новой архитектуры\n";
echo "================================\n\n";

// 1. Инициализация
$apiKey = '8864d0ddbee51acdf5f923f5cab025bd665f26e5c67a07e44d81af5eb8a7b29e';
$db = new DataBase('localhost', 'root', '', 'culture_navigator');
$service = new EventService($apiKey, $db->getConnection());

// 2. Тест поиска
echo "1. Тест поиска мероприятий:\n";
$events = $service->search([
    'city' => 'Новосибирск',
    'min_age' => 6,
    'max_age' => 12,
    'limit' => 3
]);

echo "   Найдено: " . count($events['events']) . " мероприятий\n";

if (!empty($events['events'])) {
    $first = $events['events'][0];
    echo "   Пример: {$first['title']}\n";
    echo "   Город: {$first['city']}, Возраст: {$first['age']}+\n";
    
    // 3. Тест отзывов
    echo "\n2. Тест системы отзывов:\n";
    $reviewService = new ReviewService($db->getConnection());
    
    // Добавляем тестовый отзыв
    $reviewService->addReview(1, $first['id'], 'Отличное мероприятие!', 'positive', 5);
    echo "   ✅ Добавлен тестовый отзыв\n";
    
    // Получаем отзывы
    $reviews = $reviewService->getEventReviews($first['id']);
    echo "   Отзывов для мероприятия: " . count($reviews) . "\n";
    
    // Статистика
    $stats = $reviewService->getSentimentStats($first['id']);
    echo "   Статистика: \n";
    foreach ($stats as $sentiment => $data) {
        echo "     {$sentiment}: {$data['count']} отзывов\n";
    }
}

// 4. Проверка кэша
echo "\n3. Проверка файлового кэша:\n";
$cacheFiles = glob('cache/*.json');
if (empty($cacheFiles)) {
    echo "   ❌ Нет файлов кэша\n";
} else {
    echo "   ✅ Файлов в кэше: " . count($cacheFiles) . "\n";
    foreach ($cacheFiles as $file) {
        $size = round(filesize($file) / 1024, 2);
        $name = basename($file);
        echo "     - {$name} ({$size} KB)\n";
    }
}

echo "\n✅ Тестирование завершено\n";
?>