<?php
// includes/EventService.php
require_once 'ApiClient.php';
require_once 'ReviewService.php';

class EventService {
    private $apiClient;
    private $reviewService;
    
    public function __construct($apiKey, $dbConnection = null) {
        $this->apiClient = new ApiClient($apiKey);
        $this->reviewService = $dbConnection ? new ReviewService($dbConnection) : null;
    }
    
    /**
     * Поиск мероприятий
     */
    public function search($filters = []) {
        // Получаем из API (с кэшированием)
        $events = $this->apiClient->getEvents($filters);
        
        // Добавляем отзывы если есть подключение к БД
        if ($this->reviewService) {
            foreach ($events as &$event) {
                $event['reviews'] = $this->reviewService->getEventReviews($event['id']);
                $event['sentiment_stats'] = $this->reviewService->getSentimentStats($event['id']);
            }
        }
        
        return [
            'events' => $events,
            'total' => count($events),
            'filters' => $filters
        ];
    }
    
    /**
     * Получить одно мероприятие
     */
    public function getEvent($externalId) {
        // Можно либо искать в кэше, либо делать отдельный запрос
        // Для простоты ищем в кэше всех мероприятий
        $allEvents = $this->apiClient->getEvents([]);
        
        foreach ($allEvents as $event) {
            if ($event['id'] == $externalId) {
                // Добавляем отзывы
                if ($this->reviewService) {
                    $event['reviews'] = $this->reviewService->getEventReviews($externalId);
                }
                return $event;
            }
        }
        
        return null;
    }
}
?>