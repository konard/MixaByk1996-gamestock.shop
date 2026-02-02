<?php
// index.php - Обработка быстрого заказа на главной
session_start();
require_once 'includes/config.php';
// Получаем подключение к БД
$pdo = getDBConnection();
// Функция для создания заказа и перенаправления на оплату (ИЗ КАТАЛОГА)
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

// Перенаправляем сразу на оплату с флагом быстрого заказа
header('Location: payment.php?order_id=' . $order_id . '&fast_order=1');
exit;

} catch (Exception $e) {
// Откатываем транзакцию при ошибке
if ($pdo->inTransaction()) {
$pdo->rollBack();
}
// Сохраняем ошибку в сессии для показа на странице
$_SESSION['order_error'] = "Ошибка создания заказа: " . $e->getMessage();
return false;
}
}
// Обработка формы быстрого заказа на главной странице
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
$product_id = intval($_POST['product_id']);
$customer_email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($product_id > 0) {
if (createAndRedirectToPayment($pdo, $product_id, $customer_email)) {
// Редирект уже выполнен в функции
exit;
}
} else {
$_SESSION['order_error'] = "Выберите товар";
}
}
// Получаем список товаров для выпадающего списка (только те что в наличии)
try {
$stmt = $pdo->query("SELECT id, name, our_price, stock FROM supplier_products WHERE stock > 0 ORDER BY name LIMIT 50");
$available_products = $stmt->fetchAll();
} catch (Exception $e) {
$available_products = [];
}
?>
<!DOCTYPE html>
<html lang="ru-RU">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<!-- SEO Meta Tags -->
<meta name="description" content="Pavo is a mobile app Tailwind CSS HTML template created to help you present benefits, features and information about mobile apps in order to convince visitors to download them" />
<meta name="author" content="Your name" />
<!-- OG Meta Tags to improve the way the post looks when you share the page on Facebook, Twitter, LinkedIn -->
<meta property="og:site_name" content="" /> <!-- website name -->
<meta property="og:site" content="" /> <!-- website link -->
<meta property="og:title" content="" /> <!-- title shown in the actual shared post -->
<meta property="og:description" content="" /> <!-- description shown in the actual shared post -->
<meta property="og:image" content="" /> <!-- image link, make sure it's jpg -->
<meta property="og:url" content="" /> <!-- where do you want your post to link to -->
<meta name="twitter:card" content="summary_large_image" /> <!-- to have large image post format in Twitter -->
<!-- Webpage Title -->
<title>Маркетплейс цифровых товаров: Аккаунты, Буст, Скины, Игровая валюта, Предметы и другое</title>
<!-- Styles -->
<link rel="preconnect" href="https://fonts.gstatic.com" />
<link href="https://gamestock.shop/styles/fonts.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/awesome.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/tailwind.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/magnific-popup.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/styles.css" rel="stylesheet" />
<!-- Favicon  -->
<link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
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
<body data-spy="scroll" data-target=".fixed-top">
<!-- Navigation -->
<nav class="navbar fixed-top">
<div class="container sm:px-4 lg:px-8 flex flex-wrap items-center justify-between lg:flex-nowrap">
<!-- Text Logo - Use this if you don't have a graphic logo -->
<!-- <a class="text-gray-800 font-semibold text-3xl leading-4 no-underline page-scroll" href="index.html">Pavo</a> -->
<!-- Image Logo -->
<a class="inline-block mr-4 py-0.5 text-xl whitespace-nowrap hover:no-underline focus:no-underline" href="index.html">
<img src="images/logo.svg" alt="alternative" class="h-8" />
</a>
<button class="background-transparent rounded text-xl leading-none hover:no-underline focus:no-underline lg:hidden lg:text-gray-400" type="button" data-toggle="offcanvas">
<span class="navbar-toggler-icon inline-block w-8 h-8 align-middle"></span>
</button>
<div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault" lg:flex lg:flex-grow lg:items-center>
<ul class="pl-0 mt-3 mb-2 ml-auto flex flex-col list-none lg:mt-0 lg:mb-0 lg:flex-row">
<li>
<a class="nav-link page-scroll" href="#fast_order"><img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"> Быстрый заказ</a>
</li>
<li>
<a class="nav-link page-scroll active" href="/cabinet"><img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon"> Вход <span class="sr-only">(current)</span></a>
</li>
<li>
<a class="nav-link page-scroll" href="/cabinet"><img class="inline" src="https://gamestock.shop/icons/sign-up.png" alt="icon"> Регистрация</a>
</li>
<li>
<a class="nav-link page-scroll" href="/catalog.php"><img class="inline" src="https://gamestock.shop/icons/list.png" alt="icon"> Каталог</a>
</li>
</ul>
</div> <!-- end of navbar-collapse -->
</div>
 <!-- end of container -->
</nav> <!-- end of navbar -->
<!-- end of navigation -->
<!-- Header -->
<header id="header" class="header py-28 text-center md:pt-36 lg:text-left xl:pt-44 xl:pb-32">
<div class="container px-4 sm:px-8 lg:grid lg:grid-cols-2 lg:gap-x-8">
<div class="mb-16 lg:mt-32 xl:mt-8 xl:mr-12">
<h1 class="main-title">Маркетплейс цифровых товаров</h1>
<div style="text-align: center;"><p class="main-description">Аккаунты, Буст, Скины, Игровая валюта, Предметы и другое</p></div>
<div style="text-align: center;"><p class="main-description">Быстрое оформление. Низвие цены. Безопасность заказов.</p></div>
<br>
<div class="btn-group">
<a href="#fast_order" class="page-scroll"><button class="glow-on-hover"><img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"><b> Быстрый заказ</b></button>
</a>
<a href="/cabinet" class="page-scroll"><button class="glow-on-hover2"><img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon"><b> Вход</b></button>
</a></a>
<div style="text-align: center;"><a href="/catalog.php" class="page-scroll"><button class="glow-on-hover3"><img class="inline" src="https://gamestock.shop/icons/list.png" alt="icon"><b> Каталог</b></button>
</a></div></div></div>
<div class="xl:text-right">
<img class="inline" src="https://gamestock.shop/images/gamepad.png" alt="alternative" />
<!-- Statistics -->
<div class="counter">
<div class="container px-4 sm:px-8">
<!-- Counter -->
<div id="counter">
<div class="cell">
<div class="counter-value number-count">600</div>
<b class="counter-info">ТЫСЯЧ ПОЛЬЗОВАТЕЛЕЙ</b>
</div>
<div class="cell">
<div class="counter-value number-count">5</div>
<b class="counter-info">МИЛЛИОНОВ ЗАКАЗОВ</b>
</div></div></div></div> <!-- end of container -->
</header> <!-- end of header -->
<!-- end of header -->
</div></div> <!-- end of counter -->
<!-- end of statistics -->
<!-- Quick Order Form -->
<ul><section id="fast_order"></section></nav>
<div class="quick-order-section py-16 bg-gray-50" id="quick-order-form">
<div class="container px-4 sm:px-8 xl:px-4">
<div class="max-w-4xl mx-auto">
<h2 class="text-3xl font-bold text-center text-gray-800 mb-4"><img class="inline" src="https://gamestock.shop/icons/order_bag.png" alt="icon"> Быстрый заказ</h2>
<p class="text-lg text-center text-gray-600 mb-1">Заполните форму ниже для мгновенного оформления заказа</p>
<?php if (isset($_SESSION['order_error'])): ?>
<div class="alert alert-danger mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
<strong>Ошибка:</strong> <?= htmlspecialchars($_SESSION['order_error']) ?>
</div>
<?php unset($_SESSION['order_error']); ?>
<?php endif; ?>
<!-- ОСНОВНАЯ ФОРМА БЫСТРОГО ЗАКАЗА - С ФУНКЦИОНАЛОМ ИЗ КАТАЛОГА -->
<?php
// Получаем подключение к БД
require_once 'includes/config.php';
$pdo = getDBConnection();
// Получаем все товары один раз и сохраняем в JavaScript
$all_products = [];
$category_products = [];
try {
    $stmt = $pdo->query("
        SELECT id, name, description, our_price, stock, category
        FROM supplier_products
        WHERE stock > 0
        ORDER BY category, name
    ");
    $all_products = $stmt->fetchAll();

    // Группируем по категориям для JavaScript
    foreach ($all_products as $product) {
        $category = $product['category'];
        if (!isset($category_products[$category])) {
            $category_products[$category] = [];
        }
        $category_products[$category][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => mb_substr($product['description'] ?? '', 0, 200, 'UTF-8'),
            'price' => $product['our_price'],
            'stock' => $product['stock']
        ];
    }
} catch (Exception $e) {
    // Ошибка - показываем пустой список
}
?>

<form method="GET" action="/catalog.php" class="bg-white p-8 rounded-lg shadow-lg" id="quickOrderForm">
<div class="mb-6">
<label class="block text-gray-700 text-lg font-bold mb-2">1) Выберите категорию *</label>
<select name="quick_category" required id="categorySelect"
class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg">
<option value="">-- Выберите категорию --</option>
<option value="2">Фейсбук</option>
<option value="5">Мобильные прокси</option>
<option value="10">Фейсбук Самозарядка</option>
<option value="13">Дискорд</option>
<option value="15">Реддит</option>
<option value="18">Яндекс Дзен</option>
<option value="21">SEO - Ссылки</option>
<option value="25">Скайп</option>
<option value="26">Инстаграм</option>
<option value="29">Google Ads</option>
<option value="30">Яндекс.Директ</option>
<option value="42">Google iOS</option>
<option value="44">TikTok Ads</option>
<option value="50">Твиттер</option>
<option value="51">Epic Games</option>
<option value="53">Трафик/SEO</option>
<option value="68">ВКонтакте</option>
<option value="75">Почта (Email)</option>
</select>
<p class="text-sm text-gray-500 mt-1">Выберите категорию товара</p>
</div>
<div class="mb-6">
<label class="block text-gray-700 text-lg font-bold mb-2">2) Выберите товар *</label>
<select name="product_id" required id="productSelect" disabled
class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg">
<option value="">-- Сначала выберите категорию --</option>
</select>
<p class="text-sm text-gray-500 mt-1" id="productsInfo">Товары появятся после выбора категории</p>
<div id="productDescription" class="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200 text-sm text-gray-700" style="display:none;"></div>
</div>
<div class="mb-6">
<label for="email" class="block text-gray-700 text-lg font-bold mb-2">3) Ваш email (Необязательно)</label>
<input type="email" name="customer_email" id="customerEmail"
placeholder="example@gmail.com"
class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg">
<p class="text-sm text-gray-500 mt-1">
</div>

<!-- ИТОГО К ОПЛАТЕ -->
<div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
<h4 class="text-center font-bold text-xl">
<span class="text-gray-700">Итого к оплате:</span>
<span id="totalAmount" class="text-green-600 ml-2">0.00 ₽</span>
</h4>
</div>
<div class="text-center">
<button type="submit" name="quick_buy" value="1" class="glow-on-hover" id="payButton" disabled>
<img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"><b> Оплатить</b>
</button>
</div>
<br>
<div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
<h4 class="font-bold text-blue-800 mb-2">Как работает быстрый заказ:</h4>
<ul class="text-blue-700 space-y-1">
<li>1️⃣  Выберите категорию</li>
<li>2️⃣  Выберите товар</li>
<li>3️⃣  Нажмите "Оплатить"</li>
<li>4️⃣  Перейдете на страницу оплаты</li>
<li>5️⃣  После оплаты товары и данные будут доступны в личном кабинете</li>
</ul>
</div>
</form>
<script>
// Все товары из PHP
const categoryProducts = <?php echo json_encode($category_products, JSON_UNESCAPED_UNICODE); ?>;
// Загружаем товары при выборе категории
document.getElementById('categorySelect').addEventListener('change', function() {
    const categoryId = this.value;
    const productSelect = document.getElementById('productSelect');
    const productsInfo = document.getElementById('productsInfo');
    const payButton = document.getElementById('payButton');
    const totalAmount = document.getElementById('totalAmount');
    
    // Сбрасываем
    productSelect.innerHTML = '<option value="">-- Выберите товар --</option>';
    productSelect.disabled = true;
    payButton.disabled = true;
    totalAmount.textContent = '0.00 ₽';
    
    if (!categoryId) {
        productSelect.innerHTML = '<option value="">-- Сначала выберите категорию --</option>';
        productsInfo.textContent = 'Товары появятся после выбора категории';
        return;
    }
    
    // Получаем товары для выбранной категории
    const products = categoryProducts[categoryId] || [];
    
    if (products.length > 0) {
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.name} - ${product.price} ₽ (${product.stock} шт.)`;
            option.dataset.price = product.price;
            option.dataset.description = product.description || '';
            productSelect.appendChild(option);
        });
        productSelect.disabled = false;
        productsInfo.textContent = `Найдено ${products.length} товаров`;
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Нет товаров в этой категории';
        option.disabled = true;
        productSelect.appendChild(option);
        productsInfo.textContent = 'Нет товаров в выбранной категории';
    }
});

// Обновляем итоговую сумму и описание при выборе товара
document.getElementById('productSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const totalAmount = document.getElementById('totalAmount');
    const payButton = document.getElementById('payButton');
    const descBlock = document.getElementById('productDescription');

    if (this.value && selectedOption.dataset.price) {
        const price = parseFloat(selectedOption.dataset.price);
        totalAmount.textContent = price.toFixed(2) + ' ₽';
        payButton.disabled = false;
        // Show product description
        const desc = selectedOption.dataset.description || '';
        if (desc) {
            descBlock.textContent = desc;
            descBlock.style.display = 'block';
        } else {
            descBlock.style.display = 'none';
        }
    } else {
        totalAmount.textContent = '0.00 ₽';
        payButton.disabled = true;
        descBlock.style.display = 'none';
    }
});

// Валидация формы
document.getElementById('quickOrderForm').addEventListener('submit', function(e) {
    const category = document.getElementById('categorySelect').value;
    const product = document.getElementById('productSelect').value;
    const email = document.getElementById('customerEmail').value;
    
    if (!category) {
        e.preventDefault();
        alert('Пожалуйста, выберите категорию');
        return false;
    }
    
    if (!product) {
        e.preventDefault();
        alert('Пожалуйста, выберите товар');
        return false;
    }
    
    if (email && !email.includes('@')) {
        e.preventDefault();
        alert('Пожалуйста, введите корректный email или оставьте поле пустым');
        return false;
    }
    
    // Форма отправится в /catalog.php с параметрами
    return true;
});
</script>
<!-- КОНЕЦ ФОРМЫ -->
<div class="flex flex-wrap justify-center items-center mt-6 space-x-6 md:space-x-12">
<div class="text-center">
<div class="text-yellow-500 text-3xl mb-2">🕘</div>
<h4 class="font-bold text-gray-800">Быстро</h4>
</div>
<div class="text-center">
<div class="text-yellow-500 text-3xl mb-2">✅</div>
<h4 class="font-bold text-gray-800">Безопасно</h4>
</div>
<div class="text-center">
<div class="text-yellow-500 text-3xl mb-2">💬</div>
<h4 class="font-bold text-gray-800">Поддержка 24/7</h4>
</div></div></div></div>
<!-- Pricing -->
<div id="pricing" class="cards-2">
<div class="absolute bottom-0 h-40 w-full bg-white"></div>
<div class="container px-4 pb-px sm:px-8">
<h2 class="mb-2.5 text-white lg:max-w-xl lg:mx-auto">Товары и цены</h2>
<p class="mb-16 text-white lg:max-w-3xl lg:mx-auto"> Множество товаров и услуг для игр, а также социальных сетей!</p>
<!-- Card-->
<div class="card">
<div class="card-body">
<div class="card-title">ИГОРОВЫЕ АККАУНТЫ</div>
<ul class="list mb-7 space-y-2 text-left">
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/csgo.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2"> купить аккаунты CS:GO</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/dota2.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2"> купить аккаунты Dota 2</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/wot.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2"> купить аккаунты World of Tanks</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/steam.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2"> купить аккаунты Steam</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/origin.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2"> купить аккаунты Origin</label>
</li>
</ul>
<div class="price"><span class="currency">от</span><span class="value">50₽</span></div>
<div class="frequency">за аккаунт</div>
<div class="button-wrapper">
<a href="#fast_order" class="page-scroll"><button class="glow-on-hover"><img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"><b> Купить</b></button></a>
</div></div></div> <!-- end of card -->
<!-- end of card -->
<!-- Card-->
<div class="card">
<div class="card-body">
<div class="card-title">АККАУНТЫ В СОЦИАЛЬНЫХ СЕТЯХ</div>
<ul class="list mb-7 space-y-2 text-left">
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/vk.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить аккаунты ВК</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/telegram.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить аккаунты Telegram</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/tiktok.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить аккаунты ТикТок</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/proxy.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить аккаунты Прокси</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/google.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить аккаунты Google</label>
</li>
</ul>
<div class="price"><span class="currency">от</span><span class="value">1₽</span></div>
<div class="frequency">за аккаунт</div>
<div class="button-wrapper">
<a href="#fast_order" class="page-scroll"><button class="glow-on-hover"><img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"><b> Купить</b></button></a>
</div></div></div> <!-- end of card -->
<!-- end of card -->
<!-- Card-->
<div class="card">
<div class="card-body">
<div class="card-title">SEO-ОПТИМИЗАЦИЯ И НАКРУТКА</div>
<ul class="list mb-7 text-left space-y-2">
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/seo.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить SEO-прогон</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/link.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить ссылки в социальных сетях</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/link.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить размещение ссылок на сайтах</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/subscriber.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить подписчиков в социальных сетях</label>
</li>
<li class="flex">
<img class="icon1" src="https://gamestock.shop/icons/like.png" alt="icon"></i>
<label class="block text-gray-700 text-lg font-bold mb-2">купить лайки в соц.сетях</label>
</li>
</ul>
<div class="price"><span class="currency">от</span><span class="value">49₽</span></div>
<div class="frequency">за услугу</div>
<div class="button-wrapper">
<a href="#fast_order" class="page-scroll"><button class="glow-on-hover"><img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"><b> Купить</b></button></a>
</div></div></div></div></div> <!-- end of cards-2 -->
<!-- end of pricing -->
<!-- End Quick Order Form -->
<!-- Details 2 -->
<div class="py-24">
<div class="container px-4 sm:px-8 lg:grid lg:grid-cols-12 lg:gap-x-12">
<div class="lg:col-span-7">
<div class="mb-12 lg:mb-0 xl:mr-14">
<img class="inline" src="https://gamestock.shop/images/virtual_store.png" alt="alternative" />
</div></div> <!-- end of col -->
<div class="lg:col-span-5" text-align: justify>
<div class="xl:mt-12">
<b><h2 class="mb-6">Купить цифровые товары у ведущего в сфере диллера - GAMESTOCK.SHOP</h2>
<ul class="text">
<li class="flex">
<p class="text"></i>
<div><b>Наш ресурс – это ведущий маркетплейс цифровых товаров, предлагающий обширный ассортимент: от новейших игр и лицензионных ключей до полезного программного обеспечения и уникальных внутриигровых ценностей.</b></div>
</li><br>
<li class="flex">
<div>Мы гарантируем быструю и надёжную доставку, а также полную безопасность всех транзакций.</div>
</li><br>
<li class="flex">
<div>На GAMESTOCK.SHOP каждый пользователь легко найдёт то, что ищет по самой лучшей цене. Купить игровой аккаунт, подписку на стриминговый сервис, специализированное ПО, аккаунты, буст, скины, игровую валюту, предметы или любой другой виртуальный товар надежно, просто и быстро, воспользовавшись функцией "Быстрого заказа", или в личном кабинете на нашем сайте.</div>
</li>
</ul>
</div></div></div></div>
<!-- end of details 2 -->
<!-- Details Lightbox -->
<!-- Lightbox -->
<div class="text-center">
<h3 class="title-text">Как это работает?</h3></div>
<div id="pricing" class="cards-1">
<div class="container px-4 pb-px sm:px-8">
<!-- Card-->
<div class="card">
<div class="card-body">
<span class="number-step">1</span>  
<label class="block text-gray-700 text-lg font-bold mb-2">Создайте аккаунт на нашем сайте или используйте "Быстрый заказ"</label>
<img class="smart" src="https://gamestock.shop/images/smart-sign.png" " />
<ul class="list mb-7 space-y-2 text-left">
</ul>
</div></div> 
<!-- end of card -->
<!-- end of card -->
<!-- Card-->
<div class="card">
<div class="card-body">
<span class="number-step">2</span>
<label class="block text-gray-700 text-lg font-bold mb-2">Пополните баланс любым удобным для вас способом</label><br>
<img class="smart" src="https://gamestock.shop/images/balance.png" " />
<ul class="list mb-7 space-y-2 text-left">
</ul>
</div></div> 
<!-- end of card -->
<!-- Card-->
<!-- Card-->
<div class="card">
<div class="card-body">
<span class="number-step">3</span>
<label class="block text-gray-700 text-lg font-bold mb-2">Выберите услугу и внесите необходимые данные в форму заказа</label><br>
<img class="smart" src="https://gamestock.shop/images/choose.png" " />
<ul class="list mb-7 space-y-2 text-left">
</ul>
</div></div> 
<!-- end of card -->
</div></div></div></div></div>
<div id="details-lightbox" class="lightbox-basic zoom-anim-dialog mfp-hide">
<div class="lg:grid lg:grid-cols-12 lg:gap-x-8">
<button title="Close (Esc)" type="button" class="mfp-close x-button">×</button>
<div class="lg:col-span-8">
<div class="mb-12 text-center lg:mb-0 lg:text-left xl:mr-6">
<img class="inline rounded-lg" src="images/details-lightbox.jpg" alt="alternative" />
</div></div> <!-- end of col -->
<div class="lg:col-span-4">
<h3 class="mb-2">Goals Setting</h3>
<hr class="w-11 h-0.5 mt-0.5 mb-4 ml-0 border-none bg-indigo-600" />
<p>The app can easily help you track your personal development evolution if you take the time to set it up.</p>
<h4 class="mt-7 mb-2.5">User Feedback</h4>
<p class="mb-4">This is a great app which can help you save time and make your live easier. And it will help improve your productivity.</p>
<ul class="list mb-6 space-y-2">
<li class="flex">
<i class="fas fa-chevron-right"></i>
<div>Splash screen panel</div>
</li>
<li class="flex">
<i class="fas fa-chevron-right"></i>
<div>Statistics graph report</div>
</li>
<li class="flex">
<i class="fas fa-chevron-right"></i>
<div>Events calendar layout</div>
</li>
<li class="flex">
<i class="fas fa-chevron-right"></i>
<div>Location details screen</div>
</li>
<li class="flex">
<i class="fas fa-chevron-right"></i>
<div>Onboarding steps interface</div>
</li>
</ul>
<a class="btn-solid-reg mfp-close page-scroll" href="#download">Download</a>
<button class="btn-outline-reg mfp-close as-button" type="button">Back</button>
</div></div></div> <!-- end of lightbox-basic -->
<!-- Copyright -->
<div class="copyright">
<div class="container px-4 sm:px-8 lg:grid lg:grid-cols-3">
<ul class="mb-4 list-unstyled p-small">
<b><li class="mb-2"><a href="article.html">  Правила  </a><a href="terms.html">  Соглашение  </a><a href="privacy.html">  Конфиденциальность</a></li></b>
</ul>
<p class="pb-2 p-small statement"><b>gamestock.shop © 2019-2026</b></p>
<p class="pb-2 p-small statement"><b>gamestock.shop © 2019-2026</b><a href="#your-link" class="no-underline"></a></p>
</div></div>
<style>
/* Description: Master CSS file */
/*****************************************
Table Of Contents:
- General Styles
- Navigation
- Header
- Features
- Details Lightbox
- Statistics
- Testimonials
- Pricing
- Conclusion
- Footer
- Copyright
- Back To Top Button
- Media Queries
******************************************/
/*****************************************
Colors:
- Backgrounds - light gray #f1f9fc
- Buttons, icons - purple #594cda
- Buttons, icons - red #eb427e
- Headings text - black #252c38
- Body text - dark gray #6b747b
******************************************/
/**************************/
/*     General Styles     */
/**************************/
body,
p {
color: #6b747b;
font: 400 1rem/1.625rem "Open Sans", sans-serif;
}
.header-item-icon{
width: 16px;
margin-right: 7px
}
.text{
text-align: justify;
color: #5e5955;
line-height: 1.5;
}    
.main-title{
letter-spacing: .5px;
font-size:55px;
font-weight: 700;
margin-bottom: 50px;
text-shadow: 2px 3px 0 rgb (0 0 0 / 3%);
}
.main-description {
width: max-content;
color: white;
font-size: 18px;
font-weight: 600;
margin: 0.25em 0;
text-shadow: 1px 2px 0 rgb(0 0 0 / 3%);
}
.btn-group{
display:inline;
}
.glow-on-hover1{
width: 220px;
height: 50px;
left:15px;
border: none;
outline: none;
color: #fff;
background: orange;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover1:before {
content: '';
position: absolute;
top: -2px;
left:-2px;
background-size: 400%;
z-index: -1;
filter: blur(5px);
width: calc(100% + 4px);
height: calc(100% + 4px);
opacity: 0;
transition: opacity .3s ease-in-out;
border-radius: 10px;
}
.glow-on-hover1:active {
color: #000;
}
.glow-on-hover1:active:after {
background: transparent;
}
.glow-on-hover1:hover:before {
opacity: 1;
}
.glow-on-hover1:after {
z-index: -1;
content: '';
position: absolute;
width: 100%;
height: 100%;
background:  #f9a020;
left: 0;
top: 0;
border-radius: 10px;
}
@keyframes glowing {
0% { background-position: 0 0; }
50% { background-position: 400% 0; }
100% { background-position: 0 0; }
}
.glow-on-hover2-main{
width: 220px;
height: 50px;
border: none;
outline: none;
color: #fff;
background: #111;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover2{
float: right;
display:inline;
width: 220px;
height: 50px;
border: none;
outline: none;
color: #fff;
background: #111;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover2:before {
content: '';
background: linear-gradient(45deg, #ff0000, #ff7300, #fffb00, #48ff00, #00ffd5, #002bff, #7a00ff, #ff00c8, #ff0000);
position: absolute;
top: -2px;
left:-2px;
background-size: 400%;
z-index: -1;
filter: blur(5px);
width: calc(100% + 4px);
height: calc(100% + 4px);
animation: glowing 20s linear infinite;
opacity: 0;
transition: opacity .3s ease-in-out;
border-radius: 10px;
}
.glow-on-hover2:active {
color: #000;
}
.glow-on-hover2:active:after {
background: transparent;
}
.glow-on-hover2:hover:before {
opacity: 1;
}
.glow-on-hover2:after {
z-index: -1;
content: '';
position: absolute;
width: 100%;
height: 100%;
background:  darkorange;
left: 0;
top: 0;
border-radius: 10px;
}
.glow-on-hover3-main{
width: 220px;
height: 50px;
border: none;
outline: none;
color: #fff;
background: #111;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover3{
margin-top:6%;
display:inline;
width: 220px;
height: 50px;
border: none;
outline: none;
color: #fff;
background: #111;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover3:before {
content: '';
background: linear-gradient(45deg, #ff0000, #ff7300, #fffb00, #48ff00, #00ffd5, #002bff, #7a00ff, #ff00c8, #ff0000);
position: absolute;
top: -2px;
left:-2px;
background-size: 400%;
z-index: -1;
filter: blur(5px);
width: calc(100% + 4px);
height: calc(100% + 4px);
animation: glowing 20s linear infinite;
opacity: 0;
transition: opacity .3s ease-in-out;
border-radius: 10px;
}
.glow-on-hover3:active {
color: #000;
}
.glow-on-hover3:active:after {
background: transparent;
}
.glow-on-hover3:hover:before {
opacity: 1;
}
.glow-on-hover3:after {
z-index: -1;
content: '';
position: absolute;
width: 100%;
height: 100%;
background:  darkorange;
left: 0;
top: 0;
border-radius: 10px;
}
h1 {
color: white;
letter-spacing: .5px;
font-size: 55px;
font-weight: 700;
margin-bottom: 50px;
text-shadow: 2px 3px 0 rgb(0 0 0 / 3%);
line-height: 1.4;
}
h2 {
color: royalblue;
font-weight: 700;
font-size: 2.125rem;
line-height: 2.625rem;
letter-spacing: -0.4px;
}
h3 {
color: #252c38;
font-weight: 700;
font-size: 1.75rem;
line-height: 2.25rem;
letter-spacing: -0.3px;
}
h4 {
color: #252c38;
font-weight: 700;
font-size: 1.5rem;
line-height: 2rem;
letter-spacing: -0.2px;
}
h5 {
color: #252c38;
font-weight: 700;
font-size: 1.25rem;
line-height: 1.625rem;
}
h6 {
color: #252c38;
font-weight: 700;
font-size: 1rem;
line-height: 1.375rem;
}
.h1-large {
font-size: 2.875rem;
line-height: 3.5rem;
}
.p-large {
font-size: 1.125rem;
line-height: 1.75rem;
}
.p-small {
font-size: 0.875rem;
line-height: 1.5rem;
}
.bg-gray {
background-color: #f1f9fc;
}
.container {
margin-left: auto;
margin-right: auto;
}
.smart {
width:100%;
height:100%;    
}
.title-text{
color: royalblue;
font-size: 53px;
font-weight: 700;
margin-bottom: 15px    
}
.glow-on-hover-main{
width: 220px;
height: 50px;
border: none;
outline: none;
color: #fff;
background: #111;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover{
display:inline;
width: 220px;
height: 50px;
border: none;
outline: none;
color: #fff;
background: #111;
cursor: pointer;
position: relative;
z-index: 0;
border-radius: 10px;
}
.glow-on-hover:before {
content: '';
background: linear-gradient(45deg, #ff0000, #ff7300, #fffb00, #48ff00, #00ffd5, #002bff, #7a00ff, #ff00c8, #ff0000);
position: absolute;
top: -2px;
left:-2px;
background-size: 400%;
z-index: -1;
filter: blur(5px);
width: calc(100% + 4px);
height: calc(100% + 4px);
animation: glowing 20s linear infinite;
opacity: 0;
transition: opacity .3s ease-in-out;
border-radius: 10px;
}
.glow-on-hover:active {
color: #000;
}
.glow-on-hover:active:after {
background: transparent;
}
.glow-on-hover:hover:before {
opacity: 1;
}
.glow-on-hover:after {
z-index: -1;
content: '';
position: absolute;
width: 100%;
height: 100%;
background:  darkorange;
left: 0;
top: 0;
border-radius: 10px;
}
.number-step{
display: inline-block;
width: 37px;
height: 37px;
line-height: 29px;
font-size: 19px;
font-weight: 700;
border-radius: 50px;
border: 5px solid rgb(2, 55, 241);    
}
.icon1{
width: 23px;
height: 23px;
margin-top: 3px;
margin-right: 15px;    
}
.btn-solid-reg {
display: inline-block;
padding: 1.375rem 2.25rem 1.375rem 2.25rem;
border: 1px solid #FF7E29;
border-radius: 32px;
background-color: #FF7E29;
color: #ffffff;
font-weight: 600;
font-size: 0.875rem;
line-height: 0;
text-decoration: none;
transition: all 0.2s;
}
.btn-solid-reg:hover {
border: 1px solid #594cda;
background-color: transparent;
color: #594cda; /* needs to stay here because of the color property of a tag */
text-decoration: none;
}
.btn-solid-lg {
display: inline-block;
padding: 1.625rem 2.75rem 1.625rem 2.75rem;
border: 1px solid #FF7E29;
border-radius: 32px;
background-color: #FF7E29;
color: #ffffff;
font-weight: 600;
font-size: 0.875rem;
line-height: 0;
text-decoration: none;
transition: all 0.2s;
margin-right: 0.25rem;
margin-bottom: 1.25rem;
margin-left: 0.25rem;
}
.btn-solid-lg:hover {
border: 1px solid #594cda;
background-color: transparent;
color: #594cda; /* needs to stay here because of the color property of a tag */
text-decoration: none;
}
.btn-solid-lg .fab {
margin-right: 0.5rem;
font-size: 1.25rem;
line-height: 0;
vertical-align: top;
}
.btn-solid-lg .fab.fa-google-play {
font-size: 1rem;
}
.btn-solid-lg.secondary {
border: 1px solid #eb427e;
background-color: #eb427e;
}
.btn-solid-lg.secondary:hover {
border: 1px solid #eb427e;
background: transparent;
color: #eb427e; /* needs to stay here because of the color property of a tag */
}
.btn-outline-reg {
display: inline-block;
padding: 1.375rem 2.25rem 1.375rem 2.25rem;
border: 1px solid #252c38;
border-radius: 32px;
background-color: transparent;
color: #252c38;
font-weight: 600;
font-size: 0.875rem;
line-height: 0;
text-decoration: none;
transition: all 0.2s;
}
.btn-outline-reg:hover {
background-color: #252c38;
color: #ffffff;
text-decoration: none;
}
.btn-outline-lg {
display: inline-block;
padding: 1.625rem 2.75rem 1.625rem 2.75rem;
border: 1px solid #252c38;
border-radius: 32px;
background-color: transparent;
color: #252c38;
font-weight: 600;
font-size: 0.875rem;
line-height: 0;
text-decoration: none;
transition: all 0.2s;
}
.btn-outline-lg:hover {
background-color: #252c38;
color: #ffffff;
text-decoration: none;
}
.btn-outline-sm {
display: inline-block;
padding: 1rem 1.5rem 1rem 1.5rem;
border: 1px solid #252c38;
border-radius: 32px;
background-color: transparent;
color: #252c38;
font-weight: 600;
font-size: 0.875rem;
line-height: 0;
text-decoration: none;
transition: all 0.2s;
}
.btn-outline-sm:hover {
background-color: #252c38;
color: #ffffff;
text-decoration: none;
}
.list .fas {
color: #594cda;
font-size: 0.75rem;
line-height: 1.625rem;
}
.list div {
flex: 1 1 0%;
margin-left: 0.375rem;
}
.form-group {
position: relative;
margin-bottom: 1.25rem;
}
.label-control {
position: absolute;
top: 0.875rem;
left: 1.875rem;
color: #7d838a;
opacity: 1;
font-size: 0.875rem;
line-height: 1.5rem;
cursor: text;
transition: all 0.2s ease;
}
.form-control-input:focus + .label-control,
.form-control-input.notEmpty + .label-control,
.form-control-textarea:focus + .label-control,
.form-control-textarea.notEmpty + .label-control {
top: 0.125rem;
color: #6b747b;
opacity: 1;
font-size: 0.75rem;
font-weight: 700;
}
.form-control-input,
.form-control-select {
display: block; /* needed for proper display of the label in Firefox, IE, Edge */
width: 100%;
padding-top: 1.125rem;
padding-bottom: 0.125rem;
padding-left: 1.8125rem;
border: 1px solid #d0d5e2;
border-radius: 25px;
background-color: #ffffff;
color: #6b747b;
font-size: 0.875rem;
line-height: 1.875rem;
transition: all 0.2s;
-webkit-appearance: none; /* removes inner shadow on form inputs on ios safari */
}
.form-control-select {
padding-top: 0.5rem;
padding-bottom: 0.5rem;
height: 3.25rem;
color: #7d838a;
}
select {
/* you should keep these first rules in place to maintain cross-browser behavior */
-webkit-appearance: none;
-moz-appearance: none;
-ms-appearance: none;
-o-appearance: none;
appearance: none;
background-image: url("../images/down-arrow.png");
background-position: 96% 50%;
background-repeat: no-repeat;
outline: none;
}
.form-control-textarea {
display: block; /* used to eliminate a bottom gap difference between Chrome and IE/FF */
width: 100%;
height: 14rem; /* used instead of html rows to normalize height between Chrome and IE/FF */
padding-top: 1.5rem;
padding-left: 1.3125rem;
border: 1px solid #d0d5e2;
border-radius: 4px;
background-color: #ffffff;
color: #6b747b;
font-size: 0.875rem;
line-height: 1.5rem;
transition: all 0.2s;
}
.form-control-input:focus,
.form-control-select:focus,
.form-control-textarea:focus {
border: 1px solid #a1a1a1;
outline: none; /* Removes blue border on focus */
}
.form-control-input:hover,
.form-control-select:hover,
.form-control-textarea:hover {
border: 1px solid #a1a1a1;
}
.checkbox {
font-size: 0.75rem;
line-height: 1.25rem;
}
input[type="checkbox"] {
vertical-align: -10%;
margin-right: 0.5rem;
}
.form-control-submit-button {
display: inline-block;
width: 100%;
height: 3.25rem;
border: 1px solid #594cda;
border-radius: 32px;
background-color: #594cda;
color: #252c38;
font-weight: 600;
font-size: 0.875rem;
line-height: 0;
cursor: pointer;
transition: all 0.2s;
}
.form-control-submit-button:hover {
border: 1px solid #252c38;
background-color: transparent;
color: #252c38;
}
/* Fade-move Animation For Details Lightbox - Magnific Popup */
/* at start */
.my-mfp-slide-bottom .zoom-anim-dialog {
opacity: 0;
transition: all 0.2s ease-out;
-webkit-transform: translateY(-1.25rem) perspective(37.5rem) rotateX(10deg);
-ms-transform: translateY(-1.25rem) perspective(37.5rem) rotateX(10deg);
transform: translateY(-1.25rem) perspective(37.5rem) rotateX(10deg);
}
/* animate in */
.my-mfp-slide-bottom.mfp-ready .zoom-anim-dialog {
opacity: 1;
-webkit-transform: translateY(0) perspective(37.5rem) rotateX(0);
-ms-transform: translateY(0) perspective(37.5rem) rotateX(0);
transform: translateY(0) perspective(37.5rem) rotateX(0);
}
/* animate out */
.my-mfp-slide-bottom.mfp-removing .zoom-anim-dialog {
opacity: 0;
-webkit-transform: translateY(-0.625rem) perspective(37.5rem) rotateX(10deg);
-ms-transform: translateY(-0.625rem) perspective(37.5rem) rotateX(10deg);
transform: translateY(-0.625rem) perspective(37.5rem) rotateX(10deg);
}
/* dark overlay, start state */
.my-mfp-slide-bottom.mfp-bg {
opacity: 0;
transition: opacity 0.2s ease-out;
}
/* animate in */
.my-mfp-slide-bottom.mfp-ready.mfp-bg {
opacity: 0.8;
}
/* animate out */
.my-mfp-slide-bottom.mfp-removing.mfp-bg {
opacity: 0;
}
/* end of fade-move animation for details lightbox - magnific popup */
/**********************/
/*     Navigation     */
/**********************/
.navbar {
position: relative;
background-color: rgb(2, 55, 241);
padding: 0.5rem 1rem;
flex-flow: row wrap;
justify-content: space-between;
align-items: center;
font-weight: 600;
font-size: 0.875rem;
line-height: 0.75rem;
transition: all 0.2s ease;
}
.fixed-top {
display: inline-block;
position: fixed;
float: right
}
.navbar-toggler-icon {
content: "";
background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%280, 0, 0, 0.5%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
background-repeat: no-repeat;
background-position: center center;
background-size: 100% 100%;
}
.navbar-collapse {
flex-basis: 100%;
}
.offcanvas-collapse {
position: fixed;
top: 3.25rem; /* adjusts the height between the top of the page and the offcanvas menu */
bottom: 0;
left: 100%;
width: 100%;
padding-right: 1rem;
padding-left: 1rem;
overflow-y: auto;
visibility: hidden;
background-color: rgb(2, 55, 241);
transition: visibility 0.3s ease-in-out, -webkit-transform 0.3s ease-in-out;
transition: transform 0.3s ease-in-out, visibility 0.3s ease-in-out;
transition: transform 0.3s ease-in-out, visibility 0.3s ease-in-out, -webkit-transform 0.3s ease-in-out;
}
.offcanvas-collapse.open {
visibility: visible;
-webkit-transform: translateX(-100%);
transform: translateX(-100%);
}
.nav-link {
display: block;
padding-top: 0.625rem;
padding-bottom: 0.625rem;
color: white;
text-decoration: none;
line-height: 0.875rem;
transition: all 0.2s ease;
}
.dropdown-toggle {
white-space: nowrap;
}
.dropdown-toggle::after {
display: inline-block;
margin-left: 0.255em;
vertical-align: 0.255em;
content: "";
border-top: 0.3em solid;
border-right: 0.3em solid transparent;
border-bottom: 0;
border-left: 0.3em solid transparent;
}
.dropdown-toggle:empty::after {
margin-left: 0;
}
.dropdown.show > a,
.nav-link:hover,
.nav-link.active {
color: white;
text-decoration: none;
}
.dropdown-menu {
top: 100%;
left: 0;
z-index: 1000;
display: none;
min-width: 10rem;
padding: 0.5rem 0;
margin: 0.5rem 0;
font-size: 1rem;
color: #212529;
list-style: none;
background-color: #f1f9fc;
background-clip: padding-box;
border-radius: 0.25rem;
animation: fadeDropdown 0.2s; /* required for the fade animation */
}
@keyframes fadeDropdown {
0% {
opacity: 0;
}
100% {
opacity: 1;
}
}
.dropdown-menu.show {
display: block;
top: 90%;
left: auto;
}
.dropdown-item {
display: block;
width: 100%;
padding: 0.5rem 1.5rem;
clear: both;
text-align: inherit;
white-space: nowrap;
background-color: transparent;
color: #6b747b;
font-weight: 600;
font-size: 0.875rem;
line-height: 0.875rem;
text-decoration: none;
}
.dropdown-item:hover,
.dropdown-item:focus {
text-decoration: none;
background-color: #f1f9fc;
color: #ff6e84;
}
.dropdown-divider {
overflow: hidden;
width: 100%;
height: 1px;
margin: 0.5rem auto 0.5rem auto;
background-color: #d4dce2;
}
/* end of dropdown menu */
/*****************/
/*    Header     */
/*****************/
.header {
height: 92vh;
background: lightskyblue;
background: url(https://gamestock.shop/images/background.png), linear-gradient(140deg, royalblue 0%, cornflowerblue 33%, dodgerblue 67%, lightskyblue 100%);
}
/********************/
/*     Features     */
/********************/
.cards-1 {
padding-top: 4rem;
padding-bottom: 1.5rem;
text-align: center;
}
.cards-1 .card {
margin-bottom: 3.5rem;
padding: 3.125rem 1rem 2.125rem 1rem;
border: none;
border-radius: 16px;
background-color: #f1f9fc;
}
.cards-1 .card-image {
margin-bottom: 1.5rem;
}
.cards-1 .card-image img {
width: 70px;
height: 70px;
margin-right: auto;
margin-left: auto;
}
.cards-1 .card-body {
padding: 0;
}
.cards-1 .card-title {
margin-bottom: 0.375rem;
}
/****************************/
/*     Details Lightbox     */
/****************************/
.lightbox-basic {
position: relative;
max-width: 1150px;
margin: 2.5rem auto;
padding: 3rem 1rem;
background-color: #ffffff;
text-align: left;
}
/* Action Button */
.lightbox-basic .btn-solid-reg.mfp-close {
position: relative;
width: auto;
height: auto;
color: #ffffff;
opacity: 1;
font-weight: 600;
font-family: "Open Sans";
}
.lightbox-basic .btn-solid-reg.mfp-close:hover {
color: #594cda;
}
/* end of action Button */
/* Back Button */
.lightbox-basic .btn-outline-reg.mfp-close.as-button {
position: relative;
display: inline-block;
width: auto;
height: auto;
margin-left: 0.375rem;
padding: 1.375rem 2.25rem 1.375rem 2.25rem;
border: 1px solid #252c38;
background-color: transparent;
color: #252c38;
opacity: 1;
font-family: "Open Sans";
}
.lightbox-basic .btn-outline-reg.mfp-close.as-button:hover {
background-color: #252c38;
color: #ffffff;
}
/* end of back button */
/* Close X Button */
.lightbox-basic button.mfp-close.x-button {
position: absolute;
top: -2px;
right: -2px;
width: 44px;
height: 44px;
color: #555555;
}
/* end of close x button */
/**********************/
/*     Statistics     */
/**********************/
.counter {
padding-top: 2rem;
padding-bottom: 4.5rem;
text-align: center;
}
.counter #counter {
margin-bottom: 0.75rem;
}
.counter #counter .cell {
display: inline-block;
width: 120px;
margin-right: 1.75rem;
margin-bottom: 3.5rem;
margin-left: 1.75rem;
vertical-align: top;
}
.counter #counter .counter-value {
color: white;
font-weight: 700;
font-size: 3.25rem;
line-height: 3.75rem;
vertical-align: middle;
}
.counter #counter .counter-info {
color:white;
margin-bottom: 0;
font-size: 0.875rem;
vertical-align: middle;
}
/************************/
/*     Testimonials     */
/************************/
.slider-1 .slider-container {
position: relative;
}
.slider-1 .swiper-container {
position: static;
width: 86%;
text-align: center;
}
.slider-1 .swiper-button-prev:focus,
.slider-1 .swiper-button-next:focus {
/* even if you can't see it chrome you can see it on mobile device */
outline: none;
}
.slider-1 .swiper-button-prev {
left: -14px;
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2028%2044'%3E%3Cpath%20d%3D'M0%2C22L22%2C0l2.1%2C2.1L4.2%2C22l19.9%2C19.9L22%2C44L0%2C22L0%2C22L0%2C22z'%20fill%3D'%23707375'%2F%3E%3C%2Fsvg%3E");
background-size: 18px 28px;
}
.slider-1 .swiper-button-next {
right: -14px;
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2028%2044'%3E%3Cpath%20d%3D'M27%2C22L27%2C22L5%2C44l-2.1-2.1L22.8%2C22L2.9%2C2.1L5%2C0L27%2C22L27%2C22z'%20fill%3D'%23707375'%2F%3E%3C%2Fsvg%3E");
background-size: 18px 28px;
}
.slider-1 .card {
position: relative;
border: none;
background-color: transparent;
}
.slider-1 .card-image {
width: 80px;
height: 80px;
margin-right: auto;
margin-bottom: 1.25rem;
margin-left: auto;
border-radius: 50%;
}
.slider-1 .testimonial-author {
margin-bottom: 0;
color: #252c38;
font-weight: 700;
font-size: 1rem;
line-height: 1.5rem;
}
/*******************/
/*     Pricing     */
/*******************/
.cards-2 {
position: relative;
padding-top: 8rem;
background: lightskyblue;
background: url(https://gamestock.shop/images/background.png), linear-gradient(140deg, royalblue 0%, cornflowerblue 33%, dodgerblue 67%, lightskyblue 100%);
background-size: cover;
text-align: center;
}
.cards-2 .card {
position: relative;
display: block;
background-color: #ffffff;
max-width: 330px;
margin-right: auto;
margin-bottom: 6rem;
margin-left: auto;
border: 1px solid #bcc4ca;
border-radius: 8px;
}
.cards-2 .card .card-body {
padding: 3rem 1.75rem 2.25rem 1.75rem;
}
.cards-2 .card .card-title {
margin-bottom: 1rem;
color: #eb427e;
font-weight: 700;
font-size: 1.5rem;
line-height: 1.875rem;
text-align: center;
}
.cards-2 .card p {
margin-bottom: 1.25rem;
text-align: left;
}
.cards-2 .card .value {
color: #252c38;
font-weight: 600;
font-size: 5rem;
line-height: 5rem;
text-align: center;
}
.cards-2 .card .currency {
margin-right: 0.375rem;
color: #252c38;
font-size: 2rem;
vertical-align: 80%;
}
.cards-2 .card .frequency {
margin-bottom: 1.5rem;
font-size: 0.875rem;
text-align: center;
}
.cards-2 .card .button-wrapper {
position: absolute;
right: 0;
bottom: -1.5rem;
left: 0;
text-align: center;
}
.cards-2 .card .btn-solid-reg:hover {
background-color: #ffffff;
}
/* Best Value Label */
.cards-2 .card .label {
position: absolute;
top: 0;
right: 0;
width: 10.625rem;
height: 10.625rem;
overflow: hidden;
}
.cards-2 .card .label .best-value {
position: relative;
width: 13.75rem;
padding: 0.3125rem 0 0.3125rem 4.125rem;
background-color: #eb427e;
color: #ffffff;
-webkit-transform: rotate(45deg) translate3d(0, 0, 0);
-ms-transform: rotate(45deg) translate3d(0, 0, 0);
transform: rotate(45deg) translate3d(0, 0, 0);
}
/* end of best value label */
/******************/
/*     Footer     */
/******************/
.footer {
padding-top: 6rem;
padding-bottom: 3rem;
background: white;
text-align: center;
}
.footer a {
text-decoration: none;
}
.footer .fa-stack {
width: 2em;
margin-bottom: 1.25rem;
margin-right: 0.375rem;
font-size: 1.5rem;
}
.footer .fa-stack .fa-stack-1x {
color: #252c38;
transition: all 0.2s ease;
}
.footer .fa-stack .fa-stack-2x {
color: #ffffff;
transition: all 0.2s ease;
}
.footer .fa-stack:hover .fa-stack-1x {
color: #ffffff;
}
.footer .fa-stack:hover .fa-stack-2x {
color: #252c38;
}
/*********************/
/*     Copyright     */
/*********************/
.copyright {
padding-top: 1.5rem;
background-color: rgb(2, 55, 241);
text-align: center;
}
/***********************/
/*     Extra Pages     */
/***********************/
.ex-header {
padding-top: 8.5rem;
padding-bottom: 4rem;
background-color: #f1f9fc;
}
.ex-basic-1 .list-unstyled .fas {
font-size: 0.375rem;
line-height: 1.625rem;
}
.ex-basic-1 .text-box {
padding: 1.25rem 1.25rem 0.5rem 1.25rem;
background-color: #f1f9fc;
}
.ex-cards-1 .card {
border: none;
background-color: transparent;
}
.ex-cards-1 .card .fa-stack {
width: 2em;
font-size: 1.125rem;
}
.ex-cards-1 .card .fa-stack-2x {
color: #ff6e84;
}
.ex-cards-1 .card .fa-stack-1x {
color: #ffffff;
font-weight: 700;
line-height: 2.125rem;
}
/*************************/
/*     Media Queries     */
/*************************/
/* Min-width 768px */
@media (min-width: 768px) {
/* Extra Pages */
.ex-basic-1 .text-box {
padding: 1.75rem 2rem 0.875rem 2rem;
}
/* end of extra pages */
}
/* end of min-width 768px */
/* Min-width 1024px */
@media (min-width: 1024px) {
/* General Styles */
.btn-solid-lg {
margin-right: 0.5rem;
margin-left: 0;
}
/* end of general styles */
/* Navigation */
.navbar {
background-color: transparent;
flex-wrap: nowrap;
justify-content: start;
padding-left: 0;
padding-right: 0;
padding-top: 1.75rem;
}
.navbar-collapse {
flex-basis: auto;
}
.navbar.top-nav-collapse {
padding-top: 0.5rem;
padding-bottom: 0.5rem;
background-color: rgb(2, 55, 241);
}
.offcanvas-collapse {
position: static;
top: auto;
bottom: auto;
left: auto;
width: auto;
padding-right: 0;
padding-left: 0;
background-color: transparent;
overflow-y: visible;
visibility: visible;
}
.offcanvas-collapse.open {
-webkit-transform: none;
transform: none;
}
.nav-link {
padding-right: 0.625rem;
padding-left: 0.625rem;
}
.dropdown-menu {
position: absolute;
margin-top: 0.25rem;
box-shadow: 0 3px 3px 1px rgba(0, 0, 0, 0.05);
}
.dropdown-divider {
width: 90%;
}
/* end of navigation */
/* Details Lightbox */
.lightbox-basic {
padding: 3rem 3rem;
}
/* end of details lightbox */
/* Features */
.cards-1 .card {
display: inline-block;
width: 306px;
vertical-align: top;
}
.cards-1 .card:nth-of-type(3n + 2) {
margin-right: 1rem;
margin-left: 1rem;
}
/* end of features */
/* Statistics */
.counter {
padding-top: 5rem;
}
/* end of statistics */
/* Testimonials */
.slider-1 .swiper-container {
width: 92%;
}
.slider-1 .swiper-button-prev {
left: -16px;
width: 22px;
background-size: 22px 34px;
}
.slider-1 .swiper-button-next {
right: -16px;
width: 22px;
background-size: 22px 34px;
}
/* end of testimonials */
/* Pricing */
.cards-2 .card {
display: inline-block;
max-width: 100%;
width: 312px;
vertical-align: top;
}
.cards-2 .card:nth-of-type(3n + 2) {
margin-right: 0.375rem;
margin-left: 0.375rem;
}
/* end of pricing */
/* Conclusion */
.basic-5 {
text-align: left;
}
/* end of conclusion */
/* Copyright */
.copyright {
text-align: left;
}
.copyright .list-unstyled li {
display: inline-block;
margin-right: 1rem;
}
.copyright .statement {
text-align: right;
}
/* end of copyright */
/* Extra Pages */
.ex-cards-1 .card {
display: inline-block;
width: 306px;
vertical-align: top;
}
.ex-cards-1 .card:nth-of-type(3n + 2) {
margin-right: 1rem;
margin-left: 1rem;
}
/* end of extra pages */
}
/* end of min-width 1024px */
/* Min-width 1280px */
@media (min-width: 1280px) {
/* General Styles */
.h1-large {
font-size: 3.125rem;
line-height: 3.75rem;
}
.container {
max-width: 72rem;
}
/* end of general styles */
/* Features */
.cards-1 .card {
width: 342px;
padding-right: 2.875rem;
padding-left: 2.875rem;
}
.cards-1 .card:nth-of-type(3n + 2) {
margin-right: 1.5rem;
margin-left: 1.5rem;
}
/* end of features */
/* Statistics */
.counter #counter .cell {
margin-right: 2.5rem;
margin-left: 2.5rem;
}
.counter #counter .counter-value {
font-size: 3.75rem;
line-height: 4.25rem;
}
/* end of statistics */
/* Pricing */
.cards-2 .card {
width: 335px;
}
.cards-2 .card:nth-of-type(3n + 2) {
margin-right: 2.25rem;
margin-left: 2.25rem;
}
.cards-2 .card .card-body {
padding-right: 2.25rem;
padding-left: 2.25rem;
}
/* end of pricing */
/* Extra Pages */
.ex-cards-1 .card {
width: 328px;
}
.ex-cards-1 .card:nth-of-type(3n + 2) {
margin-right: 2.875rem;
margin-left: 2.875rem;
}
/* end of extra pages */
}
/* end of min-width 1200px */
/* Magnific Popup CSS */
.mfp-bg {
top: 0;
left: 0;
width: 100%;
height: 100%;
z-index: 1042;
overflow: hidden;
position: fixed;
background: #0b0b0b;
opacity: 0.8; }
.mfp-wrap {
top: 0;
left: 0;
width: 100%;
height: 100%;
z-index: 1043;
position: fixed;
outline: none !important;
-webkit-backface-visibility: hidden; }
.mfp-container {
text-align: center;
position: absolute;
width: 100%;
height: 100%;
left: 0;
top: 0;
padding: 0 8px;
box-sizing: border-box; }
.mfp-container:before {
content: '';
display: inline-block;
height: 100%;
vertical-align: middle; }
.mfp-align-top .mfp-container:before {
display: none; }
.mfp-content {
position: relative;
display: inline-block;
vertical-align: middle;
margin: 0 auto;
text-align: left;
z-index: 1045; }
.mfp-inline-holder .mfp-content,
.mfp-ajax-holder .mfp-content {
width: 100%;
cursor: auto; }
.mfp-ajax-cur {
cursor: progress; }
.mfp-zoom-out-cur, .mfp-zoom-out-cur .mfp-image-holder .mfp-close {
cursor: -moz-zoom-out;
cursor: -webkit-zoom-out;
cursor: zoom-out; }
.mfp-zoom {
cursor: pointer;
cursor: -webkit-zoom-in;
cursor: -moz-zoom-in;
cursor: zoom-in; }
.mfp-auto-cursor .mfp-content {
cursor: auto; }
.mfp-close,
.mfp-arrow,
.mfp-preloader,
.mfp-counter {
-webkit-user-select: none;
-moz-user-select: none;
user-select: none; }
.mfp-loading.mfp-figure {
display: none; }
.mfp-hide {
display: none !important; }
.mfp-preloader {
color: #CCC;
position: absolute;
top: 50%;
width: auto;
text-align: center;
margin-top: -0.8em;
left: 8px;
right: 8px;
z-index: 1044; }
.mfp-preloader a {
color: #CCC; }
.mfp-preloader a:hover {
color: #FFF; }
.mfp-s-ready .mfp-preloader {
display: none; }
.mfp-s-error .mfp-content {
display: none; }
button.mfp-close,
button.mfp-arrow {
overflow: visible;
cursor: pointer;
background: transparent;
border: 0;
-webkit-appearance: none;
display: block;
outline: none;
padding: 0;
z-index: 1046;
box-shadow: none;
touch-action: manipulation; }
button::-moz-focus-inner {
padding: 0;
border: 0; }
.mfp-close {
width: 44px;
height: 44px;
line-height: 44px;
position: absolute;
right: 0;
top: 0;
text-decoration: none;
text-align: center;
opacity: 0.65;
padding: 0 0 18px 10px;
color: #FFF;
font-style: normal;
font-size: 28px;
font-family: Arial, Baskerville, monospace; }
.mfp-close:hover,
.mfp-close:focus {
opacity: 1; }
.mfp-close:active {
top: 1px; }
.mfp-close-btn-in .mfp-close {
color: #333; }
.mfp-image-holder .mfp-close,
.mfp-iframe-holder .mfp-close {
color: #FFF;
right: -6px;
text-align: right;
padding-right: 6px;
width: 100%; }
.mfp-counter {
position: absolute;
top: 0;
right: 0;
color: #CCC;
font-size: 12px;
line-height: 18px;
white-space: nowrap; }
.mfp-arrow {
position: absolute;
opacity: 0.65;
margin: 0;
top: 50%;
margin-top: -55px;
padding: 0;
width: 90px;
height: 110px;
-webkit-tap-highlight-color: transparent; }
.mfp-arrow:active {
margin-top: -54px; }
.mfp-arrow:hover,
.mfp-arrow:focus {
opacity: 1; }
.mfp-arrow:before,
.mfp-arrow:after {
content: '';
display: block;
width: 0;
height: 0;
position: absolute;
left: 0;
top: 0;
margin-top: 35px;
margin-left: 35px;
border: medium inset transparent; }
.mfp-arrow:after {
border-top-width: 13px;
border-bottom-width: 13px;
top: 8px; }
.mfp-arrow:before {
border-top-width: 21px;
border-bottom-width: 21px;
opacity: 0.7; }
.mfp-arrow-left {
left: 0; }
.mfp-arrow-left:after {
border-right: 17px solid #FFF;
margin-left: 31px; }
.mfp-arrow-left:before {
margin-left: 25px;
border-right: 27px solid #3F3F3F; }
.mfp-arrow-right {
right: 0; }
.mfp-arrow-right:after {
border-left: 17px solid #FFF;
margin-left: 39px; }
.mfp-arrow-right:before {
border-left: 27px solid #3F3F3F; }
.mfp-iframe-holder {
padding-top: 40px;
padding-bottom: 40px; }
.mfp-iframe-holder .mfp-content {
line-height: 0;
width: 100%;
max-width: 900px; }
.mfp-iframe-holder .mfp-close {
top: -40px; }
.mfp-iframe-scaler {
width: 100%;
height: 0;
overflow: hidden;
padding-top: 56.25%; }
.mfp-iframe-scaler iframe {
position: absolute;
display: block;
top: 0;
left: 0;
width: 100%;
height: 100%;
box-shadow: 0 0 8px rgba(0, 0, 0, 0.6);
background: #000; }
/* Main image in popup */
img.mfp-img {
width: auto;
max-width: 100%;
height: auto;
display: block;
line-height: 0;
box-sizing: border-box;
padding: 40px 0 40px;
margin: 0 auto; }
/* The shadow behind the image */
.mfp-figure {
line-height: 0; }
.mfp-figure:after {
content: '';
position: absolute;
left: 0;
top: 40px;
bottom: 40px;
display: block;
right: 0;
width: auto;
height: auto;
z-index: -1;
box-shadow: 0 0 8px rgba(0, 0, 0, 0.6);
background: #444; }
.mfp-figure small {
color: #BDBDBD;
display: block;
font-size: 12px;
line-height: 14px; }
.mfp-figure figure {
margin: 0; }
.mfp-bottom-bar {
margin-top: -36px;
position: absolute;
top: 100%;
left: 0;
width: 100%;
cursor: auto; }
.mfp-title {
text-align: left;
line-height: 18px;
color: #F3F3F3;
word-wrap: break-word;
padding-right: 36px; }
.mfp-image-holder .mfp-content {
max-width: 100%; }
.mfp-gallery .mfp-image-holder .mfp-figure {
cursor: pointer; }
@media screen and (max-width: 800px) and (orientation: landscape), screen and (max-height: 300px) {
/**
* Remove all paddings around the image on small screen
*/
.mfp-img-mobile .mfp-image-holder {
padding-left: 0;
padding-right: 0; }
.mfp-img-mobile img.mfp-img {
padding: 0; }
.mfp-img-mobile .mfp-figure:after {
top: 0;
bottom: 0; }
.mfp-img-mobile .mfp-figure small {
display: inline;
margin-left: 5px; }
.mfp-img-mobile .mfp-bottom-bar {
background: rgba(0, 0, 0, 0.6);
bottom: 0;
margin: 0;
top: auto;
padding: 3px 5px;
position: fixed;
box-sizing: border-box; }
.mfp-img-mobile .mfp-bottom-bar:empty {
padding: 0; }
.mfp-img-mobile .mfp-counter {
right: 5px;
top: 3px; }
.mfp-img-mobile .mfp-close {
top: 0;
right: 0;
width: 35px;
height: 35px;
line-height: 35px;
background: rgba(0, 0, 0, 0.6);
position: fixed;
text-align: center;
padding: 0; } }
@media all and (max-width: 900px) {
.mfp-arrow {
-webkit-transform: scale(0.75);
transform: scale(0.75); }
.mfp-arrow-left {
-webkit-transform-origin: 0;
transform-origin: 0; }
.mfp-arrow-right {
-webkit-transform-origin: 100%;
transform-origin: 100%; }
.mfp-container {
padding-left: 6px;
padding-right: 6px; } }
/**
* Swiper 4.4.6
* Most modern mobile touch slider and framework with hardware accelerated transitions
* http://www.idangero.us/swiper/
*
* Copyright 2014-2018 Vladimir Kharlampidi
*
* Released under the MIT License
*
* Released on: December 19, 2018
*/
.swiper-container {
margin: 0 auto;
position: relative;
overflow: hidden;
list-style: none;
padding: 0;
/* Fix of Webkit flickering */
z-index: 1;
}
.swiper-container-no-flexbox .swiper-slide {
float: left;
}
.swiper-container-vertical > .swiper-wrapper {
-webkit-box-orient: vertical;
-webkit-box-direction: normal;
-webkit-flex-direction: column;
-ms-flex-direction: column;
flex-direction: column;
}
.swiper-wrapper {
position: relative;
width: 100%;
height: 100%;
z-index: 1;
display: -webkit-box;
display: -webkit-flex;
display: -ms-flexbox;
display: flex;
-webkit-transition-property: -webkit-transform;
transition-property: -webkit-transform;
-o-transition-property: transform;
transition-property: transform;
transition-property: transform, -webkit-transform;
-webkit-box-sizing: content-box;
box-sizing: content-box;
}
.swiper-container-android .swiper-slide,
.swiper-wrapper {
-webkit-transform: translate3d(0px, 0, 0);
transform: translate3d(0px, 0, 0);
}
.swiper-container-multirow > .swiper-wrapper {
-webkit-flex-wrap: wrap;
-ms-flex-wrap: wrap;
flex-wrap: wrap;
}
.swiper-container-free-mode > .swiper-wrapper {
-webkit-transition-timing-function: ease-out;
-o-transition-timing-function: ease-out;
transition-timing-function: ease-out;
margin: 0 auto;
}
.swiper-slide {
-webkit-flex-shrink: 0;
-ms-flex-negative: 0;
flex-shrink: 0;
width: 100%;
height: 100%;
position: relative;
-webkit-transition-property: -webkit-transform;
transition-property: -webkit-transform;
-o-transition-property: transform;
transition-property: transform;
transition-property: transform, -webkit-transform;
}
.swiper-slide-invisible-blank {
visibility: hidden;
}
/* Auto Height */
.swiper-container-autoheight,
.swiper-container-autoheight .swiper-slide {
height: auto;
}
.swiper-container-autoheight .swiper-wrapper {
-webkit-box-align: start;
-webkit-align-items: flex-start;
-ms-flex-align: start;
align-items: flex-start;
-webkit-transition-property: height, -webkit-transform;
transition-property: height, -webkit-transform;
-o-transition-property: transform, height;
transition-property: transform, height;
transition-property: transform, height, -webkit-transform;
}
/* 3D Effects */
.swiper-container-3d {
-webkit-perspective: 1200px;
perspective: 1200px;
}
.swiper-container-3d .swiper-wrapper,
.swiper-container-3d .swiper-slide,
.swiper-container-3d .swiper-slide-shadow-left,
.swiper-container-3d .swiper-slide-shadow-right,
.swiper-container-3d .swiper-slide-shadow-top,
.swiper-container-3d .swiper-slide-shadow-bottom,
.swiper-container-3d .swiper-cube-shadow {
-webkit-transform-style: preserve-3d;
transform-style: preserve-3d;
}
.swiper-container-3d .swiper-slide-shadow-left,
.swiper-container-3d .swiper-slide-shadow-right,
.swiper-container-3d .swiper-slide-shadow-top,
.swiper-container-3d .swiper-slide-shadow-bottom {
position: absolute;
left: 0;
top: 0;
width: 100%;
height: 100%;
pointer-events: none;
z-index: 10;
}
.swiper-container-3d .swiper-slide-shadow-left {
background-image: -webkit-gradient(linear, right top, left top, from(rgba(0, 0, 0, 0.5)), to(rgba(0, 0, 0, 0)));
background-image: -webkit-linear-gradient(right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: -o-linear-gradient(right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: linear-gradient(to left, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
}
.swiper-container-3d .swiper-slide-shadow-right {
background-image: -webkit-gradient(linear, left top, right top, from(rgba(0, 0, 0, 0.5)), to(rgba(0, 0, 0, 0)));
background-image: -webkit-linear-gradient(left, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: -o-linear-gradient(left, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: linear-gradient(to right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
}
.swiper-container-3d .swiper-slide-shadow-top {
background-image: -webkit-gradient(linear, left bottom, left top, from(rgba(0, 0, 0, 0.5)), to(rgba(0, 0, 0, 0)));
background-image: -webkit-linear-gradient(bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: -o-linear-gradient(bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: linear-gradient(to top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
}
.swiper-container-3d .swiper-slide-shadow-bottom {
background-image: -webkit-gradient(linear, left top, left bottom, from(rgba(0, 0, 0, 0.5)), to(rgba(0, 0, 0, 0)));
background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: -o-linear-gradient(top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0));
}
/* IE10 Windows Phone 8 Fixes */
.swiper-container-wp8-horizontal,
.swiper-container-wp8-horizontal > .swiper-wrapper {
-ms-touch-action: pan-y;
touch-action: pan-y;
}
.swiper-container-wp8-vertical,
.swiper-container-wp8-vertical > .swiper-wrapper {
-ms-touch-action: pan-x;
touch-action: pan-x;
}
.swiper-button-prev,
.swiper-button-next {
position: absolute;
top: 50%;
width: 27px;
height: 44px;
margin-top: -22px;
z-index: 10;
cursor: pointer;
background-size: 27px 44px;
background-position: center;
background-repeat: no-repeat;
}
.swiper-button-prev.swiper-button-disabled,
.swiper-button-next.swiper-button-disabled {
opacity: 0.35;
cursor: auto;
pointer-events: none;
}
.swiper-button-prev,
.swiper-container-rtl .swiper-button-next {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2027%2044'%3E%3Cpath%20d%3D'M0%2C22L22%2C0l2.1%2C2.1L4.2%2C22l19.9%2C19.9L22%2C44L0%2C22L0%2C22L0%2C22z'%20fill%3D'%23007aff'%2F%3E%3C%2Fsvg%3E");
left: 10px;
right: auto;
}
.swiper-button-next,
.swiper-container-rtl .swiper-button-prev {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2027%2044'%3E%3Cpath%20d%3D'M27%2C22L27%2C22L5%2C44l-2.1-2.1L22.8%2C22L2.9%2C2.1L5%2C0L27%2C22L27%2C22z'%20fill%3D'%23007aff'%2F%3E%3C%2Fsvg%3E");
right: 10px;
left: auto;
}
.swiper-button-prev.swiper-button-white,
.swiper-container-rtl .swiper-button-next.swiper-button-white {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2027%2044'%3E%3Cpath%20d%3D'M0%2C22L22%2C0l2.1%2C2.1L4.2%2C22l19.9%2C19.9L22%2C44L0%2C22L0%2C22L0%2C22z'%20fill%3D'%23ffffff'%2F%3E%3C%2Fsvg%3E");
}
.swiper-button-next.swiper-button-white,
.swiper-container-rtl .swiper-button-prev.swiper-button-white {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2027%2044'%3E%3Cpath%20d%3D'M27%2C22L27%2C22L5%2C44l-2.1-2.1L22.8%2C22L2.9%2C2.1L5%2C0L27%2C22L27%2C22z'%20fill%3D'%23ffffff'%2F%3E%3C%2Fsvg%3E");
}
.swiper-button-prev.swiper-button-black,
.swiper-container-rtl .swiper-button-next.swiper-button-black {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2027%2044'%3E%3Cpath%20d%3D'M0%2C22L22%2C0l2.1%2C2.1L4.2%2C22l19.9%2C19.9L22%2C44L0%2C22L0%2C22L0%2C22z'%20fill%3D'%23000000'%2F%3E%3C%2Fsvg%3E");
}
.swiper-button-next.swiper-button-black,
.swiper-container-rtl .swiper-button-prev.swiper-button-black {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%2027%2044'%3E%3Cpath%20d%3D'M27%2C22L27%2C22L5%2C44l-2.1-2.1L22.8%2C22L2.9%2C2.1L5%2C0L27%2C22L27%2C22z'%20fill%3D'%23000000'%2F%3E%3C%2Fsvg%3E");
}
.swiper-button-lock {
display: none;
}
.swiper-pagination {
position: absolute;
text-align: center;
-webkit-transition: 300ms opacity;
-o-transition: 300ms opacity;
transition: 300ms opacity;
-webkit-transform: translate3d(0, 0, 0);
transform: translate3d(0, 0, 0);
z-index: 10;
}
.swiper-pagination.swiper-pagination-hidden {
opacity: 0;
}
/* Common Styles */
.swiper-pagination-fraction,
.swiper-pagination-custom,
.swiper-container-horizontal > .swiper-pagination-bullets {
bottom: 10px;
left: 0;
width: 100%;
}
/* Bullets */
.swiper-pagination-bullets-dynamic {
overflow: hidden;
font-size: 0;
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet {
-webkit-transform: scale(0.33);
-ms-transform: scale(0.33);
transform: scale(0.33);
position: relative;
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active {
-webkit-transform: scale(1);
-ms-transform: scale(1);
transform: scale(1);
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-main {
-webkit-transform: scale(1);
-ms-transform: scale(1);
transform: scale(1);
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev {
-webkit-transform: scale(0.66);
-ms-transform: scale(0.66);
transform: scale(0.66);
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev-prev {
-webkit-transform: scale(0.33);
-ms-transform: scale(0.33);
transform: scale(0.33);
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next {
-webkit-transform: scale(0.66);
-ms-transform: scale(0.66);
transform: scale(0.66);
}
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next-next {
-webkit-transform: scale(0.33);
-ms-transform: scale(0.33);
transform: scale(0.33);
}
.swiper-pagination-bullet {
width: 8px;
height: 8px;
display: inline-block;
border-radius: 100%;
background: #000;
opacity: 0.2;
}
button.swiper-pagination-bullet {
border: none;
margin: 0;
padding: 0;
-webkit-box-shadow: none;
box-shadow: none;
-webkit-appearance: none;
-moz-appearance: none;
appearance: none;
}
.swiper-pagination-clickable .swiper-pagination-bullet {
cursor: pointer;
}
.swiper-pagination-bullet-active {
opacity: 1;
background: #007aff;
}
.swiper-container-vertical > .swiper-pagination-bullets {
right: 10px;
top: 50%;
-webkit-transform: translate3d(0px, -50%, 0);
transform: translate3d(0px, -50%, 0);
}
.swiper-container-vertical > .swiper-pagination-bullets .swiper-pagination-bullet {
margin: 6px 0;
display: block;
}
.swiper-container-vertical > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic {
top: 50%;
-webkit-transform: translateY(-50%);
-ms-transform: translateY(-50%);
transform: translateY(-50%);
width: 8px;
}
.swiper-container-vertical > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic .swiper-pagination-bullet {
display: inline-block;
-webkit-transition: 200ms top, 200ms -webkit-transform;
transition: 200ms top, 200ms -webkit-transform;
-o-transition: 200ms transform, 200ms top;
transition: 200ms transform, 200ms top;
transition: 200ms transform, 200ms top, 200ms -webkit-transform;
}
.swiper-container-horizontal > .swiper-pagination-bullets .swiper-pagination-bullet {
margin: 0 4px;
}
.swiper-container-horizontal > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic {
left: 50%;
-webkit-transform: translateX(-50%);
-ms-transform: translateX(-50%);
transform: translateX(-50%);
white-space: nowrap;
}
.swiper-container-horizontal > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic .swiper-pagination-bullet {
-webkit-transition: 200ms left, 200ms -webkit-transform;
transition: 200ms left, 200ms -webkit-transform;
-o-transition: 200ms transform, 200ms left;
transition: 200ms transform, 200ms left;
transition: 200ms transform, 200ms left, 200ms -webkit-transform;
}
.swiper-container-horizontal.swiper-container-rtl > .swiper-pagination-bullets-dynamic .swiper-pagination-bullet {
-webkit-transition: 200ms right, 200ms -webkit-transform;
transition: 200ms right, 200ms -webkit-transform;
-o-transition: 200ms transform, 200ms right;
transition: 200ms transform, 200ms right;
transition: 200ms transform, 200ms right, 200ms -webkit-transform;
}
/* Progress */
.swiper-pagination-progressbar {
background: rgba(0, 0, 0, 0.25);
position: absolute;
}
.swiper-pagination-progressbar .swiper-pagination-progressbar-fill {
background: #007aff;
position: absolute;
left: 0;
top: 0;
width: 100%;
height: 100%;
-webkit-transform: scale(0);
-ms-transform: scale(0);
transform: scale(0);
-webkit-transform-origin: left top;
-ms-transform-origin: left top;
transform-origin: left top;
}
.swiper-container-rtl .swiper-pagination-progressbar .swiper-pagination-progressbar-fill {
-webkit-transform-origin: right top;
-ms-transform-origin: right top;
transform-origin: right top;
}
.swiper-container-horizontal > .swiper-pagination-progressbar,
.swiper-container-vertical > .swiper-pagination-progressbar.swiper-pagination-progressbar-opposite {
width: 100%;
height: 4px;
left: 0;
top: 0;
}
.swiper-container-vertical > .swiper-pagination-progressbar,
.swiper-container-horizontal > .swiper-pagination-progressbar.swiper-pagination-progressbar-opposite {
width: 4px;
height: 100%;
left: 0;
top: 0;
}
.swiper-pagination-white .swiper-pagination-bullet-active {
background: #ffffff;
}
.swiper-pagination-progressbar.swiper-pagination-white {
background: rgba(255, 255, 255, 0.25);
}
.swiper-pagination-progressbar.swiper-pagination-white .swiper-pagination-progressbar-fill {
background: #ffffff;
}
.swiper-pagination-black .swiper-pagination-bullet-active {
background: #000000;
}
.swiper-pagination-progressbar.swiper-pagination-black {
background: rgba(0, 0, 0, 0.25);
}
.swiper-pagination-progressbar.swiper-pagination-black .swiper-pagination-progressbar-fill {
background: #000000;
}
.swiper-pagination-lock {
display: none;
}
/* Scrollbar */
.swiper-scrollbar {
border-radius: 10px;
position: relative;
-ms-touch-action: none;
background: rgba(0, 0, 0, 0.25);
}
.swiper-container-horizontal > .swiper-scrollbar {
position: absolute;
left: 1%;
bottom: 3px;
z-index: 50;
height: 5px;
width: 98%;
}
.swiper-container-vertical > .swiper-scrollbar {
position: absolute;
right: 3px;
top: 1%;
z-index: 50;
width: 5px;
height: 98%;
}
.swiper-scrollbar-drag {
height: 100%;
width: 100%;
position: relative;
background: rgba(0, 0, 0, 0.5);
border-radius: 10px;
left: 0;
top: 0;
}
.swiper-scrollbar-cursor-drag {
cursor: move;
}
.swiper-scrollbar-lock {
display: none;
}
.swiper-zoom-container {
width: 100%;
height: 100%;
display: -webkit-box;
display: -webkit-flex;
display: -ms-flexbox;
display: flex;
-webkit-box-pack: center;
1webkit-justify-content: center;
-ms-flex-pack: center;
justify-content: center;
-webkit-box-align: center;
-webkit-align-items: center;
-ms-flex-align: center;
align-items: center;
text-align: center;
}
.swiper-zoom-container > img,
.swiper-zoom-container > svg,
.swiper-zoom-container > canvas {
max-width: 100%;
max-height: 100%;
-o-object-fit: contain;
object-fit: contain;
}
.swiper-slide-zoomed {
cursor: move;
}
/* Preloader */
.swiper-lazy-preloader {
width: 42px;
height: 42px;
position: absolute;
left: 50%;
top: 50%;
margin-left: -21px;
margin-top: -21px;
z-index: 10;
-webkit-transform-origin: 50%;
-ms-transform-origin: 50%;
transform-origin: 50%;
-webkit-animation: swiper-preloader-spin 1s steps(12, end) infinite;
animation: swiper-preloader-spin 1s steps(12, end) infinite;
}
.swiper-lazy-preloader:after {
display: block;
content: '';
width: 100%;
height: 100%;
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D'0%200%20120%20120'%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20xmlns%3Axlink%3D'http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink'%3E%3Cdefs%3E%3Cline%20id%3D'l'%20x1%3D'60'%20x2%3D'60'%20y1%3D'7'%20y2%3D'27'%20stroke%3D'%236c6c6c'%20stroke-width%3D'11'%20stroke-linecap%3D'round'%2F%3E%3C%2Fdefs%3E%3Cg%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(30%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(60%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(90%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(120%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(150%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.37'%20transform%3D'rotate(180%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.46'%20transform%3D'rotate(210%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.56'%20transform%3D'rotate(240%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.66'%20transform%3D'rotate(270%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.75'%20transform%3D'rotate(300%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.85'%20transform%3D'rotate(330%2060%2C60)'%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E");
background-position: 50%;
background-size: 100%;
background-repeat: no-repeat;
}
.swiper-lazy-preloader-white:after {
background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D'0%200%20120%20120'%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20xmlns%3Axlink%3D'http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink'%3E%3Cdefs%3E%3Cline%20id%3D'l'%20x1%3D'60'%20x2%3D'60'%20y1%3D'7'%20y2%3D'27'%20stroke%3D'%23fff'%20stroke-width%3D'11'%20stroke-linecap%3D'round'%2F%3E%3C%2Fdefs%3E%3Cg%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(30%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(60%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(90%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(120%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.27'%20transform%3D'rotate(150%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.37'%20transform%3D'rotate(180%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.46'%20transform%3D'rotate(210%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.56'%20transform%3D'rotate(240%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.66'%20transform%3D'rotate(270%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.75'%20transform%3D'rotate(300%2060%2C60)'%2F%3E%3Cuse%20xlink%3Ahref%3D'%23l'%20opacity%3D'.85'%20transform%3D'rotate(330%2060%2C60)'%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E");
}
@-webkit-keyframes swiper-preloader-spin {
100% {
-webkit-transform: rotate(360deg);
transform: rotate(360deg);
}
}
@keyframes swiper-preloader-spin {
100% {
-webkit-transform: rotate(360deg);
transform: rotate(360deg);
}
}
/* a11y */
.swiper-container .swiper-notification {
position: absolute;
left: 0;
top: 0;
pointer-events: none;
opacity: 0;
z-index: -1000;
}
.swiper-container-fade.swiper-container-free-mode .swiper-slide {
-webkit-transition-timing-function: ease-out;
-o-transition-timing-function: ease-out;
transition-timing-function: ease-out;
}
.swiper-container-fade .swiper-slide {
pointer-events: none;
-webkit-transition-property: opacity;
-o-transition-property: opacity;
transition-property: opacity;
}
.swiper-container-fade .swiper-slide .swiper-slide {
pointer-events: none;
}
.swiper-container-fade .swiper-slide-active,
.swiper-container-fade .swiper-slide-active .swiper-slide-active {
pointer-events: auto;
}
.swiper-container-cube {
overflow: visible;
}
.swiper-container-cube .swiper-slide {
pointer-events: none;
-webkit-backface-visibility: hidden;
backface-visibility: hidden;
z-index: 1;
visibility: hidden;
-webkit-transform-origin: 0 0;
-ms-transform-origin: 0 0;
transform-origin: 0 0;
width: 100%;
height: 100%;
}
.swiper-container-cube .swiper-slide .swiper-slide {
pointer-events: none;
}
.swiper-container-cube.swiper-container-rtl .swiper-slide {
-webkit-transform-origin: 100% 0;
-ms-transform-origin: 100% 0;
transform-origin: 100% 0;
}
.swiper-container-cube .swiper-slide-active,
.swiper-container-cube .swiper-slide-active .swiper-slide-active {
pointer-events: auto;
}
.swiper-container-cube .swiper-slide-active,
.swiper-container-cube .swiper-slide-next,
.swiper-container-cube .swiper-slide-prev,
.swiper-container-cube .swiper-slide-next + .swiper-slide {
pointer-events: auto;
visibility: visible;
}
.swiper-container-cube .swiper-slide-shadow-top,
.swiper-container-cube .swiper-slide-shadow-bottom,
.swiper-container-cube .swiper-slide-shadow-left,
.swiper-container-cube .swiper-slide-shadow-right {
z-index: 0;
-webkit-backface-visibility: hidden;
backface-visibility: hidden;
}
.swiper-container-cube .swiper-cube-shadow {
position: absolute;
left: 0;
bottom: 0px;
width: 100%;
height: 100%;
background: #000;
opacity: 0.6;
-webkit-filter: blur(50px);
filter: blur(50px);
z-index: 0;
}
.swiper-container-flip {
overflow: visible;
}
.swiper-container-flip .swiper-slide {
pointer-events: none;
-webkit-backface-visibility: hidden;
backface-visibility: hidden;
z-index: 1;
}
.swiper-container-flip .swiper-slide .swiper-slide {
pointer-events: none;
}
.swiper-container-flip .swiper-slide-active,
.swiper-container-flip .swiper-slide-active .swiper-slide-active {
pointer-events: auto;
}
.swiper-container-flip .swiper-slide-shadow-top,
.swiper-container-flip .swiper-slide-shadow-bottom,
.swiper-container-flip .swiper-slide-shadow-left,
.swiper-container-flip .swiper-slide-shadow-right {
z-index: 0;
-webkit-backface-visibility: hidden;
backface-visibility: hidden;
}
.swiper-container-coverflow .swiper-wrapper {
/* Windows 8 IE 10 fix */
-ms-perspective: 1200px;
}

</style>
<!-- Scripts -->
<script src="https://gamestock.shop/scripts/jquery.min.js"></script> <!-- jQuery for JavaScript plugins -->
<script src="https://gamestock.shop/scripts/jquery.easing.min.js"></script> <!-- jQuery Easing for smooth scrolling between anchors -->
<script src="https://gamestock.shop/scripts/swiper.min.js"></script> <!-- Swiper for image and text sliders -->
<script src="https://gamestock.shop/scripts/jquery.magnific-popup.js"></script> <!-- Magnific Popup for lightboxes -->
<script src="https://gamestock.shop//scripts/scripts.js"></script> <!-- Custom scripts -->
</body>
</html>
</noindex>
