<?php
// /admin/sync_buyaccs.php - Упрощенная версия с конвертером валют
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

// Подключаем файлы
require_once '../includes/config.php';
require_once '../includes/ApiSuppliers/BuyAccsNet.php';
require_once '../includes/price_calculator.php';
require_once '../includes/currency_converter.php'; // ДОБАВЛЕНО

echo "<!DOCTYPE html>
<html>
<head>
    <title>Синхронизация buy-accs.net</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
</head>
<body>
<nav class=\"navbar navbar-dark bg-primary\">
    <div class=\"container\">
        <span class=\"navbar-brand\">🔄 Синхронизация buy-accs.net</span>
        <a href=\"suppliers_info.php\" class=\"btn btn-light\">← Назад</a>
    </div>
</nav>

<div class=\"container mt-4\">";

try {
    // Подключаемся к БД
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Инициализируем конвертер валют
    $converter = new CurrencyConverter(); // ДОБАВЛЕНО
    
    // Получаем поставщика
    $supplier_id = 1;
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        die("<div class='alert alert-danger'>❌ Поставщик не найден</div>");
    }
    
    // Получаем данные о курсе валют для этого поставщика
    $rate_data = $converter->getSupplierRate($supplier_id);
    $is_conversion_active = $rate_data['is_active'] && $rate_data['currency_code'] != 'RUB';
    $rate_to_rub = $rate_data['rate_to_rub'];
    $currency_code = $rate_data['currency_code'];
    
    echo "<div class='card mb-4'>
        <div class='card-header'>
            <h4>Поставщик: " . htmlspecialchars($supplier['name']) . "</h4>
        </div>
        <div class='card-body'>
            <div class='row'>
                <div class='col-md-6'>
                    <p><strong>Наценка:</strong> " . $supplier['markup_value'] . ($supplier['markup_type'] == 'percent' ? '%' : '₽') . "</p>
                </div>
                <div class='col-md-6'>
                    <p><strong>Валюта:</strong> <span class='badge bg-info'>" . $currency_code . "</span></p>";
    
    if ($is_conversion_active) {
        echo "<p><strong>Курс к рублю:</strong> 1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽</p>";
        echo "<p><span class='badge bg-success'>Конвертация активна</span></p>";
    } else {
        echo "<p><span class='badge bg-secondary'>Цены в рублях</span></p>";
        echo "<p><small class='text-muted'>Настройте курс в разделе <a href='currency_rates.php'>Курсы валют</a></small></p>";
    }
    
    echo "        </div>
            </div>
        </div>
    </div>";
    
    // Обработка синхронизации
    if (isset($_GET['action']) && $_GET['action'] == 'sync') {
        echo "<div class='card'>
            <div class='card-header bg-warning'>
                <h5>🔄 Запуск синхронизации...</h5>
            </div>
            <div class='card-body'>";
        
        $api = new BuyAccsNet($supplier['api_key']);
        
        // 1. Тест подключения
        echo "<h6>1. Тест подключения...</h6>";
        $test = $api->testConnection('rub');
        
        if ($test['success']) {
            echo "<div class='alert alert-success'>✅ " . $test['message'] . "</div>";
            
            // 2. Получаем товары
            echo "<h6>2. Получение товаров...</h6>";
            $result = $api->getGoods('rub', ['limit' => 10]);
            
            if ($result['success'] && isset($result['data']['goods'])) {
                $goods = $result['data']['goods'];
                $total = count($goods);
                
                echo "<div class='alert alert-success'>✅ Получено товаров: $total</div>";
                
                // 3. Обработка товаров
                echo "<h6>3. Обработка товаров...</h6>";
                
                $processed = 0;
                $added = 0;
                $updated = 0;
                $errors = 0;
                $converted_total = 0;
                
                foreach ($goods as $item) {
                    $processed++;
                    
                    try {
                        // Проверяем наличие товара
                        $check = $pdo->prepare("SELECT id FROM supplier_products WHERE supplier_id = ? AND external_id = ?");
                        $check->execute([$supplier_id, $item['id']]);
                        $existing = $check->fetch();
                        
                        // Оригинальная цена от поставщика
                        $original_price = $item['price'];
                        
                        // КОНВЕРТАЦИЯ ВАЛЮТЫ - ОСНОВНОЕ ИЗМЕНЕНИЕ
                        if ($is_conversion_active && $currency_code != 'RUB') {
                            // Конвертируем цену в рубли
                            $converted_price = $converter->convertToRub($original_price, $supplier_id);
                            $converted_total += $original_price;
                            
                            echo "<div class='alert alert-light'>";
                            echo "✅ Товар #" . $item['id'] . " - " . htmlspecialchars(substr($item['title'], 0, 50)) . "...<br>";
                            echo "<small class='text-info'>";
                            echo "Цена: " . $original_price . " " . $currency_code . " → " . 
                                  number_format($converted_price, 2) . " ₽ (курс: " . $rate_to_rub . ")";
                            echo "</small>";
                            echo "</div>";
                        } else {
                            // Без конвертации
                            $converted_price = $original_price;
                            
                            echo "<div class='alert alert-light'>";
                            echo "✅ Товар #" . $item['id'] . " - " . htmlspecialchars(substr($item['title'], 0, 50)) . "...<br>";
                            echo "<small class='text-muted'>Цена: " . $original_price . " ₽</small>";
                            echo "</div>";
                        }
                        
                        // Рассчитываем нашу цену (с наценкой)
                        $calculated = PriceCalculator::calculatePrice(
                            $converted_price, // Используем конвертированную цену
                            $supplier['markup_type'],
                            $supplier['markup_value']
                        );
                        
                        // Определяем статус товара
                        $stock = $item['count'] ?? 0;
                        $is_available = $stock > 0;
                        
                        if ($existing) {
                            // Обновляем существующий товар
                            $sql = "UPDATE supplier_products SET 
                                name = ?, 
                                category = ?, 
                                price = ?, 
                                our_price = ?, 
                                original_price = ?, 
                                currency_code = ?, 
                                converted_price = ?, 
                                stock = ?, 
                                last_updated = NOW()
                                WHERE id = ?";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $item['title'],
                                $item['category_id'],
                                $converted_price,           // price (для показа)
                                $calculated['final_price'], // our_price (с наценкой)
                                $original_price,            // original_price (оригинал в валюте поставщика)
                                $currency_code,             // currency_code
                                $converted_price,           // converted_price (конвертированная цена)
                                $stock,
                                $existing['id']
                            ]);
                            
                            $updated++;
                        } else {
                            // Добавляем новый товар
                            $sql = "INSERT INTO supplier_products 
                                (supplier_id, external_id, name, category, price, our_price, 
                                 original_price, currency_code, converted_price, stock, last_updated) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $supplier_id,
                                $item['id'],
                                $item['title'],
                                $item['category_id'],
                                $converted_price,           // price (для показа)
                                $calculated['final_price'], // our_price (с наценкой)
                                $original_price,            // original_price
                                $currency_code,             // currency_code
                                $converted_price,           // converted_price
                                $stock
                            ]);
                            
                            $added++;
                        }
                        
                    } catch (Exception $e) {
                        $errors++;
                        echo "<div class='alert alert-danger'>❌ Ошибка товара #" . $item['id'] . ": " . $e->getMessage() . "</div>";
                    }
                }
                
                // Обновляем время синхронизации
                $pdo->prepare("UPDATE suppliers SET last_sync = NOW() WHERE id = ?")
                    ->execute([$supplier_id]);
                
                // Итоги
                echo "<div class='alert alert-success mt-3'>
                    <h5>📊 Итоги синхронизации</h5>
                    <ul>
                        <li>Обработано: $processed</li>
                        <li>Добавлено: $added</li>
                        <li>Обновлено: $updated</li>
                        <li>Ошибок: $errors</li>";
                
                if ($is_conversion_active && $converted_total > 0) {
                    echo "<li>Конвертировано валюты: " . number_format($converted_total, 2) . " " . $currency_code . "</li>";
                    echo "<li>Курс конвертации: 1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽</li>";
                }
                
                echo "</ul>
                </div>";
                
                // Пример товара с конвертацией
                if (count($goods) > 0) {
                    $sample = $goods[0];
                    $sample_original_price = $sample['price'];
                    
                    if ($is_conversion_active && $currency_code != 'RUB') {
                        $sample_converted_price = $converter->convertToRub($sample_original_price, $supplier_id);
                    } else {
                        $sample_converted_price = $sample_original_price;
                    }
                    
                    $calculated = PriceCalculator::calculatePrice(
                        $sample_converted_price,
                        $supplier['markup_type'],
                        $supplier['markup_value']
                    );
                    
                    echo "<div class='card mt-3'>
                        <div class='card-header'>Пример товара после обработки</div>
                        <div class='card-body'>";
                    
                    echo "<p><strong>" . htmlspecialchars($sample['title']) . "</strong></p>";
                    
                    if ($is_conversion_active && $currency_code != 'RUB') {
                        echo "<p>Цена поставщика: <span class='text-primary'>" . 
                             number_format($sample_original_price, 2) . " " . $currency_code . "</span></p>";
                        echo "<p>Конвертация (" . $rate_to_rub . "): " . 
                             number_format($sample_original_price, 2) . " " . $currency_code . " × " . $rate_to_rub . " = " . 
                             "<span class='text-info'>" . number_format($sample_converted_price, 2) . " ₽</span></p>";
                    } else {
                        echo "<p>Цена поставщика: <span class='text-info'>" . 
                             number_format($sample_original_price, 2) . " ₽</span></p>";
                    }
                    
                    echo "<p>Наценка (" . $supplier['markup_value'] . 
                         ($supplier['markup_type'] == 'percent' ? '%' : '₽') . "): " . 
                         $calculated['markup_amount'] . " ₽</p>";
                    echo "<p class='text-success'><strong>Итоговая цена: " . 
                         number_format($calculated['final_price'], 2) . " ₽</strong></p>";
                    
                    // Информация о хранении в базе
                    echo "<hr><div class='alert alert-light'>";
                    echo "<small><strong>Данные в базе:</strong><br>";
                    echo "• original_price: " . number_format($sample_original_price, 2) . "<br>";
                    echo "• currency_code: " . $currency_code . "<br>";
                    echo "• converted_price: " . number_format($sample_converted_price, 2) . "<br>";
                    echo "• price: " . number_format($sample_converted_price, 2) . " (для показа)<br>";
                    echo "• our_price: " . number_format($calculated['final_price'], 2) . " (с наценкой)</small>";
                    echo "</div>";
                    
                    echo "</div></div>";
                }
                
            } else {
                echo "<div class='alert alert-danger'>❌ Ошибка получения товаров</div>";
                if (isset($result['message'])) {
                    echo "<p>Сообщение: " . $result['message'] . "</p>";
                }
            }
            
        } else {
            echo "<div class='alert alert-danger'>❌ " . $test['message'] . "</div>";
        }
        
        echo "</div></div>";
        
    } else {
        // Кнопка запуска
        echo "<div class='card'>
            <div class='card-header'>
                <h5>Запуск синхронизации</h5>
            </div>
            <div class='card-body'>
                <p>Нажмите кнопку для синхронизации первых 10 товаров.</p>";
        
        if ($is_conversion_active && $currency_code != 'RUB') {
            echo "<div class='alert alert-info mb-3'>
                <i class='fas fa-info-circle'></i> 
                <strong>Конвертация активна!</strong> 
                Цены будут конвертированы из " . $currency_code . " в рубли по курсу: 
                <strong>1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽</strong>
            </div>";
        }
        
        echo "<a href='?action=sync' class='btn btn-primary btn-lg'>🔄 Запустить синхронизацию</a>
                <a href='suppliers_info.php' class='btn btn-secondary'>Отмена</a>
                <a href='currency_rates.php?supplier_id=" . $supplier_id . "' class='btn btn-info'>
                    <i class='fas fa-exchange-alt'></i> Настроить курс валюты
                </a>
            </div>
        </div>";
    }
    
    // Показываем текущие товары
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Товары в базе</h5>
        </div>
        <div class='card-body'>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM supplier_products WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $count = $stmt->fetch();
    
    echo "<p>Товаров в базе: <strong>" . $count['count'] . "</strong></p>";
    
    if ($count['count'] > 0) {
        $stmt = $pdo->prepare("
            SELECT sp.*, 
                   CASE 
                       WHEN sp.currency_code = 'RUB' THEN '₽'
                       ELSE sp.currency_code 
                   END as currency_display
            FROM supplier_products sp 
            WHERE supplier_id = ? 
            ORDER BY last_updated DESC 
            LIMIT 5
        ");
        $stmt->execute([$supplier_id]);
        $products = $stmt->fetchAll();
        
        echo "<table class='table table-sm'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Оригинальная цена</th>
                    <th>Конвертировано</th>
                    <th>Наша цена</th>
                    <th>Валюта</th>
                    <th>В наличии</th>
                </tr>
            </thead>
            <tbody>";
        
        foreach ($products as $product) {
            $original_price = $product['original_price'] ?? $product['price'];
            $currency = $product['currency_code'] ?? 'RUB';
            
            echo "<tr>";
            echo "<td>" . $product['external_id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($product['name'], 0, 30)) . "...</td>";
            
            // Оригинальная цена
            echo "<td>";
            if ($currency != 'RUB') {
                echo "<span class='text-primary'>" . number_format($original_price, 2) . " " . $currency . "</span>";
            } else {
                echo number_format($original_price, 2) . " ₽";
            }
            echo "</td>";
            
            // Конвертированная цена
            echo "<td>";
            if ($currency != 'RUB' && $product['converted_price'] > 0) {
                echo "<span class='text-info'>" . number_format($product['converted_price'], 2) . " ₽</span>";
            } else {
                echo "<span class='text-muted'>" . number_format($product['price'], 2) . " ₽</span>";
            }
            echo "</td>";
            
            // Наша цена (с наценкой)
            echo "<td><strong class='text-success'>" . number_format($product['our_price'], 2) . " ₽</strong></td>";
            
            // Валюта
            echo "<td>";
            if ($currency != 'RUB') {
                echo "<span class='badge bg-info'>" . $currency . "</span>";
            } else {
                echo "<span class='badge bg-secondary'>RUB</span>";
            }
            echo "</td>";
            
            // В наличии
            echo "<td>";
            if ($product['stock'] > 10) {
                echo "<span class='badge bg-success'>" . $product['stock'] . "</span>";
            } elseif ($product['stock'] > 0) {
                echo "<span class='badge bg-warning'>" . $product['stock'] . "</span>";
            } else {
                echo "<span class='badge bg-danger'>0</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        // Сводка по валютам
        $stmt = $pdo->prepare("
            SELECT currency_code, COUNT(*) as count, 
                   AVG(original_price) as avg_original,
                   AVG(converted_price) as avg_converted
            FROM supplier_products 
            WHERE supplier_id = ? 
            GROUP BY currency_code
        ");
        $stmt->execute([$supplier_id]);
        $currency_stats = $stmt->fetchAll();
        
        if (count($currency_stats) > 0) {
            echo "<div class='mt-3 p-3 bg-light rounded'>";
            echo "<h6>📊 Статистика по валютам:</h6>";
            foreach ($currency_stats as $stat) {
                echo "<small class='me-3'>";
                echo "<span class='badge bg-info'>" . $stat['currency_code'] . "</span> ";
                echo $stat['count'] . " товаров";
                
                if ($stat['currency_code'] != 'RUB') {
                    echo " (средняя: " . number_format($stat['avg_original'], 2) . " " . 
                         $stat['currency_code'] . " → " . number_format($stat['avg_converted'], 2) . " ₽)";
                }
                echo "</small>";
            }
            echo "</div>";
        }
    }
    
    echo "</div></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>