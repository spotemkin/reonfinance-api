<?php
function hi() {}
include('kernel/core.php');

$admin = array();

$act = '';
if (isset($_GET['act']))
    $act = $_GET['act'];


$id = '';
if (isset($_GET['id']))
    $id = $_GET['id'];

if (isset($_COOKIE['token'])) // Проверка кукисов
{
    $sql = $db->prepare("SELECT user_id FROM tokens LEFT JOIN users ON users.id=tokens.user_id WHERE token = ? AND admin=1");
    $sql->execute([$_COOKIE['token']]);
    if ($sql->rowCount() == 0) // Форма логина в админку
    {
        $content = file_get_contents('templates/admin/login.html');
        die ($content);
    }

    if ($sql->rowCount() > 0) // Админ залогинен
    {
        $result = $sql->fetch();
        $admin['id'] = $result['user_id'];
        $modules_dir = scandir('modules');

        $module = '';
        if (isset($_GET['module']))
            $module = $_GET['module'];
        //echo 'module = '.$module.', act = '.$act.', id = '.$id;
        if (file_exists('modules/' . $module . '/config.php'))
            include('modules/' . $module . '/config.php');
        if (file_exists('modules/' . $module . '/functions.php'))
            include('modules/' . $module . '/functions.php');

        if (($module != '') and (file_exists('modules/' . $module . '/ajax.php'))) // подключение модуля
            include('modules/' . $module . '/ajax.php');

    }
}