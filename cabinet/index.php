<?php
// cabinet/index.php - Личный кабинет с регистрацией и входом
session_start();
require_once __DIR__ . '/../includes/config.php';

// Обработка выхода
if (isset($_GET['logout'])) {
session_destroy();
header('Location: /cabinet/');
exit();
}

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if (isset($_POST['login'])) {
// ВХОД
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

try {
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['admin'] = (bool)$user['is_admin'];
$_SESSION['email'] = $user['email'];

// Обновляем время последнего входа
$update = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
$update->execute([$user['id']]);

header('Location: /cabinet/');
exit();
} else {
$login_error = "Неверный логин или пароль";
}
} catch (Exception $e) {
$login_error = "Ошибка базы данных";
}
}

elseif (isset($_POST['register'])) {
// РЕГИСТРАЦИЯ
$username = trim($_POST['reg_username'] ?? '');
$email = trim($_POST['reg_email'] ?? '');
$password = $_POST['reg_password'] ?? '';
$password_confirm = $_POST['reg_password_confirm'] ?? '';

$errors = [];

// Валидация
if (strlen($username) < 3) $errors[] = "Логин должен быть не менее 3 символов";
if (strlen($username) > 50) $errors[] = "Логин должен быть не более 50 символов";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некорректный email";
if (strlen($password) < 6) $errors[] = "Пароль должен быть не менее 6 символов";
if ($password !== $password_confirm) $errors[] = "Пароли не совпадают";

if (empty($errors)) {
try {
$pdo = getDBConnection();

// Проверяем уникальность
$check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->execute([$username, $email]);
if ($check->fetch()) {
$errors[] = "Пользователь с таким логином или email уже существует";
} else {
// Создаем пользователя БЕЗ БОНУСА
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("
INSERT INTO users (username, email, password, balance, is_admin, created_at)
VALUES (?, ?, ?, 0.00, FALSE, NOW())
");

if ($stmt->execute([$username, $email, $hashed_password])) {
$user_id = $pdo->lastInsertId();

$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $username;
$_SESSION['admin'] = false;
$_SESSION['email'] = $email;

$register_success = "Регистрация успешна!";
header('Location: /cabinet/');
exit();
}
}
} catch (Exception $e) {
$errors[] = "Ошибка при регистрации: " . $e->getMessage();
}
}

if (!empty($errors)) {
$register_error = implode("<br>", $errors);
}
}
}

// Если пользователь авторизован - показываем кабинет
if (isset($_SESSION['user_id'])) {
try {
$pdo = getDBConnection();

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
session_destroy();
header('Location: /cabinet/');
exit();
}

// Получаем заказы с описанием товара
$orders_stmt = $pdo->prepare("
SELECT o.*, sp.description as product_description
FROM orders o
LEFT JOIN supplier_products sp ON o.product_id = sp.id
WHERE o.user_id = ?
ORDER BY o.created_at DESC
LIMIT 10
");
$orders_stmt->execute([$_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll();

// Получаем транзакции
$transactions_stmt = $pdo->prepare("
SELECT * FROM transactions
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 10
");
$transactions_stmt->execute([$_SESSION['user_id']]);
$transactions = $transactions_stmt->fetchAll();

// Получаем последний оплаченный заказ с данными аккаунта
$last_paid_with_account = null;
foreach ($orders as $order) {
if ($order['payment_status'] === 'paid' && !empty($order['login_data']) && !empty($order['password_data'])) {
$last_paid_with_account = $order;
break;
}
}

$balance = $user['balance'] ?? 0;

} catch (Exception $e) {
if (DEBUG_MODE) {
die("Ошибка БД: " . $e->getMessage());
}
$user = [];
$orders = [];
$transactions = [];
$balance = 0;
$last_paid_with_account = null;
}
}

// Detect if registration tab should be active (from /cabinet/reg/ URL)
$show_register_tab = false;
if (isset($_GET['tab']) && $_GET['tab'] === 'register') {
    $show_register_tab = true;
}
if (strpos($_SERVER['REQUEST_URI'], '/cabinet/reg') !== false) {
    $show_register_tab = true;
}

$page_title = isset($_SESSION['user_id']) ? 'Личный кабинет' : 'Вход и регистрация';
$page_title .= ' - ' . SITE_NAME;
require_once __DIR__ . '/../templates/header.php';
?>
<style>
.auth-container {
max-width: 500px;
margin: 30px auto;
background: white;
border-radius: 20px;
overflow: hidden;
box-shadow: 0 15px 50px rgba(0,0,0,0.2);
}
.auth-tabs {
display: flex;
background: #f8f9fa;
border-bottom: 1px solid #dee2e6;
}
.auth-tab {
flex: 1;
text-align: center;
padding: 20px;
cursor: pointer;
font-weight: 500;
border-bottom: 3px solid transparent;
transition: all 0.3s;
}
.auth-tab.active {
background: white;
border-bottom: 3px solid #007bff;
color: #007bff;
}
.auth-content {
padding: 40px;
}
.auth-form {
display: none;
}
.auth-form.active {
display: block;
}
.cabinet-container {
max-width: 1200px;
margin: 0 auto;
background: white;
border-radius: 15px;
overflow: hidden;
box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}
.user-card {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
color: white;
padding: 40px;
}
.stats-card {
background: white;
border-radius: 10px;
padding: 20px;
margin-bottom: 20px;
box-shadow: 0 2px 10px rgba(0,0,0,0.1);
border: 1px solid #dee2e6;
}
.form-password-toggle {
position: relative;
}
.password-toggle {
position: absolute;
right: 10px;
top: 50%;
transform: translateY(-50%);
cursor: pointer;
color: #6c757d;
}
</style>
<?php if (!isset($_SESSION['user_id'])): ?>
<!-- ФОРМЫ ВХОДА И РЕГИСТРАЦИИ -->
<!-- Favicon  -->
<link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
<div class="auth-container">
<div class="auth-tabs">
<div class="auth-tab active" data-tab ="login">Вход</div>
<div class="auth-tab" data-tab="register">Регистрация</div>
</div>
<div class="auth-content">
<!-- ФОРМА ВХОДА -->
<div id="login-form" class="auth-form active">
<h2 class="mb-4"><i class="fas fa-sign-in-alt me-2"></i>Вход в личный кабинет</h2>

<?php if (isset($login_error)): ?>
<div class="alert alert-danger"><?= $login_error ?></div>
<?php endif; ?>

<form method="POST" id="loginForm">
<input type="hidden" name="login" value="1">
<div class="mb-3">
<label class="form-label">Логин или Email</label>
<input type="text" class="form-control" name="username" required
value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
autocomplete="username">
</div>
<div class="mb-3 form-password-toggle">
<label class="form-label">Пароль</label>
<input type="password" class="form-control" name="password" required
id="loginPassword" autocomplete="current-password">
<span class="password-toggle" onclick="togglePassword('loginPassword')">
<i class="fas fa-eye"></i>
</span>
</div>
<div class="d-grid gap-2 mb-3">
<button type="submit" class="btn btn-primary btn-lg">
<i class="fas fa-sign-in-alt me-2"></i>Войти
</button>
</div>
<div class="text-center">
<a href="#" onclick="showTab('register'); return false;">Нет аккаунта? Зарегистрируйтесь</a>
</div>
</form>
</div>
<!-- ФОРМА РЕГИСТРАЦИИ -->
<div  class="auth-form" id="register-form">
<h2 class="mb-4"><i class="fas fa-user-plus me-2"></i>Регистрация</h2>
<?php if (isset($register_error)): ?>
<div class="alert alert-danger"><?= $register_error ?></div>
<?php endif; ?>

<?php if (isset($register_success)): ?>
<div class="alert alert-success"><?= $register_success ?></div>
<?php endif; ?>

<form method="POST" id="registerForm">
<input type="hidden" name="register" value="1">
<div class="mb-3">
<label class="form-label">Логин *</label>
<input type="text" class="form-control" name="reg_username" required
value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>"
minlength="3" maxlength="50"
pattern="[a-zA-Z0-9_]+"
title="Только латинские буквы, цифры и подчеркивание"
autocomplete="username">
<div class="form-text">Только латинские буквы, цифры и подчеркивание. 3-50 символов.</div>
</div>
<div class="mb-3">
<label class="form-label">Email *</label>
<input type="email" class="form-control" name="reg_email" required
value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>"
autocomplete="email">
</div>
<div class="mb-3 form-password-toggle">
<label class="form-label">Пароль *</label>
<input type="password" class="form-control" name="reg_password" required
id="regPassword" minlength="6"
autocomplete="new-password">
<span class="password-toggle" onclick="togglePassword('regPassword')">
<i class="fas fa-eye"></i>
</span>
<div class="form-text">Не менее 6 символов</div>
</div>
<div class="mb-3 form-password-toggle">
<label class="form-label">Подтверждение пароля *</label>
<input type="password" class="form-control" name="reg_password_confirm" required
id="regPasswordConfirm"
autocomplete="new-password">
<span class="password-toggle" onclick="togglePassword('regPasswordConfirm')">
<i class="fas fa-eye"></i>
</span>
</div>
<div class="d-grid gap-2 mb-3">
<button type="submit" class="btn btn-success btn-lg"><i class="fas fa-user-plus me-2"></i>Зарегистрироваться</button>
</div>
<div class="text-center">
<a href="#" onclick="showTab('login'); return false;">Уже есть аккаунт? Войдите</a>
</div>
</form>
</div>
</div>
<div class="text-center p-3 border-top">
<a href="/" class="btn btn-outline-secondary">
<i class="fas fa-arrow-left me-1"></i>На главную
</a>
</div>
<?php else: ?>
<!-- ЛИЧНЫЙ КАБИНЕТ -->
<div class="cabinet-container">
<!-- Шапка -->
<div class="user-card">
<div class="row align-items-center">
<div class="col-md-8">
<h2><i class="fas fa-user-circle me-2"></i>Личный кабинет</h2>
<p class="mb-0">
Добро пожаловать, <strong><?= htmlspecialchars($user['username']) ?></strong>! |
ID: #<?= $user['id'] ?> |
<?= $user['is_admin'] ? '👑 Администратор' : '👤 Пользователь' ?>
</p>
</div>
<div class="col-md-4 text-md-end">
<h3 class="mb-0">
<i class="fas fa-wallet me-2"></i>
<?= number_format($balance, 2) ?> ₽
</h3>
<small>Ваш баланс</small>
<div class="mt-2">
<a href="?logout" class="btn btn-sm btn-light" onclick="return confirm('Выйти из аккаунта?')">
<i class="fas fa-sign-out-alt me-1"></i>Выйти
</a>
</div>
</div>
</div>
</div>
<!-- Основной контент -->
<div class="container mt-4">
<div class="row">
<div class="col-md-8">
<!-- Заказы -->
<div class="stats-card">
<h4><i class="fas fa-shopping-cart me-2"></i>Мои заказы</h4>
<?php if (empty($orders)): ?>
<div class="text-center py-4">
<i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
<h5>Заказов пока нет</h5>
<p class="text-muted">Вы еще не совершали покупок в нашем магазине</p>
<a href="/catalog.php" class="btn btn-primary">Перейти в каталог</a>
</div>
<?php else: ?>
<div class="table-responsive">
<table class="table">
<thead>
<tr>
<th>№</th>
<th>Товар</th>
<th>Дата</th>
<th>Сумма</th>
<th>Статус</th>
<th>Данные</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $order): ?>
<tr>
<td><?= $order['order_number'] ?></td>
<td>
<?= htmlspecialchars(substr($order['product_name'] ?? 'Без названия', 0, 30)) ?>
<?php if (!empty($order['product_description'])): ?>
<br><small class="text-muted"><?= htmlspecialchars(mb_substr($order['product_description'], 0, 80, 'UTF-8')) ?><?= mb_strlen($order['product_description'] ?? '', 'UTF-8') > 80 ? '...' : '' ?></small>
<?php endif; ?>
</td>
<td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
<td><?= number_format($order['total_amount'], 2) ?> ₽</td>
<td>
<?php
$status_badges = [
'new' => '<span class="badge bg-primary">Новый</span>',
'pending' => '<span class="badge bg-warning">Ожидает</span>',
'processing' => '<span class="badge bg-info">В обработке</span>',
'completed' => '<span class="badge bg-success">Завершен</span>',
'paid' => '<span class="badge bg-success">Оплачен</span>',
'failed' => '<span class="badge bg-danger">Ошибка</span>',
'cancelled' => '<span class="badge bg-secondary">Отменен</span>'
];
echo $status_badges[$order['payment_status']] ?? $status_badges[$order['status']] ?? '<span class="badge bg-secondary">Неизвестно</span>';
?>
</td>
<td>
<?php if (!empty($order['login_data']) && !empty($order['password_data']) && $order['payment_status'] === 'paid'): ?>
<button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#credentials-<?= $order['id'] ?>">
<i class="fas fa-key me-1"></i>Показать
</button>
<?php elseif ($order['payment_status'] === 'paid'): ?>
<span class="badge bg-warning" title="Данные генерируются">⏳</span>
<?php else: ?>
<span class="badge bg-secondary">-</span>
<?php endif; ?>
</td>
</tr>
<?php if (!empty($order['login_data']) && !empty($order['password_data']) && $order['payment_status'] === 'paid'): ?>
<tr class="collapse" id="credentials-<?= $order['id'] ?>">
<td colspan="6">
<div class="p-3 bg-light rounded border">
<div class="row">
<div class="col-md-6 mb-2">
<label class="form-label small text-muted fw-bold">Логин:</label>
<div class="input-group">
<input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($order['login_data']) ?>" readonly id="login-<?= $order['id'] ?>">
<button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('login-<?= $order['id'] ?>')"><i class="fas fa-copy"></i></button>
</div>
</div>
<div class="col-md-6 mb-2">
<label class="form-label small text-muted fw-bold">Пароль:</label>
<div class="input-group">
<input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($order['password_data']) ?>" readonly id="pass-<?= $order['id'] ?>">
<button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('pass-<?= $order['id'] ?>')"><i class="fas fa-copy"></i></button>
</div>
</div>
</div>
<?php if (!empty($order['product_description'])): ?>
<div class="mt-2">
<small class="text-muted"><i class="fas fa-info-circle me-1"></i><?= htmlspecialchars(mb_substr($order['product_description'], 0, 200, 'UTF-8')) ?></small>
</div>
<?php endif; ?>
</div>
</td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
<!-- Транзакции -->
<div class="stats-card">
<h4><i class="fas fa-exchange-alt me-2"></i>История операций</h4>
<?php if (empty($transactions)): ?>
<p class="text-muted">История операций пуста</p>
<?php else: ?>
<div class="table-responsive">
<table class="table">
<thead>
<tr>
<th>Дата</th>
<th>Операция</th>
<th>Сумма</th>
<th>Описание</th>
</tr>
</thead>
<tbody>
<?php foreach ($transactions as $trans): ?>
<tr>
<td><?= date('d.m.Y H:i', strtotime($trans['created_at'])) ?></td>
<td>
<?php
$type_names = [
'deposit' => '<span class="badge bg-success">Пополнение</span>',
'purchase' => '<span class="badge bg-primary">Покупка</span>',
'refund' => '<span class="badge bg-warning">Возврат</span>',
'bonus' => '<span class="badge bg-info">Бонус</span>'
];
echo $type_names[$trans['type']] ?? '<span class="badge bg-secondary">' . $trans['type'] . '</span>';
?>
</td>
<td class="<?= $trans['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
<strong><?= $trans['amount'] > 0 ? '+' : '' ?><?= number_format($trans['amount'], 2) ?> ₽</strong>
</td>
<td><?= htmlspecialchars($trans['description']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>
<div class="col-md-4">
<!-- Профиль -->
<div class="stats-card">
<h4><i class="fas fa-user me-2"></i>Мой профиль</h4>
<div class="mb-3">
<label class="form-label small text-muted">Логин</label>
<div class="form-control"><?= htmlspecialchars($user['username']) ?></div>
</div>
<div class="mb-3">
<label class="form-label small text-muted">Email</label>
<div class="form-control"><?= htmlspecialchars($user['email']) ?></div>
</div>
<div class="mb-3">
<label class="form-label small text-muted">Баланс</label>
<div class="form-control bg-light">
<strong><?= number_format($balance, 2) ?> ₽</strong>
</div>
</div>
<button class="btn btn-primary w-100" onclick="alert('Редактирование профиля скоро будет доступно')">
<i class="fas fa-edit me-2"></i>Редактировать профиль</button>
</div>
<!-- Быстрые действия -->
<div class="stats-card">
<h4><i class="fas fa-bolt me-2"></i>Быстрые действия</h4>
<div class="d-grid gap-2">
<a href="/catalog.php" class="btn btn-outline-primary">
<i class="fas fa-store me-2"></i>Каталог товаров
</a>
<a href="/cabinet/deposit.php" class="btn btn-outline-success">
<i class="fas fa-plus-circle me-2"></i>Пополнить баланс
</a>
<?php if ($user['is_admin']): ?>
<a href="/admin/" class="btn btn-outline-warning">
<i class="fas fa-cog me-2"></i>Админ-панель</a>
<?php endif; ?>
</div>
</div>
<!-- Информация -->
<div class="stats-card">
<h4><i class="fas fa-info-circle me-2"></i>Информация</h4>
<ul class="list-unstyled">
<li class="mb-2">
<i class="fas fa-calendar me-2 text-primary"></i>
Регистрация: <?= date('d.m.Y', strtotime($user['created_at'])) ?>
</li>
<li class="mb-2">
<i class="fas fa-shopping-cart me-2 text-success"></i>
Заказов: <?= count($orders) ?>
</li>
<li>
<i class="fas fa-exchange-alt me-2 text-info"></i>
Операций: <?= count($transactions) ?>
</li>
</ul>
</div>
</div>
</div><!-- /.row -->
</div><!-- /.container mt-4 -->
</div><!-- /.cabinet-container -->
<?php endif; ?>
<script>
// Глобальная функция для переключения вкладок
function showTab(tabName) {
// Ждем полной загрузки DOM
if (document.readyState === 'loading') {
document.addEventListener('DOMContentLoaded', function() {
showTab(tabName);
});
return;
}

// Находим все элементы
const tabs = document.querySelectorAll('.auth-tab');
const forms = document.querySelectorAll('.auth-form');

// Проверяем, что элементы существуют
if (tabs.length === 0 || forms.length === 0) {
setTimeout(function() {
showTab(tabName);
}, 100);
return;
}

// Переключаем вкладки
tabs.forEach(tab => {
tab.classList.remove('active');
});
forms.forEach(form => {
form.classList.remove('active');
});

// Активируем нужные элементы
const activeTab = document.querySelector(`.auth-tab[data-tab="${tabName}"]`);
const activeForm = document.getElementById(`${tabName}-form`);

if (activeTab) activeTab.classList.add('active');
if (activeForm) activeForm.classList.add('active');

// Отменяем стандартное действие ссылки
return false;
}

// Добавляем обработчики на вкладки после загрузки
document.addEventListener('DOMContentLoaded', function() {
// Обработчики для вкладок
document.querySelectorAll('.auth-tab').forEach(tab => {
tab.addEventListener('click', function() {
const tabName = this.getAttribute('data-tab');
showTab(tabName);
});
});

// Обработчики для ссылок в формах
document.querySelectorAll('a[onclick*="showTab"]').forEach(link => {
const oldOnClick = link.getAttribute('onclick');
link.removeAttribute('onclick');
link.addEventListener('click', function(e) {
e.preventDefault();
const tabName = oldOnClick.includes("'register'") ? 'register' : 'login';
showTab(tabName);
});
});

// Автоматический фокус на первой форме
<?php if (isset($_POST['register']) || isset($register_error) || $show_register_tab): ?>
showTab('register');
<?php else: ?>
document.querySelector('input[name="username"]')?.focus();
<?php endif; ?>
});

// Функция показа/скрытия пароля
function togglePassword(inputId) {
const input = document.getElementById(inputId);
const icon = input.nextElementSibling.querySelector('i');

if (input.type === 'password') {
input.type = 'text';
icon.classList.remove('fa-eye');
icon.classList.add('fa-eye-slash');
} else {
input.type = 'password';
icon.classList.remove('fa-eye-slash');
icon.classList.add('fa-eye');
}
}

// Копирование в буфер обмена
function copyToClipboard(inputId) {
const input = document.getElementById(inputId);
input.select();
input.setSelectionRange(0, 99999); // Для мобильных устройств

try {
const successful = document.execCommand('copy');
const button = event.target.closest('button');
const originalHTML = button.innerHTML;

button.innerHTML = '<i class="fas fa-check"></i>';
button.classList.remove('btn-outline-secondary');
button.classList.add('btn-success');

setTimeout(() => {
button.innerHTML = originalHTML;
button.classList.remove('btn-success');
button.classList.add('btn-outline-secondary');
}, 1500);

} catch (err) {
alert('Не удалось скопировать. Скопируйте вручную.');
}
}

// Валидация формы регистрации
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
const password = document.querySelector('input[name="reg_password"]');
const confirm = document.querySelector('input[name="reg_password_confirm"]');

if (password.value !== confirm.value) {
e.preventDefault();
alert('Пароли не совпадают!');
confirm.focus();
}
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
