<?php
// api/reviews.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/ReviewService.php';
require_once __DIR__ . '/../includes/DataBase.php';

$db = new DataBase('localhost', 'root', '', 'culture_navigator');
$reviewService = new ReviewService($db->getConnection());

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Получить отзывы для мероприятия
        $eventId = $_GET['event_id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['error' => 'event_id is required']);
            break;
        }
        
        $reviews = $reviewService->getEventReviews($eventId);
        $stats = $reviewService->getSentimentStats($eventId);
        
        echo json_encode([
            'reviews' => $reviews,
            'stats' => $stats
        ], JSON_UNESCAPED_UNICODE);
        break;
        
    case 'POST':
        // Добавить отзыв (нужна авторизация)
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authorized']);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $success = $reviewService->addReview(
            $_SESSION['user_id'],
            $data['event_id'],
            $data['text'],
            $data['sentiment'],
            $data['rating'] ?? null
        );
        
        echo json_encode(['success' => $success]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>