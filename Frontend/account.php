<?php
// account_new.php - ПОЛНОСТЬЮ ПЕРЕПИСАННАЯ ВЕРСИЯ
session_start();

require_once __DIR__ . '/../Backend/database.php';
require_once __DIR__ . '/../Backend/config.php';
require_once __DIR__ . '/../Backend/includes/ApiClient.php';

$error = '';
$success = '';

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
    

    $updateStmt = $connection->prepare("UPDATE subscriptions SET subname = ?, city = ?, age = ?, event_categories = ?, is_free_only = ?, price_max = ? WHERE id = ? AND user_id = ?");    $stmt->bind_param("issssss", $_SESSION['user_id'], $subname, $city, $age, $event_categories, $is_free_only, $price_max);
    $updateStmt->bind_param("ssisiiii", $subname, $city, $age, $event_categories, $is_free_only, $price_max, $id, $user_id);

    $stmt->close();
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container py-4">
        <h1 class="mb-4">Мой аккаунт</h1>
        
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
                <button class="btn btn-primary-custom" type="button" data-bs-toggle="collapse" 
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
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label required-field">Название подписки</label>
                                <input type="text" class="form-control" name="subname" required>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Город</label>
                                    <select class="form-select" name="city">
                                        <option value="">Все города</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Возраст ребёнка</label>
                                    <select class="form-select" name="age">
                                        <option value="0">Любой возраст</option>
                                        <option value="0">0+</option>
                                        <option value="6">6+</option>
                                        <option value="12">12+</option>
                                        <option value="16">16+</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required-field">Категория мероприятия</label>
                                <select class="form-select" name="event_categories" required>
                                    <option value="">Выберите категорию</option>
                                    <option value="Кино">Кино</option>
                                    <option value="Театр">Театр</option>
                                    <option value="Выставка">Выставка</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_free_only" value="1">
                                    <label class="form-check-label">Только бесплатные мероприятия</label>
                                </div>
                            </div>
                            
                            <button type="submit" name="create_subscription" class="btn btn-primary-custom">
                                Создать подписку
                            </button>
                        </form>
            </div>
        </div>

        <!-- Список подписок -->
        <div class="card">
            <div class="card-body">
                <h3 class="mb-3"><i class="fas fa-bell me-2"></i>Мои подписки</h3>
                
                <?php if (empty($subscriptions)): ?>
                    <p class="text-muted">У вас еще нет подписок.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Город</th>
                                    <th>Возраст</th>
                                    <th>Категория</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sub['subname']) ?></td>
                                    <td><?= htmlspecialchars($sub['city'] ?: 'Все') ?></td>
                                    <td><?= $sub['age'] > 0 ? $sub['age'].'+' : 'Любой' ?></td>
                                    <td><?= htmlspecialchars($sub['event_categories']) ?></td>
                                    <td>
                                        <a href="account.php?delete=<?= $sub['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Удалить подписку?')">
                                            Удалить
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>