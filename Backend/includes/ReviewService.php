<?php
// includes/ReviewService.php
class ReviewService {
    private $db;
    
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }
    
    /**
     * Добавить отзыв к мероприятию (по external_id из API)
     */
    public function addReview($userId, $eventExternalId, $text, $sentiment, $rating = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO reviews (user_id, event_external_id, text, sentiment, rating) 
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                text = VALUES(text),
                sentiment = VALUES(sentiment),
                rating = VALUES(rating),
                created_at = CURRENT_TIMESTAMP"
        );
        
        $stmt->bind_param("iissi", $userId, $eventExternalId, $text, $sentiment, $rating);
        return $stmt->execute();
    }
    
    /**
     * Получить отзывы для мероприятия
     */
    public function getEventReviews($eventExternalId) {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.username, 
                    CASE r.sentiment
                        WHEN 'positive' THEN 'green'
                        WHEN 'neutral' THEN 'yellow'
                        WHEN 'negative' THEN 'red'
                    END as color
             FROM reviews r 
             JOIN users u ON r.user_id = u.id 
             WHERE r.event_external_id = ? 
             ORDER BY r.created_at DESC"
        );
        $stmt->bind_param("i", $eventExternalId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Статистика по тональности отзывов
     */
    public function getSentimentStats($eventExternalId) {
        $stmt = $this->db->prepare(
            "SELECT 
                sentiment,
                COUNT(*) as count,
                AVG(rating) as avg_rating
             FROM reviews 
             WHERE event_external_id = ? 
             GROUP BY sentiment"
        );
        $stmt->bind_param("i", $eventExternalId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Формируем структуру для фронтенда
        $stats = [
            'positive' => ['count' => 0, 'color' => 'green'],
            'neutral' => ['count' => 0, 'color' => 'yellow'],
            'negative' => ['count' => 0, 'color' => 'red']
        ];
        
        foreach ($result as $row) {
            $stats[$row['sentiment']]['count'] = $row['count'];
            $stats[$row['sentiment']]['avg_rating'] = $row['avg_rating'];
        }
        
        return $stats;
    }
    
    /**
     * Получить отзывы пользователя
     */
    public function getUserReviews($userId) {
        $stmt = $this->db->prepare(
            "SELECT r.*, 
                    CASE r.sentiment
                        WHEN 'positive' THEN 'green'
                        WHEN 'neutral' THEN 'yellow'
                        WHEN 'negative' THEN 'red'
                    END as color
             FROM reviews r 
             WHERE r.user_id = ? 
             ORDER BY r.created_at DESC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>