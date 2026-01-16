<?php
// includes/ApiClient.php
require_once 'CacheManager.php';

class ApiClient {
    private $apiKey;
    private $baseUrl = 'https://opendata.mkrf.ru/v2';
    private $cache;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->cache = new CacheManager();
    }
    
    /**
     * Получить события с кэшированием
     */
    public function getEvents($filters = []) {
        $cacheKey = $this->cache->generateKey($filters);
        
        // 1. Пробуем получить из кэша
        if ($cached = $this->cache->get($cacheKey)) {
            // Применяем limit и offset к закэшированным данным
            return $this->applyPagination($cached, $filters);
        }
        
        // 2. Загружаем из API
        $events = $this->fetchFromApi($filters);
        
        // 3. Обрабатываем данные
        $processedEvents = $this->processEvents($events);
        
        // 4. Сохраняем в кэш (на 1 час)
        $this->cache->set($cacheKey, $processedEvents);
        
        // 5. Возвращаем с пагинацией
        return $this->applyPagination($processedEvents, $filters);
    }
    
    private function fetchFromApi($filters) {
        // Базовые параметры
        $params = [
            'l' => 500, // Загружаем много за раз для кэша
            's' => 0,
        ];
        
        // Можно добавить базовые фильтры если API их поддерживает
        $url = $this->baseUrl . "/events/\$?" . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "X-API-KEY: {$this->apiKey}",
                "Accept: application/json"
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [];
        }
        
        $data = json_decode($response, true);
        
        // Извлекаем события
        $events = [];
        foreach ($data['data'] ?? [] as $item) {
            if (isset($item['data'])) {
                $events[] = $item['data'];
            }
        }
        
        return $events;
    }
    
    private function processEvents($events) {
        $processed = [];
        
        foreach ($events as $event) {
            $general = $event['general'] ?? [];
            if (empty($general['id'])) continue;
            
            // Парсим даты
            $startDate = $this->parseDate($general['start'] ?? null);
            $endDate = $this->parseDate($general['end'] ?? null);
            
            $processed[] = [
                'id' => (int)$general['id'],
                'title' => $general['name'] ?? '',
                'description' => $general['shortDescription'] ?? '',
                'age' => $general['ageRestriction'] ?? 0,
                'age_category' => $this->getAgeCategory($general['ageRestriction'] ?? 0),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'city' => $event['general']['places'][0]['locale']['name'] ?? '',
                'category' => $general['category']['name'] ?? '',
                'is_free' => (bool)($general['isFree'] ?? false),
                'price' => $general['price'] ?? null,
                'place' => $general['places'][0]['name'] ?? '',
                'address' => $general['places'][0]['address']['fullAddress'] ?? '',
                'organizer' => $general['organization']['name'] ?? '',
                'raw_data' => $event // Полные данные для детальной страницы
            ];
        }
        
        return $processed;
    }
    
    private function parseDate($dateString) {
        if (empty($dateString)) return null;
        
        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function getAgeCategory($age) {
        if ($age == 0) return '0+';
        if ($age <= 6) return '6+';
        if ($age <= 12) return '12+';
        if ($age <= 16) return '16+';
        return '18+';
    }
    
    private function applyPagination($events, $filters) {
        $limit = $filters['limit'] ?? 20;
        $offset = $filters['offset'] ?? 0;
        
        // Сначала фильтруем по другим критериям
        $filtered = $this->applyFilters($events, $filters);
        
        // Затем применяем пагинацию
        return array_slice($filtered, $offset, $limit);
    }
    
    private function applyFilters($events, $filters) {
        return array_filter($events, function($event) use ($filters) {
            // Фильтр по городу
            if (!empty($filters['city']) && 
                stripos($event['city'], $filters['city']) === false) {
                return false;
            }
            
            // Фильтр по возрасту
            if (!empty($filters['min_age']) && $event['age'] < $filters['min_age']) {
                return false;
            }
            
            if (!empty($filters['max_age']) && $event['age'] > $filters['max_age']) {
                return false;
            }
            
            // Фильтр по категории
            if (!empty($filters['category']) && $event['category'] != $filters['category']) {
                return false;
            }
            
            // Фильтр по дате
            if (!empty($filters['date_from']) && $event['start_date'] < $filters['date_from']) {
                return false;
            }
            
            return true;
        });
    }
}
?>