<?php
session_start();
$is_logged_in = isset($_SESSION['application_id']);
$user_id = $is_logged_in ? $_SESSION['application_id'] : null;

// Если это API-запрос (параметр route)
if (isset($_GET['route'])) {
    header('Content-Type: application/json; charset=UTF-8');
    require_once 'db.php';
    require_once 'order_functions.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    $route = $_GET['route'];
    
    // Эмуляция PUT через POST + _method
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }
    $input_json = null;
    if ($method === 'POST' && empty($_POST)) {
        $input_json = json_decode(file_get_contents('php://input'), true);
        if (isset($input_json['_method'])) {
            $method = strtoupper($input_json['_method']);
            unset($input_json['_method']);
        }
    }
    
    if ($route === 'order') {
        // POST /?route=order - создание заказа
        if ($method === 'POST') {
            $data = $input_json ?? $_POST;
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Нет данных']);
                exit;
            }
            $result = createOrder($data, $is_logged_in, $user_id);
            if ($result['success']) {
                http_response_code(201);
                echo json_encode([
                    'status' => 'ok',
                    'order_id' => $result['order_id'],
                    'total' => $result['total'],
                    'login' => $result['generated_login'] ?? null,
                    'password' => $result['generated_password'] ?? null,
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['errors' => $result['errors']]);
            }
        }
        // PUT /?route=order&id=123 - обновление
        elseif (($method === 'PUT' || ($method === 'POST' && isset($_GET['_method']))) && isset($_GET['id'])) {
            if (!$is_logged_in) {
                http_response_code(401);
                echo json_encode(['error' => 'Требуется авторизация']);
                exit;
            }
            $order_id = (int)$_GET['id'];
            $data = $input_json ?? $_POST;
            $result = updateOrder($order_id, $data, $user_id);
            if ($result['success']) {
                echo json_encode(['status' => 'updated', 'order_id' => $result['order_id'], 'total' => $result['total']]);
            } else {
                http_response_code(400);
                echo json_encode(['errors' => $result['errors']]);
            }
        }
        // GET /?route=order&id=123 - получение заказа
        elseif ($method === 'GET' && isset($_GET['id'])) {
            if (!$is_logged_in) {
                http_response_code(401);
                echo json_encode(['error' => 'Требуется авторизация']);
                exit;
            }
            $order_id = (int)$_GET['id'];
            $order = getOrderById($order_id, $user_id);
            if ($order) {
                echo json_encode(['status' => 'ok', 'order' => $order]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Заказ не найден']);
            }
        }
        else {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не разрешён']);
        }
    }
    elseif ($route === 'orders' && $method === 'GET') {
        if (!$is_logged_in) {
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация']);
            exit;
        }
        $orders = getUserOrders($user_id);
        echo json_encode(['status' => 'ok', 'orders' => $orders]);
    }
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint не найден']);
    }
    exit;
}?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" href="https://img.icons8.com/color/96/000000/kebab.png" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дёнер "Королевский" | Лучшая шаурма в городе</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>
<!-- ========== HEADER ========== -->
<header>
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="https://assets.mixkit.co/videos/preview/mixkit-fire-in-a-barbecue-4888-large.mp4" type="video/mp4">
            Ваш браузер не поддерживает видео.
        </video>
        <div class="overlay"></div>
    </div>
    
    <nav>
        <a href="#" class="logo"><i class="fas fa-utensils"></i> Дёнер<span>Королевский</span></a>
        <ul class="nav-links">
            <li><a href="#"><i class="fas fa-home"></i> Главная</a></li>
            <li><a href="#menu"><i class="fas fa-hamburger"></i> Меню</a></li>
            <li><a href="#calculator"><i class="fas fa-calculator"></i> Калькулятор</a></li>
            <li><a href="#gallery"><i class="fas fa-images"></i> Галерея</a></li>
            <li><a href="#contact"><i class="fas fa-address-book"></i> Заказ</a></li>
            <li><a href="#" class="btn contact-btn"><i class="fas fa-phone-alt"></i> Заказать</a></li>
        </ul>
        <div class="burger" id="burgerBtn">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </nav>
    
    <div class="hero">
        <h1>Дёнер "Королевский"</h1>
        <p>Настоящая шаурма по королевскому рецепту! Сочное мясо, свежие овощи и фирменные соусы. Приготовлено на открытом огне.</p>
        <a href="#menu" class="btn">Выбрать шаурму</a>
    </div>
</header>

<!-- ========== МЕНЮ ========== -->
<section id="menu" class="section">
    <div class="section-title">
        <h2>Королевское меню</h2>
        <p>Выберите свою идеальную шаурму с нашими свежими ингредиентами</p>
    </div>
    
    <div class="models-grid">
        <div class="model-card">
            <div class="model-img">
                <img src="https://i.pinimg.com/originals/cf/dd/2e/cfdd2e941e766c51fa6113c1c17f3b81.jpg" alt="Классическая шаурма">
            </div>
            <div class="model-info">
                <h3>Классическая шаурма</h3>
                <p>Сочная курица, свежие овощи, лаваш и фирменный соус. Классика жанра!</p>
                <div class="model-price">от 250 ₽</div>
                <div class="ingredients-picker">
                    <h4>Добавить ингредиенты:</h4>
                    <div class="ingredients-options">
                        <div class="ingredient-option active" data-product="1">Курица</div>
                        <div class="ingredient-option" data-product="1">Говядина</div>
                        <div class="ingredient-option" data-product="1">Свинина</div>
                        <div class="ingredient-option" data-product="1">Сыр +50₽</div>
                        <div class="ingredient-option" data-product="1">Грибы +30₽</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="model-card">
            <div class="model-img">
                <img src="https://static.tildacdn.com/stor6336-3463-4565-b063-653566633463/38836220.jpg" alt="Острая шаурма">
            </div>
            <div class="model-info">
                <h3>Острая шаурма</h3>
                <p>Для любителей поострее! Специи, острый перец и аджика по-кавказски.</p>
                <div class="model-price">от 280 ₽</div>
                <div class="ingredients-picker">
                    <h4>Выберите остроту:</h4>
                    <div class="ingredients-options">
                        <div class="ingredient-option active" data-product="2">Средняя 🌶️</div>
                        <div class="ingredient-option" data-product="2">Острая 🌶️🌶️</div>
                        <div class="ingredient-option" data-product="2">Очень острая 🌶️🌶️🌶️</div>
                        <div class="ingredient-option" data-product="2">Двойное мясо +100₽</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="model-card">
            <div class="model-img">
                <img src="https://cafehabibi.ru/d/vegan.jpg" alt="Вегетарианская шаурма">
            </div>
            <div class="model-info">
                <h3>Вегетарианская шаурма</h3>
                <p>Свежие овощи, грибы, сыр и соус песто. Без мяса, но очень вкусно!</p>
                <div class="model-price">от 220 ₽</div>
                <div class="ingredients-picker">
                    <h4>Выберите основу:</h4>
                    <div class="ingredients-options">
                        <div class="ingredient-option active" data-product="3">Овощная</div>
                        <div class="ingredient-option" data-product="3">С грибами</div>
                        <div class="ingredient-option" data-product="3">С сыром +50₽</div>
                        <div class="ingredient-option" data-product="3">Фалафель +70₽</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== ТАБЛИЦА СРАВНЕНИЯ ========== -->
<section class="performance-models section">
    <div class="section-title">
        <h2>Сравнение наших позиций</h2>
        <p>Калорийность и состав наших самых популярных позиций</p>
    </div>
    
    <div class="table-container">
        <table class="performance-table">
            <thead>
                <tr>
                    <th>Позиция</th>
                    <th>Вес</th>
                    <th>Калории</th>
                    <th>Основной ингредиент</th>
                    <th>Время приготовления</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Классическая шаурма</td><td>350 г</td><td>450 ккал</td><td>Курица</td><td>5-7 мин</td></tr>
                <tr><td>Острая шаурма</td><td>380 г</td><td>520 ккал</td><td>Говядина</td><td>6-8 мин</td></tr>
                <tr><td>Вегетарианская</td><td>320 г</td><td>380 ккал</td><td>Овощи</td><td>4-6 мин</td></tr>
                <tr><td>Дёнер в лаваше</td><td>400 г</td><td>550 ккал</td><td>Смешанное мясо</td><td>7-9 мин</td></tr>
                <tr><td>Дёнер в лепёшке</td><td>420 г</td><td>580 ккал</td><td>Баранина</td><td>8-10 мин</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- ========== ГАЛЕРЕЯ ========== -->
<section id="gallery" class="gallery-section section">
    <div class="section-title">
        <h2>Наша шаурмечная</h2>
        <p>Загляните на нашу кухню и почувствуйте атмосферу вкуса</p>
    </div>
    
    <div class="gallery-container">
        <div class="gallery-slider" id="gallerySlider">
            <div class="gallery-slide active">
                <img src="https://avatars.mds.yandex.net/i?id=d202eda8eaa2900ea519fb7ca66a8007_l-5276461-images-thumbs&n=13" alt="Кухня">
                <div class="slide-content">
                    <h3>Наша чистая кухня</h3>
                    <p>Всегда свежие ингредиенты и строгое соблюдение санитарных норм.</p>
                </div>
            </div>
            <div class="gallery-slide">
                <img src="https://cast.kz/img/Post/_%D0%B2%D0%BA%20%D0%BF%D0%BE%D0%B2%D0%B0%D1%80.jpg" alt="Приготовление">
                <div class="slide-content">
                    <h3>Мастер-шаурмист за работой</h3>
                    <p>Наши повара готовят каждую шаурму с любовью и вниманием к деталям.</p>
                </div>
            </div>
            <div class="gallery-slide">
                <img src="https://arh-predmet.by/wp-content/uploads/2024/12/7.webp" alt="Интерьер">
                <div class="slide-content">
                    <h3>Уютный интерьер</h3>
                    <p>Комфортная атмосфера для тех, кто предпочитает есть на месте.</p>
                </div>
            </div>
        </div>
        
        <div class="gallery-controls">
            <button class="gallery-btn prev-btn" id="prevBtn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="gallery-btn next-btn" id="nextBtn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="gallery-dots" id="galleryDots">
            <span class="gallery-dot active" data-slide="0"></span>
            <span class="gallery-dot" data-slide="1"></span>
            <span class="gallery-dot" data-slide="2"></span>
        </div>
    </div>
</section>

<!-- ========== КАЛЬКУЛЯТОР СТОИМОСТИ ========== -->
<section id="calculator" class="section">
    <div class="section-title">
        <h2>Калькулятор заказа</h2>
        <p>Рассчитайте стоимость вашего заказа с учётом всех дополнений</p>
    </div>
    
    <div class="calculator">
        <form class="calculator-form" id="price-calculator">
            <div class="form-group">
                <label for="product">Тип шаурмы</label>
                <select id="product" name="product">
                    <option value="250">Классическая (250 ₽)</option>
                    <option value="280">Острая (280 ₽)</option>
                    <option value="220">Вегетарианская (220 ₽)</option>
                    <option value="350">Дёнер премиум (350 ₽)</option>
                    <option value="300">Дёнер в лепёшке (300 ₽)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantity">Количество: <span id="quantityValue">2</span> шт.</label>
                <input type="range" id="quantity" name="quantity" min="1" max="10" value="2">
            </div>
            
            <div class="form-group">
                <label for="delivery">Доставка</label>
                <select id="delivery" name="delivery">
                    <option value="0">Самовывоз (бесплатно)</option>
                    <option value="150">По району (150 ₽)</option>
                    <option value="250">По городу (250 ₽)</option>
                    <option value="400">Срочная доставка (400 ₽)</option>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label>Дополнительно</label>
                <div class="options-group">
                    <div class="option-checkbox">
                        <input type="checkbox" id="cheese" name="cheese" value="50">
                        <label for="cheese">Доп. сыр (+50 ₽)</label>
                    </div>
                    <div class="option-checkbox">
                        <input type="checkbox" id="sauce" name="sauce" value="30">
                        <label for="sauce">Доп. соус (+30 ₽)</label>
                    </div>
                    <div class="option-checkbox">
                        <input type="checkbox" id="meat" name="meat" value="100">
                        <label for="meat">Двойное мясо (+100 ₽)</label>
                    </div>
                    <div class="option-checkbox">
                        <input type="checkbox" id="set" name="set" value="150">
                        <label for="set">Комбо (напиток+картошка) (+150 ₽)</label>
                    </div>
                </div>
            </div>
            
            <div class="calculator-result">
                <h3>Итоговая стоимость</h3>
                <div class="total-price" id="total-price">500 ₽</div>
                <p class="hint">(2 шт. классической × 250 ₽ + доставка 0 ₽)</p>
            </div>
        </form>
    </div>
</section>

<!-- ========== КОНТАКТНАЯ ФОРМА ========== -->
<section id="contact" class="section">
    <div class="section-title">
        <h2>Оформить заказ</h2>
        <p>Заполните форму, и мы приготовим для вас самую вкусную шаурму</p>
    </div>

    <div id="credentials-block" class="credentials-block" style="display: none;"></div>

    <form id="order-form" class="contact-form">
        <div class="form-group">
            <label for="full_name">Ваше имя *</label>
            <input type="text" id="full_name" name="full_name" required minlength="2" placeholder="Иван Петров">
        </div>
        <div class="form-group">
            <label for="phone">Телефон *</label>
            <input type="tel" id="phone" name="phone" required placeholder="+7 (123) 456-78-90">
        </div>
        <div class="form-group">
            <label for="email">Электронная почта *</label>
            <input type="email" id="email" name="email" required placeholder="example@mail.ru">
        </div>
        <div class="form-group">
            <label for="address">Адрес доставки *</label>
            <input type="text" id="address" name="address" required placeholder="г. Москва, ул. Примерная, д.1">
        </div>
        <div class="form-group">
            <label for="message">Пожелания к заказу</label>
            <textarea id="message" name="message" rows="3" placeholder="Например: без лука, острый соус отдельно..."></textarea>
        </div>

        <input type="hidden" id="edit_order_id" value="">
        <button type="submit" class="btn" id="submit-order">Отправить заказ</button>
        <div id="form-status" class="form-message"></div>
    </form>

    <?php if ($is_logged_in): ?>
    <div class="my-orders">
        <h3>Мои заказы</h3>
        <div id="orders-list" class="orders-list">
            <!-- Список заказов загружается через JS -->
            <p>Загрузка...</p>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- ========== FOOTER ========== -->
<footer>
    <div class="footer-content">
        <div class="footer-logo"><i class="fas fa-utensils"></i> Дёнер<span>Королевский</span></div>
        <ul class="footer-links">
            <li><a href="#">Главная</a></li>
            <li><a href="#menu">Меню</a></li>
            <li><a href="#calculator">Калькулятор</a></li>
            <li><a href="#gallery">Галерея</a></li>
            <li><a href="#contact">Заказ</a></li>
        </ul>
        <div class="quote-section">
            <p class="inspiration-quote">
                "Лучшая шаурма в городе! Сочное мясо, свежие овощи и идеальные соусы. Рекомендую!"
            </p>
        </div>
        <div class="social-links">
            <a href="#"><i class="fab fa-vk"></i></a>
            <a href="#"><i class="fab fa-telegram"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
        <div class="copyright">
            <p><i class="fas fa-clock"></i> Ежедневно с 10:00 до 23:00</p>
            <p>© 2024 Дёнер "Королевский". Все права защищены.</p>
        </div>
    </div>
</footer>

<!-- ========== MODAL ========== -->
<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal" id="contact-modal">
    <div class="modal-content">
        <span class="close-modal" id="closeModal">&times;</span>
        <h2>Быстрый заказ</h2>
        <!-- ФОРМА ИЗ ПЕРВОГО КОДА ДЛЯ МОДАЛЬНОГО ОКНА -->
        <form id="modal-contact-form" action="https://formcarry.com/s/YhinEAy4WWS" method="POST" accept-charset="UTF-8">
            <input type="hidden" name="_gotcha" style="display:none !important">
            <div class="form-group">
                <label for="modal-name">Ваше имя *</label>
                <input type="text" id="modal-name" name="name" required minlength="2">
            </div>
            <div class="form-group">
                <label for="modal-email">Электронная почта *</label>
                <input type="email" id="modal-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="modal-message">Ваш заказ *</label>
                <textarea id="modal-message" name="message" rows="4" required minlength="10" placeholder="Что вы хотите заказать?"></textarea>
            </div>
            <button type="submit" class="btn">Заказать сейчас</button>
            <div class="form-message" id="modal-form-message"></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Шаурмечная "Дёнер Королевский" загружена!');
    
  
    
   
    
  
    
const orderForm = document.getElementById('order-form');
    const statusDiv = document.getElementById('form-status');
    const credBlock = document.getElementById('credentials-block');
    const editOrderIdField = document.getElementById('edit_order_id');
    
    function buildOrderItems() {
        const productSelect = document.getElementById('product');
        const quantity = parseInt(document.getElementById('quantity').value);
        const cheese = document.getElementById('cheese').checked;
        const sauce = document.getElementById('sauce').checked;
        const meat = document.getElementById('meat').checked;
        const setOpt = document.getElementById('set').checked;

        const productMap = {
            'Классическая': 1, 'Острая': 2, 'Вегетарианская': 3,
            'Дёнер премиум': 4, 'Дёнер в лепёшке': 5
        };
        const productText = productSelect.options[productSelect.selectedIndex].text.split(' (')[0];
        const productId = productMap[productText];

        const options = {};
        if (cheese) options.cheese = true;
        if (sauce) options.sauce = true;
        if (meat) options.meat = true;
        if (setOpt) options.set = true;

        return [{ product_id: productId, quantity: quantity, options: options }];
    }
    
    async function submitOrder(isEdit, orderId) {
        const full_name = document.getElementById('full_name').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const address = document.getElementById('address').value.trim();
        const message = document.getElementById('message').value.trim();
        const items = buildOrderItems();

        const body = { full_name, phone, email, address, message, items };


        const url = isEdit ? `/index.php?route=order&id=${orderId}` : '/index.php?route=order';
        if (isEdit) body._method = 'PUT';

       
        const method = 'POST'; // всегда POST, PUT эмулируется через _method

        statusDiv.innerHTML = '⏳ Отправка...';
        statusDiv.className = 'form-message sending';
        document.getElementById('submit-order').disabled = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const result = await response.json();
            if (response.ok && (result.status === 'ok' || result.status === 'updated')) {
                statusDiv.innerHTML = isEdit ? '✅ Заказ обновлён!' : '✅ Заказ принят!';
                statusDiv.className = 'form-message success';
                if (!isEdit && result.login && result.password) {
                    credBlock.innerHTML = `
                        <h3>Ваши данные для входа</h3>
                        <p><strong>Логин:</strong> ${escapeHtml(result.login)}</p>
                        <p><strong>Пароль:</strong> ${escapeHtml(result.password)}</p>
                        <p><a href="/login.php">Войти</a> для редактирования заказа.</p>
                    `;
                    credBlock.style.display = 'block';
                } else {
                    credBlock.style.display = 'none';
                }
                orderForm.reset();
                document.getElementById('quantity').value = 2;
                document.getElementById('quantityValue').innerText = '2';
                document.getElementById('total-price').innerText = '500 ₽';
                editOrderIdField.value = '';
                if (<?= json_encode($is_logged_in) ?>) loadUserOrders();
            } else {
                let errorMsg = 'Ошибка: ';
                if (result.errors) errorMsg += Object.values(result.errors).join(' ');
                else if (result.error) errorMsg += result.error;
                else errorMsg += 'Неизвестная ошибка';
                statusDiv.innerHTML = '❌ ' + errorMsg;
                statusDiv.className = 'form-message error';
            }
        } catch (err) {
            statusDiv.innerHTML = '❌ Ошибка сети. Попробуйте позже.';
            statusDiv.className = 'form-message error';
        } finally {
            document.getElementById('submit-order').disabled = false;
            setTimeout(() => {
                if (statusDiv.className !== 'form-message error')
                    statusDiv.innerHTML = '';
            }, 5000);
        }
    }
    
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const editId = editOrderIdField.value;
        if (editId) submitOrder(true, editId);
        else submitOrder(false, null);
    });
    
    function escapeHtml(str) { 
         if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    <?php if ($is_logged_in): ?>
    async function loadUserOrders() {
        const container = document.getElementById('orders-list');
        try {
            const response = await fetch('/index.php?route=orders');
            const data = await response.json();
            if (data.status === 'ok' && data.orders.length) {
                let html = '<ul>';
                data.orders.forEach(order => {
                    html += `<li>
                        Заказ №${order.id} от ${new Date(order.created_at).toLocaleString()}, сумма: ${order.total_price} ₽
                        <button class="edit-order-btn" data-id="${order.id}">Редактировать</button>
                    </li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
                document.querySelectorAll('.edit-order-btn').forEach(btn => {
                    btn.addEventListener('click', () => loadOrderForEdit(btn.dataset.id));
                });
            } else {
                container.innerHTML = '<p>У вас пока нет заказов.</p>';
            }
        } catch(e) {
            container.innerHTML = '<p>Ошибка загрузки заказов.</p>';
        }
    }
    
    async function loadOrderForEdit(orderId) {
        try {
            const response = await fetch('/api.php?route=orders');
            const data = await response.json();
            if (data.status === 'ok') {
                const order = data.order;
                document.getElementById('full_name').value = order.full_name;
                document.getElementById('phone').value = order.phone;
                document.getElementById('email').value = order.email;
                document.getElementById('address').value = order.address;
                document.getElementById('message').value = order.message || '';
                if (order.items && order.items.length) {
                    const item = order.items[0];
                    // Установить select продукта по product_id
                    const productMapInv = {1:'Классическая',2:'Острая',3:'Вегетарианская',4:'Дёнер премиум',5:'Дёнер в лепёшке'};
                    const productName = productMapInv[item.product_id];
                    const select = document.getElementById('product');
                    for (let i=0; i<select.options.length; i++) {
                        if (select.options[i].text.startsWith(productName)) {
                            select.selectedIndex = i;
                            break;
                        }
                    }
                    document.getElementById('quantity').value = item.quantity;
                    document.getElementById('quantityValue').innerText = item.quantity;
                    const opts = item.options;
                    document.getElementById('cheese').checked = !!opts.cheese;
                    document.getElementById('sauce').checked = !!opts.sauce;
                    document.getElementById('meat').checked = !!opts.meat;
                    document.getElementById('set').checked = !!opts.set;
                    // пересчитать стоимость
                    const event = new Event('change');
                    document.getElementById('product').dispatchEvent(event);
                    document.getElementById('quantity').dispatchEvent(event);
                }
                editOrderIdField.value = orderId;
                document.getElementById('submit-order').innerText = 'Обновить заказ';
                window.scrollTo({ top: document.getElementById('contact').offsetTop - 80, behavior: 'smooth' });
            }
        } catch(e) {}
    }
    
    loadUserOrders();
    <?php endif; ?>










    
   const contactBtn = document.querySelector('.contact-btn');
const modalOverlay = document.getElementById('modalOverlay');
const contactModal = document.getElementById('contact-modal');
const closeModalBtn = document.getElementById('closeModal');

if (contactBtn && modalOverlay && contactModal) {
    contactBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modalOverlay.classList.add('active');
        contactModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    const closeModal = () => {
        modalOverlay.classList.remove('active');
        contactModal.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && contactModal.classList.contains('active')) closeModal();
    });
}
    
    // ========== ПЛАВНАЯ ПРОКРУТКА ==========
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#' || href.includes('javascript')) return;
            
            const targetElement = document.querySelector(href);
            if (targetElement) {
                e.preventDefault();
                
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // ========== ОБНОВЛЕНИЕ ГОДА ==========
    const yearElements = document.querySelectorAll('.copyright');
    yearElements.forEach(el => {
        if (el.textContent.includes('2024')) {
            const currentYear = new Date().getFullYear();
            el.textContent = el.textContent.replace('2024', currentYear);
        }
    });
    
    // ========== АВТОСЛАЙДЕР ==========
    let autoSlideInterval = setInterval(nextSlide, 5000);
    
    if (slider) {
        slider.addEventListener('mouseenter', () => {
            clearInterval(autoSlideInterval);
        });
        
        slider.addEventListener('mouseleave', () => {
            autoSlideInterval = setInterval(nextSlide, 5000);
        });
    }
    
    updateSlider();
});


</script>
<script src="calculator.js"></script>
<script src="gallery.js"></script>
<script src="ingridient.js"></script>
<script src="mobileMenu.js"></script>
</body>
</html>
