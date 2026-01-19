<?php
header('Content-Type: text/html; charset=utf-8');
// Добавляем проверку сессии
if (!isset($_SESSION)) {
    session_start();
}

// Определяем текущую страницу
$current_page = basename($_SERVER['PHP_SELF']);
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
                <a href="subscriptions.php" class="nav-link <?= $current_page == 'subscriptions.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell me-1"></i> Подписки</a>

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