<?php
// Включение отображения ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
$current_page = 'index.php';

// Подключаем конфигурацию
require_once __DIR__ . '/../Backend/config.php';
require_once __DIR__ . '/../Backend/includes/ApiClient.php';

// Инициализация
$api = new ApiClient(API_KEY);

// Получаем параметры из GET
$page = max(1, intval($_GET['page'] ?? 1));
$filters = [
    'city' => $_GET['city'] ?? '',
    'age' => $_GET['age'] ?? '',
    'category' => $_GET['category'] ?? '',
    'price' => $_GET['price'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Получаем данные
$data = $api->getEvents($page, $filters);
$events = $data['events'];
$totalPages = $data['pages'] ?? 1;

// Получаем уникальные города и категории для фильтров
$cities = $api->getUniqueCities();
$categories = $api->getUniqueCategories();


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Культурный навигатор</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="img/icon.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Хедер -->
    <?php include 'header.php'; ?>
    
    <!-- Основной контент -->
    <main class="container py-4">
        <div class="search-section">
            <h1 class="h3 mb-4">Найди мероприятия для ребёнка</h1>
            
            <!-- Строка поиска -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <form method="GET" action="" class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Например: кукольный театр, выставка динозавров..."
                               value="<?= htmlspecialchars($filters['search']) ?>">
                        <button class="btn btn-primary-custom" type="submit">
                            Найти
                        </button>
                    </form>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-secondary w-50 mx-50" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="fas fa-filter me-2"></i>Фильтры
                    </button>
                </div>
            </div>
            
            <!-- Фильтры (скрыты по умолчанию) -->
            <div class="collapse" id="filterCollapse">
                <div class="card card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Город</label>
                                <select class="form-select" name="city">
                                    <option value="">Все города</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= htmlspecialchars($city) ?>" 
                                            <?= $filters['city'] == $city ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($city) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Возраст ребёнка</label>
                                <select class="form-select" name="age">
                                    <option value="">Любой возраст</option>
                                    <option value="0" <?= $filters['age'] == '0' ? 'selected' : '' ?>>0+</option>
                                    <option value="6" <?= $filters['age'] == '6' ? 'selected' : '' ?>>6+</option>
                                    <option value="12" <?= $filters['age'] == '12' ? 'selected' : '' ?>>12+</option>
                                    <option value="16" <?= $filters['age'] == '16' ? 'selected' : '' ?>>16+</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Категория</label>
                                <select class="form-select" name="category">
                                    <option value="">Все категории</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>" 
                                            <?= $filters['category'] == $category ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Цена</label>
                                <select class="form-select" name="price">
                                    <option value="">Любая</option>
                                    <option value="free" <?= $filters['price'] == 'free' ? 'selected' : '' ?>>Бесплатные</option>
                                    <option value="paid" <?= $filters['price'] == 'paid' ? 'selected' : '' ?>>Платные</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="index.php" class="btn btn-outline-danger me-2">Сбросить</a>
                            <button type="submit" class="btn btn-primary-custom">Применить фильтры</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Контейнер для мероприятий -->
        <div id="eventsContainer">
            <div class="row" id="eventsList">
                <?php if (empty($events)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Мероприятия не найдены. Попробуйте изменить фильтры.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 event-card">
                            <?php if (!empty($event['image'])): ?>
                                <img src="<?= htmlspecialchars($event['image']) ?>" 
                                    class="card-img-top" 
                                    alt="<?= htmlspecialchars($event['title']) ?>"
                                    style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                    style="height: 200px;">
                                    <i class="fas fa-calendar-alt fa-3x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-secondary me-1">
                                        <?= htmlspecialchars($event['ageCategory']) ?>
                                    </span>
                                    <?php if ($event['isFree']): ?>
                                        <span class="badge bg-success">Бесплатно</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                                <p class="card-text flex-grow-1">
                                    <?= htmlspecialchars(mb_substr($event['description'], 0, 100)) ?>
                                    <?= mb_strlen($event['description']) > 100 ? '...' : '' ?>
                                </p>

                                <?php if (!$event['isFree']): ?>
                                    <p class="card-price">от <?= htmlspecialchars($event['price']) ?>₽</p>
                                <?php endif; ?>    
                                
                                <div class="mt-auto">
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($event['city']) ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-tag me-1"></i>
                                        <?= htmlspecialchars($event['category']) ?>
                                    </p>
                                    <?php if ($event['startDate']): ?>
                                        <p class="mb-3 text-muted small">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= htmlspecialchars($event['startDate']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-primary-custom w-100" data-bs-toggle="modal" data-bs-target="#eventModal<?= $event['id'] ?>">
                                        Подробнее
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Модальное окно для деталей события -->
                    <div class="modal fade" id="eventModal<?= $event['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= htmlspecialchars($event['title']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?php if (!empty($event['image'])): ?>
                                                <img src="<?= htmlspecialchars($event['image']) ?>" 
                                                     class="img-fluid rounded mb-3" 
                                                     alt="<?= htmlspecialchars($event['title']) ?>">
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <span class="badge bg-primary me-1">
                                                    <?= htmlspecialchars($event['ageCategory']) ?>
                                                </span>
                                                <?php if ($event['isFree']): ?>
                                                    <span class="badge bg-success">Бесплатно</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Платно</span>
                                                <?php endif; ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($event['category']) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <p><strong>Описание:</strong></p>
                                            <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                            <hr>
                                            <p><i class="fas fa-map-marker-alt me-2"></i> <strong>Город:</strong> <?= htmlspecialchars($event['city']) ?></p>
                                            <?php if (!empty($event['place'])): ?>
                                                <p><i class="fas fa-building me-2"></i> <strong>Место:</strong> <?= htmlspecialchars($event['place']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($event['address'])): ?>
                                                <p><i class="fas fa-map-pin me-2"></i> <strong>Адрес:</strong> <?= htmlspecialchars($event['address']) ?></p>
                                            <?php endif; ?>
                                            <p><i class="fas fa-calendar-alt me-2"></i> <strong>Начало:</strong> <?= htmlspecialchars($event['startDate']) ?></p>
                                            <?php if (!empty($event['endDate'])): ?>
                                                <p><i class="fas fa-calendar-times me-2"></i> <strong>Окончание:</strong> <?= htmlspecialchars($event['endDate']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($event['organizer'])): ?>
                                                <p><i class="fas fa-users me-2"></i> <strong>Организатор:</strong> <?= htmlspecialchars($event['organizer']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Навигация по страницам" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" 
                           href="?page=<?= $i ?>&city=<?= urlencode($filters['city']) ?>&age=<?= $filters['age'] ?>&category=<?= urlencode($filters['category']) ?>&price=<?= $filters['price'] ?>&search=<?= urlencode($filters['search']) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </main>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Культурный навигатор</h5>
                    <p>Находим лучшие мероприятия для детей в вашем городе.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-vk"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-telegram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Навигация</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50 text-decoration-none">Афиша</a></li>
                        <li><a href="#subscriptions" class="text-white-50 text-decoration-none">Подписки</a></li>
                        <li><a href="#reviews" class="text-white-50 text-decoration-none">Отзывы</a></li>
                        <li><a href="#about" class="text-white-50 text-decoration-none">О проекте</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Контакты</h5>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i> info@culture-navigator.ru</p>
                    <p><i class="fas fa-phone me-2"></i> 8-800-123-45-67</p>
                    <p class="small text-white-50 mt-3">
                        Используются данные с портала открытых данных Минкультуры РФ
                    </p>
                </div>
            </div>
            <hr class="bg-white-50">
            <div class="text-center pt-2">
                <p class="small mb-0">© 2024 Культурный навигатор. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>