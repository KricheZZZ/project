<?php
session_start();
if (!isset($_SESSION['application_id'])) {
    header('Location: login.php');
    exit();
}
// Можно вывести информацию о пользователе, но для простоты перенаправим на главную
header('Location: index.php');
exit();