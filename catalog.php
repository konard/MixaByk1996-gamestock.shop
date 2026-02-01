<?php
// catalog.php - Страница каталога товаров с мгновенной покупкой
session_start();
require_once 'includes/config.php';
require_once 'includes/currency_converter.php';

// Получаем подключение к БД
$pdo = getDBConnection();

// Функция для создания заказа и перенаправления на оплату
function createAndRedirectToPayment($pdo, $product_id, $customer_email = '') {
try {
// Начинаем транзакцию
$pdo->beginTransaction();

// Получаем информацию о товаре
$stmt = $pdo->prepare("SELECT id, name, our_price, stock FROM supplier_products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
throw new Exception("Товар не найден");
}

if ($product['stock'] < 1) {
throw new Exception("Товар закончился");
}

// Генерируем тестовые данные
$login_data = 'user_' . strtoupper(substr(md5(uniqid()), 0, 8));
$password_data = 'pass_' . strtoupper(substr(md5(uniqid()), 0, 10));
$order_number = 'GS' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));

// Если email не указан, используем автоматический
if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
$customer_email = 'customer_' . strtoupper(substr(md5(uniqid()), 0, 8)) . '@gamestock.shop';
$notes = "Мгновенный заказ. Автоматический email";
} else {
$notes = "Мгновенный заказ. Email указан клиентом";
}

$sql = "
INSERT INTO orders (
user_id, order_number, product_id, product_name,
customer_email, total_amount, login_data, password_data,
status, payment_status, notes
) VALUES (0, ?, ?, ?, ?, ?, ?, ?, 'new', 'pending', ?)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
$order_number,
$product['id'],
$product['name'],
$customer_email,
$product['our_price'],
$login_data,
$password_data,
$notes
]);

$order_id = $pdo->lastInsertId();

// Уменьшаем количество товара
$stmt = $pdo->prepare("UPDATE supplier_products SET stock = stock - 1 WHERE id = ?");
$stmt->execute([$product_id]);

// Фиксируем транзакцию
$pdo->commit();

// Перенаправляем сразу на оплату
header('Location: payment.php?order_id=' . $order_id);
exit;

} catch (Exception $e) {
// Откатываем транзакцию при ошибке
if ($pdo->inTransaction()) {
$pdo->rollBack();
}
die("Ошибка создания заказа: " . $e->getMessage());
}
}

// Обработка покупки из формы быстрого заказа
if (isset($_GET['quick_buy']) && isset($_GET['product_id'])) {
$product_id = intval($_GET['product_id']);
$customer_email = isset($_GET['customer_email']) ? trim($_GET['customer_email']) : '';
createAndRedirectToPayment($pdo, $product_id, $customer_email);
exit;
}

// Обработка мгновенной покупки через каталог
if (isset($_GET['buy_now']) && isset($_GET['product_id'])) {
$product_id = intval($_GET['product_id']);
createAndRedirectToPayment($pdo, $product_id);
exit;
}

// Получаем категорию для быстрого заказа (если выбрана)
$quick_category = isset($_GET['quick_category']) ? (int)$_GET['quick_category'] : 0;

// Получаем товары для быстрого заказа по выбранной категории
$quick_products = [];
if ($quick_category > 0) {
$quick_stmt = $pdo->prepare("
SELECT id, name, our_price, stock
FROM supplier_products
WHERE category = ? AND stock > 0
ORDER BY name
");
$quick_stmt->execute([$quick_category]);
$quick_products = $quick_stmt->fetchAll();
}

// Получаем товары для каталога (с пагинацией для отображения)
try {
// Инициализация конвертера валют
$converter = new CurrencyConverter();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
$where_conditions[] = "sp.name LIKE ?";
$params[] = "%$search%";
}

if ($category > 0) {
$where_conditions[] = "sp.category = ?";
$params[] = $category;
}

$where_sql = '';
if (!empty($where_conditions)) {
$where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Общее количество
$total_sql = "SELECT COUNT(*) as total FROM supplier_products sp $where_sql";
$total_stmt = $pdo->prepare($total_sql);
if (!empty($params)) {
$total_stmt->execute($params);
} else {
$total_stmt->execute();
}

$total = $total_stmt->fetchColumn() ?? 0;
$total_pages = ceil($total / $per_page);

// Получаем товары
$sql = "
SELECT sp.*, s.name as supplier_name, s.id as supplier_id
FROM supplier_products sp
LEFT JOIN suppliers s ON sp.supplier_id = s.id
$where_sql
ORDER BY sp.last_updated DESC
LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

} catch (Exception $e) {
$products = [];
$total = 0;
$total_pages = 1;
$search = '';
$category = 0;
}

// Получаем список категорий для выпадающих списков
try {
$category_stmt = $pdo->query("
SELECT DISTINCT category
FROM supplier_products
WHERE stock > 0
ORDER BY category
");
$available_categories = $category_stmt->fetchAll();
} catch (Exception $e) {
$available_categories = [];
}

// ВСЕ КАТЕГОРИИ НА РУССКОМ ЯЗЫКЕ
$category_names = [
2 => 'Фейсбук',
5 => 'Мобильные прокси',
10 => 'Фейсбук Самозарядка',
13 => 'Дискорд',
15 => 'Реддит',
18 => 'Яндекс Дзен',
21 => 'SEO - Ссылки',
25 => 'Скайп',
26 => 'Инстаграм',
29 => 'Google Ads',
30 => 'Яндекс.Директ',
42 => 'Google iOS',
44 => 'TikTok Ads',
50 => 'Твиттер',
51 => 'Epic Games',
53 => 'Трафик/SEO',
68 => 'ВКонтакте',
75 => 'Почта (Email)'
];

$page_title = 'Каталог товаров - ' . SITE_NAME;
require_once 'templates/header.php';
?>
<div class="container">
<h1 class="my-4">🎮 Каталог товаров</h1>
<!-- ФОРМА БЫСТРОГО ЗАКАЗА - ИСПРАВЛЕННАЯ -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-primary text-white">
<h4 class="mb-0"><i class="fas fa-bolt me-2"></i>Быстрый заказ</h4>
</div>
<div class="card-body">
<form method="get" id="quickOrderForm">
<div class="row g-3">
<!-- 1. Выберите категорию -->
<div class="col-md-4">
<label class="form-label fw-bold">1) Выберите категорию *</label>
<select class="form-select" name="quick_category" id="quickCategory" required
onchange="this.form.submit()">
<option value="">-- Выберите категорию --</option>
<?php
foreach ($category_names as $id => $name) {
// Проверяем, есть ли товары в этой категории
$has_products = false;
foreach ($available_categories as $cat) {
if ((int)$cat['category'] == $id) {
$has_products = true;
break;
}
}

if ($has_products) {
echo '<option value="' . $id . '" ';
echo ($quick_category == $id) ? 'selected' : '';
echo '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
}
}
?>
</select>
<small class="form-text text-muted">Отображаются только категории с товарами в наличии</small>
</div>
<!-- 2. Выберите товар (обновляется после выбора категории) -->
<div class="col-md-4">
<label class="form-label fw-bold">2) Выберите товар *</label>
<select class="form-select" name="product_id" id="productSelect" required
<?= empty($quick_products) ? 'disabled' : '' ?>
onchange="updateTotalPrice(this)">
<option value=""><?= empty($quick_products) ? '-- Сначала выберите категорию --' : '-- Выберите товар --' ?></option>

<?php if (!empty($quick_products)): ?>
<?php foreach ($quick_products as $product): ?>
<option value="<?= $product['id'] ?>"
data-price="<?= $product['our_price'] ?>">
<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> -
<?= number_format($product['our_price'], 2) ?> ₽
(<?= $product['stock'] ?> шт.)
</option>
<?php endforeach; ?>
<?php endif; ?>
</select>
</div>
<!-- 3. Email для уведомлений (необязательно) -->
<div class="col-md-4">
<label class="form-label fw-bold">3) Ваш email (необязательно)</label>
<input type="email" class="form-control" name="customer_email"
id="customerEmail" placeholder="your@email.com">
<small class="form-text text-muted">Для уведомлений о заказе</small>
</div>
<!-- Итоговая сумма -->
<div class="col-12">
<div class="border p-3 rounded bg-light">
<h4 class="mb-0 text-center">
<span class="fw-bold">Итоговая сумма к оплате:</span>
<span id="totalAmount" class="text-success ms-2">0.00 ₽</span>
</h4>
</div>
</div>
<!-- Кнопка оплаты -->
<div class="col-12 mt-3">
<button type="submit" class="btn btn-success btn-lg w-100 py-3"
id="payButton"
name="quick_buy"
value="1"
<?= empty($quick_products) ? 'disabled' : '' ?>>
<i class="fas fa-credit-card me-2"></i>ОПЛАТИТЬ СЕЙЧАС
</button>
<small class="text-muted text-center d-block mt-2">
<i class="fas fa-bolt me-1"></i> Мгновенный переход к оплате
</small>
</div>
</div>
</form>
<!-- Сообщение если нет товаров в категории -->
<?php if ($quick_category > 0 && empty($quick_products)): ?>
<div class="alert alert-warning mt-3">
<i class="fas fa-exclamation-triangle me-2"></i>
В выбранной категории нет доступных товаров. Выберите другую категорию.
</div>
<?php endif; ?>
</div>
</div>
<!-- КОНЕЦ ФОРМЫ БЫСТРОГО ЗАКАЗА -->
<!-- Поиск и фильтры -->
<div class="row mb-4">
<div class="col-md-12">
<form method="get" class="row g-3">
<div class="col-md-8">
<input type="text" class="form-control" placeholder="Поиск товаров..."
name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-md-2">
<select class="form-select" name="category">
<option value="0">Все категории</option>
<?php
foreach ($category_names as $id => $name) {
echo '<option value="' . $id . '" ';
echo ($category == $id) ? 'selected' : '';
echo '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
}
?>
</select>
</div>
<div class="col-md-2">
<button type="submit" class="btn btn-primary w-100">Найти</button>
</div>
</form>
</div>
</div>

<?php if (empty($products)): ?>
<div class="alert alert-warning text-center py-5">
<h4>
<?php if (!empty($search) || $category > 0): ?>
😕 Товаров не найдено
<?php else: ?>
😔 Товаров пока нет
<?php endif; ?>
</h4>

<?php if (!empty($search) || $category > 0): ?>
<p class="mb-3">По вашему запросу ничего не найдено. Попробуйте изменить поисковый запрос.</p>
<a href="catalog.php" class="btn btn-primary">Показать все товары</a>
<?php else: ?>
<p class="mb-3">Каталог пуст. Запустите синхронизацию товаров от поставщиков.</p>
<?php if (isset($_SESSION['admin'])): ?>
<a href="/admin/sync_buyaccs.php" class="btn btn-primary btn-lg">🔄 Запустить синхронизацию</a>
<?php endif; ?>
<?php endif; ?>
</div>
<?php else: ?>
<!-- Статистика -->
<div class="row mb-4">
<div class="col-md-3">
<div class="card text-center">
<div class="card-body">
<h5 class="card-title"><?= $total ?></h5>
<p class="card-text">Всего товаров</p>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-center">
<div class="card-body">
<h5 class="card-title"><?= count($products) ?></h5>
<p class="card-text">На этой странице</p>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-center">
<div class="card-body">
<h5 class="card-title"><?= $total_pages ?></h5>
<p class="card-text">Страниц</p>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-center">
<div class="card-body">
<h5 class="card-title">Мгновенно</h5>
<p class="card-text">Оплата</p>
</div>
</div>
</div>
</div>
<!-- Список товаров -->
<div class="row">
<?php foreach ($products as $product): ?>
<?php
// Определяем статус наличия
if ($product['stock'] > 10) {
$stock_class = 'bg-success';
$stock_text = 'В наличии';
} elseif ($product['stock'] > 0) {
$stock_class = 'bg-warning';
$stock_text = 'Мало';
} else {
$stock_class = 'bg-secondary';
$stock_text = 'Нет в наличии';
}

// Определяем категорию
$product_category = $product['category'] ?? 0;
$category_name = $category_names[$product_category] ?? 'Категория ' . $product_category;
?>

<div class="col-lg-4 col-md-6 mb-4">
<div class="card product-card h-100">
<div class="card-body d-flex flex-column">
<div class="d-flex justify-content-between align-items-start mb-2">
<span class="badge bg-light text-dark">
<?= htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8') ?>
</span>
<span class="badge <?= $stock_class ?>">
<?= $stock_text ?> (<?= $product['stock'] ?>)
</span>
</div>
<h5 class="card-title"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h5>
<p class="text-muted mb-3 flex-grow-1" style="font-size: 0.9rem;">
<?= htmlspecialchars(substr($product['name'], 0, 100), ENT_QUOTES, 'UTF-8') ?>...
</p>
<!-- ИСПРАВЛЕНО: Убрано упоминание buy-accs.net -->
<!-- Удален блок с информацией о поставщике -->
<div class="mt-auto">
<div class="d-flex justify-content-between align-items-center mb-2">
<div>
<div class="h4 text-success">
<?= number_format($product['our_price'], 2) ?> ₽
</div>
</div>
</div>
<!-- Кнопка покупки - МГНОВЕННАЯ! -->
<div class="d-grid">
<?php if ($product['stock'] > 0): ?>
<a href="catalog.php?buy_now=1&product_id=<?= $product['id'] ?>"
class="btn btn-primary btn-lg">
<?php if ($product['stock'] > 10): ?>
🚀 Купить сейчас
<?php else: ?>
⚡ Купить (осталось: <?= $product['stock'] ?>)
<?php endif; ?>
</a>
<small class="text-muted text-center mt-1">
<i class="fas fa-bolt"></i> Мгновенный переход к оплате
</small>
<?php else: ?>
<button class="btn btn-secondary btn-lg" disabled>
❌ Нет в наличии
</button>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<!-- Пагинация -->
<?php if ($total_pages > 1): ?>
<nav class="mt-4">
<ul class="pagination justify-content-center">
<?php for ($i = 1; $i <= $total_pages; $i++): ?>
<li class="page-item <?= $i == $page ? 'active' : '' ?>">
<a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>
</ul>
</nav>
<?php endif; ?>
<?php endif; ?>
</div></div></div>
<!-- Chatra {literal} -->
<script>
(function(d, w, c) {
w.ChatraID = 'GXdF3eAtsspXao2vf';
var s = d.createElement('script');
w[c] = w[c] || function() {
(w[c].q = w[c].q || []).push(arguments);
};
s.async = true;
s.src = 'https://call.chatra.io/chatra.js';
if (d.head) d.head.appendChild(s);
})(document, window, 'Chatra');
</script>
<!-- /Chatra {/literal} -->
<style>
.copyright {
padding-top: 1.5rem;
background-color: dodgerblue;
text-align: center;
}
.copyright .list-unstyled li {
display: inline-block;
margin-right: 1rem;
}
.copyright .statement {
text-align: right;
}
.product-card {
transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
border: 1px solid #e9ecef;
}
.product-card:hover {
transform: translateY(-5px);
box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.btn {
transition: all 0.3s ease;
font-weight: 500;
}
.btn:hover:not(:disabled) {
transform: scale(1.02);
box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.btn:disabled {
cursor: not-allowed;
opacity: 0.7;
}
.btn-primary {
background: darkorange;
border: none;
}
.btn-primary:hover {
background: linear-gradient(#f9a020, #e58b07);
}
.btn-success {
background: darkorange;
border: none;
}
.btn-success:hover {
background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
}
#quickOrderForm .form-label {
font-weight: 600;
margin-bottom: 5px;
}
#quickOrderForm .form-control,
#quickOrderForm .form-select {
padding: 10px 15px;
border: 1px solid #ced4da;
border-radius: 8px;
font-size: 1rem;
}
#quickOrderForm .form-control:focus,
#quickOrderForm .form-select:focus {
border-color: #28a745;
box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
#quickOrderForm .card-header {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
#totalAmount {
font-size: 1.5rem;
font-weight: bold;
}
/* Стиль для выбранного товара */
#productSelect option:checked {
background-color: #d1ecf1;
color: #0c5460;
}
</style>
<script>
// Функция обновления итоговой суммы
function updateTotalPrice(selectElement) {
const selectedOption = selectElement.options[selectElement.selectedIndex];
const totalAmountSpan = document.getElementById('totalAmount');
const payButton = document.getElementById('payButton');

if (selectedOption && selectedOption.value !== '') {
const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
totalAmountSpan.textContent = price.toFixed(2) + ' ₽';
payButton.disabled = false;
} else {
totalAmountSpan.textContent = '0.00 ₽';
payButton.disabled = true;
}
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
// Если есть выбранный товар, обновляем сумму
const productSelect = document.getElementById('productSelect');
if (productSelect && productSelect.value) {
updateTotalPrice(productSelect);
}

// Валидация формы быстрого заказа
document.getElementById('quickOrderForm').addEventListener('submit', function(e) {
const category = document.getElementById('quickCategory').value;
const product = document.getElementById('productSelect').value;
const email = document.getElementById('customerEmail').value;

if (!category || category === '') {
e.preventDefault();
alert('Пожалуйста, выберите категорию');
document.getElementById('quickCategory').focus();
return false;
}

if (!product || product === '') {
e.preventDefault();
alert('Пожалуйста, выберите товар');
document.getElementById('productSelect').focus();
return false;
}

// Проверка email если указан
if (email && email !== '') {
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
if (!emailRegex.test(email)) {
e.preventDefault();
alert('Пожалуйста, введите корректный email или оставьте поле пустым');
document.getElementById('customerEmail').focus();
return false;
}
}

// Форма отправится с параметрами: quick_buy=1&product_id=XXX&customer_email=...
return true;
});

// Автоматически выбираем первый товар, если он один
if (productSelect && productSelect.options.length === 2) { // 1 заголовок + 1 товар
productSelect.selectedIndex = 1;
updateTotalPrice(productSelect);
}
});
</script>
