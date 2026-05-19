<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once 'db.php';
require_once 'order_functions.php';

// Определяем реальный метод (эмуляция PUT через POST + _method)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}
// Для JSON-запросов читаем тело и ищем _method там
$input_json = null;
if ($method === 'POST' && empty($_POST)) {
    $input_json = json_decode(file_get_contents('php://input'), true);
    if (isset($input_json['_method'])) {
        $method = strtoupper($input_json['_method']);
    }
}

$path = $_SERVER['PATH_INFO'] ?? '';
$path = trim($path, '/');
$parts = explode('/', $path);

// Маршрутизация
if ($parts[0] === 'order') {
    // POST /order -> создание
    if ($method === 'POST' && count($parts) === 1) {
        $data = $input_json ?? $_POST;
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Неверный JSON или form-data']);
            exit;
        }
        $is_logged = isset($_SESSION['application_id']);
        $user_id = $is_logged ? $_SESSION['application_id'] : null;
        $result = createOrder($data, $is_logged, $user_id);
        if ($result['success']) {
            http_response_code(201);
            echo json_encode([
                'status' => 'ok',
                'order_id' => $result['order_id'],
                'total' => $result['total'],
                'login' => $result['generated_login'],
                'password' => $result['generated_password'],
                'profile_url' => $result['profile_url']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['errors' => $result['errors']]);
        }
    }
    // PUT /order/{id} (эмулируется через POST с _method=PUT)
    elseif (($method === 'PUT' || ($method === 'POST' && isset($input_json['_method']) && $input_json['_method'] === 'PUT')) && isset($parts[1]) && is_numeric($parts[1])) {
        if (!isset($_SESSION['application_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация']);
            exit;
        }
        $order_id = (int)$parts[1];
        $data = $input_json ?? $_POST;
        // Убираем _method из данных, если он там был
        if (isset($data['_method'])) unset($data['_method']);
        $result = updateOrder($order_id, $data, $_SESSION['application_id']);
        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['status' => 'updated', 'order_id' => $result['order_id'], 'total' => $result['total']]);
        } else {
            http_response_code(400);
            echo json_encode(['errors' => $result['errors']]);
        }
    }
    // GET /order/{id}
    elseif ($method === 'GET' && isset($parts[1]) && is_numeric($parts[1])) {
        if (!isset($_SESSION['application_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация']);
            exit;
        }
        $order_id = (int)$parts[1];
        $order = getOrderById($order_id, $_SESSION['application_id']);
        if ($order) {
            echo json_encode(['status' => 'ok', 'order' => $order]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ не найден или доступ запрещён']);
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешён для этого URL']);
    }
}
elseif ($parts[0] === 'orders' && $method === 'GET') {
    // GET /orders - список заказов текущего пользователя
    if (!isset($_SESSION['application_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Требуется авторизация']);
        exit;
    }
    $orders = getUserOrders($_SESSION['application_id']);
    echo json_encode(['status' => 'ok', 'orders' => $orders]);
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint не найден']);
}
?>