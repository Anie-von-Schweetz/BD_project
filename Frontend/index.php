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
    <script src="js/api.js" defer></script>
    <script src="js/ui.js" defer></script>
    <script src="js/app.js" defer></script>
</head>
<body>
    <!-- Хедер -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="img/hand-print-white.png"></img>Культурный навигатор
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="#"><i class="fas fa-calendar-alt me-1"></i> Афиша</a>
                <a class="nav-link" href="#subscriptions"><i class="fas fa-bell me-1"></i> Подписки</a>
                <a class="nav-link" href="#login"><i class="fas fa-user me-1"></i> Войти</a>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <main class="container py-4" id="app">
        <div class="search-section">
            <h1 class="h3 mb-4">Найди мероприятия для ребёнка</h1>
            
            <!-- Строка поиска -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Например: кукольный театр, выставка динозавров...">
                        <button class="btn btn-primary-custom" type="button" id="searchButton">
                            Найти
                        </button>
                    </div>
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
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Город</label>
                            <select class="form-select" id="cityFilter">
                                <option value="">Все города</option>
                                <!-- Города будут загружены через JS -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Возраст ребёнка</label>
                            <select class="form-select" id="ageFilter">
                                <option value="">Любой возраст</option>
                                <option value="0">0+</option>
                                <option value="6">6+</option>
                                <option value="12">12+</option>
                                <option value="16">16+</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Категория</label>
                            <select class="form-select" id="categoryFilter">
                                <option value="">Все категории</option>
                                <!-- Категории будут загружены через JS -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Цена</label>
                            <select class="form-select" id="priceFilter">
                                <option value="">Любая</option>
                                <option value="free">Бесплатные</option>
                                <option value="paid">Платные</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-outline-danger me-2" id="resetFilters">
                            Сбросить
                        </button>
                        <button class="btn btn-primary-custom" id="applyFilters">
                            Применить фильтры
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Контейнер для мероприятий -->
        <div id="eventsContainer">
            <div class="row" id="eventsList">
                <!-- Мероприятия будут загружены через JavaScript -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary-custom" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-3">Загружаем афишу мероприятий...</p>
                </div>
            </div>
            
            <!-- Пагинация -->
            <nav aria-label="Навигация по страницам" class="mt-4">
                <ul class="pagination justify-content-center" id="pagination">
                    <!-- Пагинация будет сгенерирована через JS -->
                </ul>
            </nav>
        </div>
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
                        <li><a href="subscriptions.php" class="text-white-50 text-decoration-none">Подписки</a></li>
                        <li><a href="reviews.php" class="text-white-50 text-decoration-none">Отзывы</a></li>
                        <li><a href="about.php" class="text-white-50 text-decoration-none">О проекте</a></li>
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