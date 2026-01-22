<?php
session_start();
require_once __DIR__ . '/../Backend/database.php';

// Проверяем, авторизован ли пользователь
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$action = $_GET['action'] ?? 'login'; // login или register

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $connection = connectDB();
        $email = sanitize($connection, $email);
        
        // Ищем пользователя по email
        $stmt = $connection->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Проверяем пароль
            if (password_verify($password, $user['password_hash'])) {
                // Успешная авторизация
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь с таким email не найден';
        }
        
        $stmt->close();
        $connection->close();
    }
}

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Базовая валидация
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Заполните все поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email';
    } else {
        $connection = connectDB();
        $username = sanitize($connection, $username);
        $email = sanitize($connection, $email);
        
        // Проверяем, не занят ли email
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Пользователь с таким email уже зарегистрирован';
        } else {
            // Хэшируем пароль
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Создаем пользователя
            $stmt = $connection->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                // Автоматически авторизуем пользователя после регистрации
                $user_id = $connection->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Ошибка при регистрации: ' . $connection->error;
            }
        }
        
        $stmt->close();
        $connection->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация | Культурный навигатор</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Хедер -->
    <?php include 'header.php'; ?>

    <!-- Основной контент -->
    <main class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h2>Добро пожаловать!</h2>
                <p class="text-muted">Найдите лучшие мероприятия для вашего ребенка</p>
            </div>

            <!-- Вкладки -->
            <div class="auth-tabs">
                <button class="auth-tab <?= $action == 'login' ? 'active' : '' ?>" 
                        onclick="showForm('login')">
                    <i class="fas fa-sign-in-alt me-2"></i>Вход
                </button>
                <button class="auth-tab <?= $action == 'register' ? 'active' : '' ?>" 
                        onclick="showForm('register')">
                    <i class="fas fa-user-plus me-2"></i>Регистрация
                </button>
            </div>

            <!-- Сообщения об ошибках/успехе -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Форма входа -->
            <form id="loginForm" class="auth-form <?= $action == 'login' ? 'active' : '' ?>" 
                  method="POST" action="">
                <div class="mb-3">
                    <label for="loginEmail" class="form-label">
                        <i class="fas fa-envelope me-1"></i>Email адрес
                    </label>
                    <input type="email" class="form-control" id="loginEmail" 
                           name="email" required 
                           placeholder="Ваш email"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="mb-4">
                    <label for="loginPassword" class="form-label">
                        <i class="fas fa-lock me-1"></i>Пароль
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="loginPassword" 
                               name="password" required 
                               placeholder="Ваш пароль">
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="login" class="btn btn-primary-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>Войти
                    </button>
                </div>
            </form>

            <!-- Форма регистрации -->
            <form id="registerForm" class="auth-form <?= $action == 'register' ? 'active' : '' ?>" 
                  method="POST" action="" onsubmit="return validateRegistration()">
                <div class="mb-3">
                    <label for="registerUsername" class="form-label">
                        <i class="fas fa-user me-1"></i>Имя пользователя
                    </label>
                    <input type="text" class="form-control" id="registerUsername" 
                           name="username" required 
                           placeholder="Как вас зовут?"
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                
                <div class="mb-3">
                    <label for="registerEmail" class="form-label">
                        <i class="fas fa-envelope me-1"></i>Email адрес
                    </label>
                    <input type="email" class="form-control" id="registerEmail" 
                           name="email" required 
                           placeholder="Ваш email"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="mb-3">
                    <label for="registerPassword" class="form-label">
                        <i class="fas fa-lock me-1"></i>Пароль
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="registerPassword" 
                               name="password" required 
                               placeholder="Придумайте пароль (минимум 6 символов)"
                               onkeyup="checkPasswordStrength()">
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="togglePassword('registerPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div id="passwordStrengthBar" class="password-strength-bar"></div>
                    </div>
                    <div id="passwordStrengthText" class="form-text"></div>
                </div>
                
                <div class="mb-4">
                    <label for="confirmPassword" class="form-label">
                        <i class="fas fa-lock me-1"></i>Подтверждение пароля
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" 
                               name="confirm_password" required 
                               placeholder="Повторите пароль"
                               onkeyup="checkPasswordMatch()">
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="togglePassword('confirmPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" class="password-match"></div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="termsCheck" required>
                    <label class="form-check-label" for="termsCheck">
                        Я согласен с <a href="#terms" class="text-decoration-none">правилами использования</a> 
                        и <a href="#privacy" class="text-decoration-none">политикой конфиденциальности</a>
                    </label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="register" class="btn btn-primary-custom">
                        <i class="fas fa-user-plus me-2"></i>Зарегистрироваться
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="text-center pt-3">
                <p class="small mb-0">© 2024 Культурный навигатор. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Показать нужную форму
        function showForm(formType) {
            // Обновляем вкладки
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.auth-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Показываем выбранную форму
            document.getElementById(formType + 'Form').classList.add('active');
            event.target.classList.add('active');
            
            // Обновляем URL без перезагрузки страницы
            history.pushState(null, null, '?action=' + formType);
        }
        
        // Проверка совпадения паролей
        function checkPasswordMatch() {
            const password = document.getElementById('registerPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.textContent = '';
                matchDiv.className = 'password-match';
            } else if (password === confirm) {
                matchDiv.textContent = '✓ Пароли совпадают';
                matchDiv.className = 'password-match valid';
            } else {
                matchDiv.textContent = '✗ Пароли не совпадают';
                matchDiv.className = 'password-match invalid';
            }
        }
        
        // Валидация формы регистрации
        function validateRegistration() {
            const password = document.getElementById('registerPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            // Проверяем длину пароля
            if (password.length < 6) {
                alert('Пароль должен содержать минимум 6 символов');
                return false;
            }
            
            // Проверяем совпадение паролей
            if (password !== confirm) {
                alert('Пароли не совпадают');
                return false;
            }
            
            // Проверяем согласие с правилами
            if (!document.getElementById('termsCheck').checked) {
                alert('Необходимо принять правила использования');
                return false;
            }
            
            return true;
        }
        
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Проверяем начальное состояние
            const password = document.getElementById('registerPassword');
            if (password) {
                checkPasswordMatch();
            }
        });
    </script>
</body>
</html>