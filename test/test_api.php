<?php
// get_sample_data.php
$api_key = '8864d0ddbee51acdf5f923f5cab025bd665f26e5c67a07e44d81af5eb8a7b29e';

// Получаем несколько примеров
$url = 'https://opendata.mkrf.ru/v2/events/$?l=5';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "X-API-KEY: $api_key",
        "Accept: application/json"
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $data = json_decode($response, true);
    file_put_contents('sample_events.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Примеры данных сохранены в sample_events.json\n";
    
    // Анализируем структуру
    echo "\nАнализ структуры:\n";
    
    if (isset($data['data']) && is_array($data['data'])) {
        $sample = $data['data'][0] ?? [];
        echo "1. Ключи верхнего уровня: " . implode(', ', array_keys($sample)) . "\n";
        
        if (isset($sample['general'])) {
            echo "2. Ключи в general: " . implode(', ', array_keys($sample['general'])) . "\n";
            
            // Проверяем наличие ключевых полей
            $important_fields = ['id', 'name', 'ageRestriction', 'start', 'end', 'places'];
            foreach ($important_fields as $field) {
                if (isset($sample['general'][$field])) {
                    echo "   - {$field}: " . json_encode($sample['general'][$field]) . "\n";
                }
            }
        }
    }
} else {
    echo "Ошибка: HTTP код {$http_code}\n";
    echo "Ответ: " . substr($response, 0, 500) . "\n";
}
?>