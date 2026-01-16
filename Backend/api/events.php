<?php
// api/events.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/EventService.php';
require_once __DIR__ . '/../includes/DataBase.php';

// Конфигурация
$apiKey = '8864d0ddbee51acdf5f923f5cab025bd665f26e5c67a07e44d81af5eb8a7b29e';
$db = new DataBase('localhost', 'root', '', 'culture_navigator');
$service = new EventService($apiKey, $db->getConnection());

// Получаем параметры запроса
$filters = [
    'city' => $_GET['city'] ?? null,
    'min_age' => isset($_GET['min_age']) ? (int)$_GET['min_age'] : null,
    'max_age' => isset($_GET['max_age']) ? (int)$_GET['max_age'] : null,
    'category' => $_GET['category'] ?? null,
    'is_free' => isset($_GET['is_free']) ? (bool)$_GET['is_free'] : null,
    'date_from' => $_GET['date_from'] ?? null,
    'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 20,
    'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0
];

// Выполняем поиск
try {
    $result = $service->search($filters);
    
    // Формируем ответ
    $response = [
        'success' => true,
        'data' => $result['events'],
        'meta' => [
            'total' => $result['total'],
            'limit' => $filters['limit'],
            'offset' => $filters['offset'],
            'filters' => array_filter($filters) // Только установленные фильтры
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>