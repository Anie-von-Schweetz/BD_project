<?php
header('Content-Type: text/html; charset=utf-8');
// Добавляем проверку сессии
if (!isset($_SESSION)) {
    session_start();
}

// Определяем текущую страницу
$current_page = basename($_SERVER['PHP_SELF']);

// Получаем подписки пользователя, если он авторизован
$user_subscriptions = [];
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../Backend/database.php';
    $connection = connectDB();
    
    $stmt = $connection->prepare("SELECT id, subname, city, age, event_categories, is_free_only, price_max FROM subscriptions WHERE user_id = ? ORDER BY subname");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $user_subscriptions[] = $row;
    }
    
    $stmt->close();
    $connection->close();
}
?>

<header>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="img/hand-print-white.png" alt="Логотип">Культурный навигатор
            </a>
            
            <div class="navbar-nav ms-auto">
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt me-1"></i> Афиша</a>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $current_page == 'subscriptions.php' ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell me-1"></i> Подписки
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                            <?php if (!empty($user_subscriptions)): ?>
                                <li><h6 class="dropdown-header">Мои подписки</h6></li>
                                <?php foreach ($user_subscriptions as $subscription): ?>
                                    <li>
                                        <a class="dropdown-item apply-subscription-filter" 
                                           href="#" 
                                           data-city="<?= htmlspecialchars($subscription['city']) ?>"
                                           data-age="<?= $subscription['age'] ?>"
                                           data-category="<?= htmlspecialchars($subscription['event_categories']) ?>"
                                           data-free-only="<?= $subscription['is_free_only'] ?>"
                                           data-price-max="<?= $subscription['price_max'] ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="subscription-name">
                                                    <?= htmlspecialchars($subscription['subname']) ?>
                                                </span>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endforeach; ?>
                                <li><a class="dropdown-item text-primary" href="account.php"><i class="fas fa-cog me-1"></i> Управление подписками</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item disabled" href="#">Нет активных подписок</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-primary" href="account.php"><i class="fas fa-plus me-1"></i> Создать подписку</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a class="dropdown-item disabled" href="#">Войдите для просмотра подписок</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-primary" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Войти</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <!-- Пользовательские элементы -->
                <?php if (isset($_SESSION['username']) && !empty($_SESSION['username'])): ?>
                    <!-- Авторизованный пользователь -->
                    <a href="account.php" class="nav-link <?= $current_page == 'account.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-circle"></i>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Аккаунт') ?></span>
                    </a>

                <?php else: ?>
                    <!-- Неавторизованный пользователь -->    
                    <a href="login.php" class="nav-link <?= $current_page == 'subscriptions.php' ? 'active' : '' ?>">
                        <i class="fas fa-user me-1"></i> Войти</a>
                <?php endif; ?>        
            </div>
        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка кликов по подпискам
    document.querySelectorAll('.apply-subscription-filter').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Получаем данные подписки
            const city = this.getAttribute('data-city');
            const age = this.getAttribute('data-age');
            const category = this.getAttribute('data-category');
            const freeOnly = this.getAttribute('data-free-only');
            const priceMax = this.getAttribute('data-price-max');
            
            // Собираем URL для фильтрации
            let url = 'index.php?';
            const params = [];
            
            if (city) params.push('city=' + encodeURIComponent(city));
            if (age && age != '0') params.push('age=' + encodeURIComponent(age));
            if (category) params.push('category=' + encodeURIComponent(category));
            
            // Обрабатываем цену
            if (freeOnly == '1') {
                params.push('price=free');
            } else if (priceMax && priceMax != '') {
                params.push('price=paid');
                // Можно также добавить логику для максимальной цены
            }
            
            // Если есть параметры - перенаправляем
            if (params.length > 0) {
                window.location.href = url + params.join('&');
            } else {
                // Если нет параметров - просто переходим на главную
                window.location.href = 'index.php';
            }
        });
    });
    
    // Закрываем dropdown при клике на элемент
    document.querySelectorAll('.dropdown-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const dropdown = this.closest('.dropdown');
            if (dropdown) {
                const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    bootstrap.Dropdown.getInstance(dropdownToggle)?.hide();
                }
            }
        });
    });
});
</script>