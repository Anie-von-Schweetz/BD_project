<?php
// account_new.php - ПОЛНОСТЬЮ ПЕРЕПИСАННАЯ ВЕРСИЯ
session_start();

require_once __DIR__ . '/../Backend/database.php';
require_once __DIR__ . '/../Backend/config.php';
require_once __DIR__ . '/../Backend/includes/ApiClient.php';

$error = '';
$success = '';
$show_form = false;

// ================== ПОЛУЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ ==================
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';

$connection = connectDB();

// ================== ЗАПИСЬ НОВЫЙ ПОДПИСКИ ==================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_subscription'])) {

    $subname = $_POST['subname'] ?? '';
    $event_categories = $_POST['event_categories'] ?? '';
    $city = trim($_POST['city'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $is_free_only = isset($_POST['is_free_only']) ? 1 : 0;
    $price_max = !empty($_POST['price_max']) ? intval($_POST['price_max']) : null;
    

    $stmt = $connection->prepare("INSERT INTO subscriptions (user_id, subname, city, age, event_categories, is_free_only, price_max) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $_SESSION['user_id'], $subname, $city, $age, $event_categories, $is_free_only, $price_max);
    
    if ($stmt->execute()) {
        $success = 'Подписка успешно создана!';
        
        // Обновляем страницу
        header('Location: account.php?success=created');
        exit();
    } else {
        $error = 'Ошибка при создании подписки: ' . $connection->error;
        echo "<!-- DEBUG SQL Error: " . $connection->error . " -->";
    }
    $stmt->close();
}

// ================== ОБНОВЛЕНИЕ ПОДПИСКИ ==================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_subscription'])) {

    $id = intval($_POST['subscription_id'] ?? 0);
    $subname = $_POST['subname'] ?? '';
    $event_categories = $_POST['event_categories'] ?? '';
    $city = trim($_POST['city'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $is_free_only = isset($_POST['is_free_only']) ? 1 : 0;
    $price_max = !empty($_POST['price_max']) ? intval($_POST['price_max']) : null;
    
    $updateStmt = $connection->prepare("UPDATE subscriptions SET subname = ?, city = ?, age = ?, event_categories = ?, is_free_only = ?, price_max = ? WHERE id = ? AND user_id = ?");
    $updateStmt->bind_param("ssisiiii", $subname, $city, $age, $event_categories, $is_free_only, $price_max, $id, $_SESSION['user_id']);

    if ($updateStmt->execute()) {
        $success = 'Подписка успешно обновлена!';           
        header('Location: account.php?success=updated');
        exit();
    } else {
        $error = 'Ошибка при обновлении подписки: ' . $connection->error;
    }

    $updateStmt->close();
}

// ================== УДАЛЕНИЕ ПОДПИСКИ ==================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    if ($user_id > 0) {
        $connection = connectDB();
        $stmt = $connection->prepare("DELETE FROM subscriptions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $connection->close();
            header('Location: account.php?success=deleted');
            exit();
        }
        $stmt->close();
        $connection->close();
    }
}

// ================== РЕДАКТИРОВАНИЕ ПОДПИСКИ ==================
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    $stmt = $connection->prepare("SELECT * FROM subscriptions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_subscription = $result->fetch_assoc();
        $show_form = true;
    } else {
        $error = 'Подписка не найдена';
    }
    $stmt->close();
}


// Получаем данные для формы
$api = new ApiClient(API_KEY);
$cities = $api->getUniqueCities();
$categories = $api->getUniqueCategories();

// Получаем список подписок
$subscriptions = [];
if ($user_id > 0) {
    $connection = connectDB();
    $stmt = $connection->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }
    $stmt->close();
}

$connection->close();

// ================== СООБЩЕНИЯ ИЗ GET ==================
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created': $success = 'Подписка успешно создана!'; break;
        case 'updated': $success = 'Подписка успешно обновлена!'; break;
        case 'deleted': $success = 'Подписка успешно удалена!'; break;
    }
}

// ================== HTML ==================
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Культурный навигатор | Мой аккаунт</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="img/icon.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container py-4">
        <div class="account-str row">
            <div class="col">
                <h1 class="mb-4">Мой аккаунт</h1>
            </div>    
            <div class="col-md-6 container-logout">
                <a href="logout.php" class="btn btn-primary-custom btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Выйти из аккаунта
                </a>
            </div>
        </div>
        
        <!-- Информация о пользователе -->
        <div class="user-info-card">
            <div class="row">
                <div class="col-md-6">
                    <h3><i class="fas fa-user-circle me-2"></i> Профиль</h3>
                    <p class="mb-2"><strong>Имя пользователя:</strong> <?= htmlspecialchars($username) ?></p>
                    <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="text-muted mb-2">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Зарегистрирован: <?= date('d.m.Y') ?>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-bell me-1"></i>
                        Активных подписок: <?= count($subscriptions) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Сообщения об ошибках/успехе -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Простая форма как на главной -->
        <div class="card mb-4">
            <div class="card-body">
                <button class="btn btn-primary-custom mb-2" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#subscriptionFormCollapse" 
                    aria-expanded="<?= $show_form ? 'true' : 'false' ?>"
                    aria-controls="subscriptionFormCollapse">
                    <i class="fas fa-plus me-2"></i>
                    <?= isset($edit_subscription) ? 'Редактировать подписку' : 'Создать новую подписку' ?>
                </button>
                
                <!-- Форма создания/редактирования подписки -->
                <div class="collapse <?= $show_form ? 'show' : '' ?>" id="subscriptionFormCollapse">
                    <div class="card card-body mb-4">
                        <h3 class="mb-3">
                            <i class="fas fa-bell me-2"></i>
                            <?= isset($edit_subscription) ? 'Редактировать подписку' : 'Настройка подписки' ?>
                        </h3>

                        <form method="POST" action="">

                            <?php if (isset($edit_subscription)): ?>
                                <input type="hidden" name="subscription_id" value="<?= $edit_subscription['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label required-field">Название подписки</label>
                                <input type="text" class="form-control" name="subname" required
                                value="<?= isset($edit_subscription) ? htmlspecialchars($edit_subscription['subname']) : '' ?>">
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <!-- Город -->
                                <div class="col-md-6">
                                    <label class="form-label">Город</label>
                                    <select class="form-select" name="city">
                                        <option value="">Все города</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city) ?>"
                                            <?= (isset($edit_subscription) && $edit_subscription['city'] == $city) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($city) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Возраст ребенка -->
                                <div class="col-md-6">
                                    <label class="form-label">Возраст ребёнка</label>
                                    <select class="form-select" name="age">
                                        <option value="0">Любой возраст</option>
                                        <option value="0"
                                        <?= (isset($edit_subscription) && $edit_subscription['age'] == 0) ? 'selected' : '' ?>>
                                        0+</option>
                                        <option value="6"
                                        <?= (isset($edit_subscription) && $edit_subscription['age'] == 6) ? 'selected' : '' ?>>
                                        6+</option>
                                        <option value="12"
                                        <?= (isset($edit_subscription) && $edit_subscription['age'] == 12) ? 'selected' : '' ?>>
                                        12+</option>
                                        <option value="16"
                                        <?= (isset($edit_subscription) && $edit_subscription['age'] == 16) ? 'selected' : '' ?>>
                                        16+</option>
                                    </select>
                                </div>

                                <!-- Категория -->                
                                <div class="mb-3">
                                    <label class="form-label required-field">Категория мероприятия</label>
                                    <select class="form-select" name="event_categories" required>
                                        <option value="">Выберите категорию</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>"
                                                <?= (isset($edit_subscription) && $edit_subscription['event_categories'] == $category) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            

                            <div class="mb-3">
                                <!-- Только бесплатные -->
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_free_only" value="1" <?= (isset($edit_subscription) && $edit_subscription['is_free_only']) ? 'checked' : '' ?>>
                                    <label class="form-check-label">Только бесплатные мероприятия</label>
                                </div>
                                <!-- Максимальная цена -->
                                <div class="col-md-6">
                                    <label for="price_filter" class="form-label">Цена</label>
                                    <select class="form-select" id="price_filter" name="price_filter">
                                        <option value="">Любые</option>
                                        <option value="free" <?= (isset($edit_subscription) && ($edit_subscription['price_filter'] ?? '') == 'free') ? 'selected' : '' ?>>Бесплатные</option>
                                        <option value="paid" <?= (isset($edit_subscription) && ($edit_subscription['price_filter'] ?? '') == 'paid') ? 'selected' : '' ?>>Платные</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="price_max" class="form-label">Максимальная цена</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="price_max" name="price_max" min="0"
                                            value="<?= isset($edit_subscription) ? htmlspecialchars($edit_subscription['price_max'] ?? '') : '' ?>">
                                        <span class="input-group-text">руб.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Информационное сообщение -->
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Вы можете применять параметры указанных фильтров для поиска подходящих мероприятий!
                            </div>
                            
                            <!-- Кнопки формы -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="account.php" class="btn btn-outline-secondary me-md-2">Отмена</a>
                                <button type="submit" name="<?= isset($edit_subscription) ? 'update_subscription' : 'create_subscription' ?>" 
                                        class="btn btn-primary-custom">
                                    <?= isset($edit_subscription) ? 'Обновить подписку' : 'Создать подписку' ?>
                                </button>
                            </div>
                        </form>
                    </div>    
                </div>
                <!-- Раздел подписок -->
                <div class="subscriptions-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">
                            <i class="fas fa-bell me-2"></i>Мои подписки
                        </h2>
                    </div>

                    <!-- Список подписок -->
                    <?php if (empty($subscriptions)): ?>
                        <div class="empty-subscriptions">
                            <i class="far fa-bell-slash"></i>
                            <h4>У вас еще нет подписок</h4>
                            <p>Создайте свою первую подписку, чтобы получать уведомления о новых мероприятиях</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($subscriptions as $subscription): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="subscription-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="mb-2"><?= htmlspecialchars($subscription['subname']) ?></h5>
                                        </div>
                                        
                                        <!-- Параметры подписки -->
                                        <div class="subscription-params">
                                            <?php if (!empty($subscription['city'])): ?>
                                                <span class="param-badge city">
                                                    <i class="fas fa-city"></i> <?= htmlspecialchars($subscription['city']) ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($subscription['age'] > 0): ?>
                                                <span class="param-badge age">
                                                    <i class="fas fa-child"></i> <?= $subscription['age'] ?>+
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($subscription['event_categories'])): ?>
                                                <span class="param-badge category">
                                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($subscription['event_categories']) ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($subscription['is_free_only']): ?>
                                                <span class="param-badge price">
                                                    <i class="fas fa-coins"></i> Бесплатные
                                                </span>
                                            <?php elseif (!empty($subscription['price_max'])): ?>
                                                <span class="param-badge price">
                                                    <i class="fas fa-ruble-sign"></i> До <?= $subscription['price_max'] ?> руб.
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-muted small mb-0">
                                            <i class="far fa-clock me-1"></i>
                                            Создана: <?= date('d.m.Y', strtotime($subscription['created_at'])) ?>
                                        </p>
                                        
                                        <div class="subscription-actions">
                                            <a href="account.php?edit=<?= $subscription['id'] ?>" 
                                            class="btn btn-outline-primary-custom btn-sm">
                                                <i class="fas fa-edit me-1"></i> Редактировать
                                            </a>
                                            <a href="account.php?delete=<?= $subscription['id'] ?>" 
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Вы уверены, что хотите удалить подписку \"<?= htmlspecialchars(addslashes($subscription['subname'])) ?>\"?')">
                                                <i class="fas fa-trash me-1"></i> Удалить
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <?php include "footer.html" ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>