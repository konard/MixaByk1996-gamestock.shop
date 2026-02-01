<?php
// admin/templates/header.php - Шапка админ-панели
if (!isset($page_title)) $page_title = "Админ-панель";
if (!isset($page_icon)) $page_icon = "fas fa-cog";
if (!isset($page_subtitle)) $page_subtitle = "Управление магазином";
if (!isset($active_menu)) $active_menu = "dashboard";
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="lava-verify" content="S3a0fe43f5k4a1dr" />
    <style>
        body {
            background-color: #f5f5f5;
        }
        .sidebar {
            background: #2c3e50;
            color: white;
            min-height: 100vh;
            padding: 0;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 15px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #34495e;
            color: white;
            border-left-color: #3498db;
        }
        .sidebar .nav-link i {
            width: 25px;
        }
        .admin-header {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-admin {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-top: 4px solid #3498db;
        }
        .btn-admin {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
        }
        .logout-link {
            color: #e74c3c !important;
        }
        .logout-link:hover {
            background-color: rgba(231, 76, 60, 0.1) !important;
            border-left-color: #e74c3c !important;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
        /* Стили для таблиц */
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <!-- Боковая панель -->
    <div class="sidebar">
        <div class="p-3 text-center border-bottom">
            <h4 class="mb-0">👑 Админ-панель</h4>
            <small><?php echo SITE_NAME; ?></small>
        </div>
        
        <nav class="nav flex-column mt-3">
            <a href="index.php" class="nav-link <?php echo $active_menu == "dashboard" ? "active" : ""; ?>">
                <i class="fas fa-tachometer-alt"></i> Дашборд
            </a>
            <!-- ДОБАВЛЕНО: Товары -->
            <a href="products.php" class="nav-link <?php echo $active_menu == "products" ? "active" : ""; ?>">
                <i class="fas fa-box"></i> Товары
            </a>
            <!-- ДОБАВЛЕНО: Пользователи -->
            <a href="users.php" class="nav-link <?php echo $active_menu == "users" ? "active" : ""; ?>">
                <i class="fas fa-users"></i> Пользователи
            </a>
            <a href="suppliers_info.php" class="nav-link <?php echo $active_menu == "suppliers" ? "active" : ""; ?>">
                <i class="fas fa-truck"></i> Поставщики
            </a>
            <a href="edit_supplier.php" class="nav-link <?php echo $active_menu == "markup" ? "active" : ""; ?>">
                <i class="fas fa-percentage"></i> Наценка
            </a>
            <a href="currency_rates.php" class="nav-link <?php echo $active_menu == "currency" ? "active" : ""; ?>">
                <i class="fas fa-exchange-alt"></i> Курсы валют
            </a>
            <a href="sync_buyaccs.php" class="nav-link <?php echo $active_menu == "sync" ? "active" : ""; ?>">
                <i class="fas fa-sync"></i> Синхронизация
            </a>
            <a href="sync_full.php" class="nav-link <?php echo $active_menu == "fullsync" ? "active" : ""; ?>">
                <i class="fas fa-redo"></i> Полная синхронизация
            </a>
            <a href="payments_info.php" class="nav-link <?php echo $active_menu == "payments" ? "active" : ""; ?>">
                <i class="fas fa-credit-card"></i> Платежи
            </a>
            
            <div class="mt-3 border-top pt-3">
                <a href="/cabinet/" class="nav-link">
                    <i class="fas fa-user"></i> Мой профиль
                </a>
                <a href="/" class="nav-link">
                    <i class="fas fa-home"></i> На сайт
                </a>
                <a href="?logout" class="nav-link logout-link">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </nav>
        
        <div class="position-absolute bottom-0 start-0 end-0 p-3 border-top">
            <small class="text-muted">Админ: <?php echo htmlspecialchars($_SESSION["username"] ?? "Admin"); ?></small>
            <br>
            <small class="text-muted"><?php echo date("d.m.Y H:i"); ?></small>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="main-content">
        <!-- Заголовок -->
        <div class="admin-header">
            <h1 class="h3 mb-0">
                <?php if (isset($page_icon)): ?>
                    <i class="<?php echo $page_icon; ?> me-2"></i>
                <?php endif; ?>
                <?php echo $page_title; ?>
            </h1>
            <?php if (isset($page_subtitle)): ?>
                <p class="text-muted mb-0"><?php echo $page_subtitle; ?></p>
            <?php endif; ?>
            
            <!-- Бейджик для отображения валюты -->
            <?php if ($active_menu == "currency"): ?>
                <div class="mt-2">
                    <span class="badge bg-info">
                        <i class="fas fa-exchange-alt me-1"></i> Система конвертации валют
                    </span>
                    <small class="text-muted ms-2">
                        Для пользователей цены всегда в рублях
                    </small>
                </div>
            <?php endif; ?>
            
            <!-- Бейджик для товаров -->
            <?php if ($active_menu == "products"): ?>
                <div class="mt-2">
                    <span class="badge bg-success">
                        <i class="fas fa-box me-1"></i> Управление товарами
                    </span>
                    <small class="text-muted ms-2">
                        Всего товаров: <?php 
                            if (isset($total)) echo number_format($total);
                            else echo "0";
                        ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <!-- Бейджик для пользователей -->
            <?php if ($active_menu == "users"): ?>
                <div class="mt-2">
                    <span class="badge bg-info">
                        <i class="fas fa-users me-1"></i> Управление пользователями
                    </span>
                    <small class="text-muted ms-2">
                        Всего пользователей: <?php 
                            if (isset($total)) echo number_format($total);
                            else echo "0";
                        ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <!-- Дополнительные кнопки для страниц -->
            <?php if ($active_menu == "products"): ?>
                <div class="mt-2">
                    <a href="sync_buyaccs.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-sync me-1"></i> Синхронизировать
                    </a>
                    <a href="edit_supplier.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-percentage me-1"></i> Наценка
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($active_menu == "users"): ?>
                <div class="mt-2">
                    <button class="btn btn-sm btn-primary" onclick="addNewUser()">
                        <i class="fas fa-user-plus me-1"></i> Добавить пользователя
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- JavaScript для добавления пользователя -->
        <script>
        function addNewUser() {
            // Сброс формы
            document.getElementById('edit_user_id').value = '0';
            document.getElementById('edit_username').value = '';
            document.getElementById('edit_email').value = '';
            document.getElementById('edit_balance').value = '0';
            document.getElementById('edit_is_admin').checked = false;
            
            // Показ модального окна
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        </script>
        
        <!-- Модальное окно для добавления/редактирования пользователя -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Добавить/редактировать пользователя</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editUserForm">
                            <input type="hidden" id="edit_user_id" name="user_id" value="0">
                            <div class="mb-3">
                                <label class="form-label">Имя пользователя *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="edit_password" name="password" 
                                       placeholder="Оставьте пустым, чтобы не менять">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Баланс</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="edit_balance" name="balance" 
                                           value="0" step="0.01" min="0">
                                    <span class="input-group-text">₽</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_admin" name="is_admin">
                                    <label class="form-check-label" for="edit_is_admin">Администратор</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser()">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно для редактирования товара -->
        <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактирование товара</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProductForm">
                            <input type="hidden" id="product_id" name="product_id">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Название товара *</label>
                                    <input type="text" class="form-control" id="product_name" name="name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Категория</label>
                                    <select class="form-select" id="product_category" name="category">
                                        <option value="">Не указана</option>
                                        <option value="2">Facebook</option>
                                        <option value="5">Мобильные прокси</option>
                                        <option value="10">Facebook Samofarm</option>
                                        <option value="13">Discord</option>
                                        <option value="15">Reddit</option>
                                        <option value="18">Yandex Zen</option>
                                        <option value="21">SEO - Ссылки</option>
                                        <option value="25">Skype</option>
                                        <option value="26">Instagram</option>
                                        <option value="29">Google Ads</option>
                                        <option value="30">Yandex.Direct</option>
                                        <option value="42">Google iOS</option>
                                        <option value="44">TikTok Ads</option>
                                        <option value="50">Twitter</option>
                                        <option value="51">Epic Games</option>
                                        <option value="53">Трафик/SEO</option>
                                        <option value="68">VK.com</option>
                                        <option value="75">Почта (Email)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Цена поставщика *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="product_price" name="price" step="0.01" min="0" required>
                                        <span class="input-group-text">₽</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Наша цена *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="product_our_price" name="our_price" step="0.01" min="0" required>
                                        <span class="input-group-text">₽</span>
                                    </div>
                                    <small class="text-muted">Рекомендуется +20-30% к цене поставщика</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Количество в наличии</label>
                                    <input type="number" class="form-control" id="product_stock" name="stock" min="0">
                                    <small class="text-muted">Оставьте 0 для "Под заказ"</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Внешний ID *</label>
                                    <input type="text" class="form-control" id="product_external_id" name="external_id" required>
                                    <small class="text-muted">ID товара у поставщика (например, 30354 для BuyAccs)</small>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    * Обязательные поля. После сохранения товар будет доступен в каталоге.
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" onclick="saveProduct()">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Функция для сохранения пользователя
        function saveUser() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            
            fetch('ajax/save_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Пользователь сохранен');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                    modal.hide();
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                alert('Ошибка соединения: ' + error);
            });
        }
        
        // Функция для сохранения товара (оставлена пустой, будет переопределена в products.php)
        function saveProduct() {
            // Эта функция будет переопределена в products.php
            alert('Функция saveProduct должна быть определена в конкретной странице');
        }
        
        // Функция для редактирования пользователя
        function editUser(id) {
            fetch('ajax/get_user.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('edit_user_id').value = user.id;
                        document.getElementById('edit_username').value = user.username;
                        document.getElementById('edit_email').value = user.email;
                        document.getElementById('edit_balance').value = user.balance;
                        document.getElementById('edit_is_admin').checked = user.is_admin == 1;
                        
                        // Очищаем поле пароля
                        document.getElementById('edit_password').value = '';
                        
                        new bootstrap.Modal(document.getElementById('editUserModal')).show();
                    } else {
                        alert('Ошибка загрузки данных');
                    }
                })
                .catch(error => {
                    alert('Ошибка соединения');
                });
        }
        
        // Функция для редактирования товара (оставлена пустой, будет переопределена в products.php)
        function editProduct(id) {
            // Эта функция будет переопределена в products.php
            alert('Функция editProduct должна быть определена в конкретной странице. ID: ' + id);
        }
        
        // Функция для удаления пользователя
        function deleteUser(id, name) {
            if (confirm('Вы уверены, что хотите удалить пользователя "' + name + '"?')) {
                fetch('ajax/delete_user.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Пользователь удален');
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Ошибка соединения');
                    });
            }
        }
        
        // Функция для удаления товара (оставлена пустой, будет переопределена в products.php)
        function deleteProduct(id, name) {
            // Эта функция будет переопределена в products.php
            if (confirm('Вы уверены, что хотите удалить товар "' + name + '"?')) {
                alert('Функция deleteProduct должна быть определена в конкретной странице. ID: ' + id);
            }
        }
        
        // Функция для добавления товара (оставлена пустой, будет переопределена в products.php)
        function addProduct() {
            // Эта функция будет переопределена в products.php
            alert('Функция addProduct должна быть определена в конкретной странице');
        }
        
        // Функция для безопасного экранирования HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        </script>