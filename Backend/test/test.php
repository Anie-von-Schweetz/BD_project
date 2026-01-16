<?php
require_once __DIR__ . '/../includes/ApiClient.php';

$api = new ApiClient('8864d0ddbee51acdf5f923f5cab025bd665f26e5c67a07e44d81af5eb8a7b29e');
$events = $api->fetchEvents(5, 0);

echo "Проверка структуры API:\n";
foreach ($events as $index => $event) {
    echo "\n=== Событие {$index} ===\n";
    echo "external_id: " . ($event['general']['id'] ?? 'НЕТ') . "\n";
    echo "title: " . ($event['general']['name'] ?? 'НЕТ') . "\n";
    
    // Проверяем уникальность ID
    if (empty($event['general']['id'])) {
        echo "⚠️  ВНИМАНИЕ: external_id пустой!\n";
    }
}
?>