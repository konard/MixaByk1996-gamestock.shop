<?php
// templates/header.php - Общий заголовок для всего сайта (публичный!)
// Стиль навигации идентичен главной странице (index.php)
if (!isset($page_title)) $page_title = SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ru-RU">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<title><?php echo htmlspecialchars($page_title); ?></title>
<!-- Styles (как на главной странице) -->
<link rel="preconnect" href="https://fonts.gstatic.com" />
<link href="https://gamestock.shop/styles/fonts.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/awesome.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/tailwind.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/magnific-popup.css" rel="stylesheet" />
<link href="https://gamestock.shop/styles/styles.css" rel="stylesheet" />
<!-- Bootstrap (для контента страниц) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<!-- Favicon -->
<link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
<meta name="lava-verify" content="S3a0fe43f5k4a1dr" />
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
    .product-card {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        height: 100%;
        transition: transform 0.3s, box-shadow 0.3s;
        background: white;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .product-price {
        font-size: 1.5rem;
        color: #28a745;
        font-weight: bold;
    }
    .product-stock {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.9rem;
    }
    .stock-available {
        background-color: #d4edda;
        color: #155724;
    }
    .stock-low {
        background-color: #fff3cd;
        color: #856404;
    }
    .stock-out {
        background-color: #f8d7da;
        color: #721c24;
    }
    .category-badge {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.8rem;
        margin-right: 5px;
    }
    .search-info {
        background: #e8f4fd;
        border-left: 4px solid #0d6efd;
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    /* Убираем ВСЕ админские стили */
    .sidebar, .admin-header, .card-admin, .btn-admin {
        display: none !important;
    }
    /* Отступ для контента под фиксированной навигацией */
    .page-content-wrapper {
        padding-top: 80px;
    }
</style>
</head>
<body data-spy="scroll" data-target=".fixed-top">
<!-- Navigation (идентична главной странице) -->
<nav class="navbar fixed-top">
<div class="container sm:px-4 lg:px-8 flex flex-wrap items-center justify-between lg:flex-nowrap">
<!-- Image Logo -->
<a class="inline-block mr-4 py-0.5 text-xl whitespace-nowrap hover:no-underline focus:no-underline" href="/">
<img src="https://gamestock.shop/images/logo.svg" alt="alternative" class="h-8" />
</a>
<button class="background-transparent rounded text-xl leading-none hover:no-underline focus:no-underline lg:hidden lg:text-gray-400" type="button" data-toggle="offcanvas">
<span class="navbar-toggler-icon inline-block w-8 h-8 align-middle"></span>
</button>
<div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault" lg:flex lg:flex-grow lg:items-center>
<ul class="pl-0 mt-3 mb-2 ml-auto flex flex-col list-none lg:mt-0 lg:mb-0 lg:flex-row">
<li>
<a class="nav-link page-scroll" href="/#fast_order"><img class="inline" src="https://gamestock.shop/icons/order.png" alt="icon"> Быстрый заказ</a>
</li>
<li>
<a class="nav-link page-scroll" href="/cabinet"><img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon"> Вход</a>
</li>
<li>
<a class="nav-link page-scroll" href="/cabinet/reg/"><img class="inline" src="https://gamestock.shop/icons/sign-up.png" alt="icon"> Регистрация</a>
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

<div class="page-content-wrapper">
<div class="container mt-4">
