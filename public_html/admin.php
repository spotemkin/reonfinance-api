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

if (($act == 'login') and (isset($_POST['phone'])) and (isset($_POST['password']))) // Вход в админку
{
    $login = $_POST['phone'];

    $sql_admin = $db->prepare("SELECT id FROM users WHERE admin = 1 AND tg_login = ?");
    $sql_admin -> execute([$login]);
    if ($sql_admin->rowCount() <= 0 || $_POST['password'] != $admin_password)
    {
        $content = file_get_contents('templates/admin/login.html');
        die ($content);
    }
    $admin_id = $sql_admin->fetch();
    $token = random_string(32);
    $sql_token = $db ->prepare("INSERT INTO `tokens` (`id`, `token`, `user_id`, `ip`) VALUES (NULL, ?, ?, ?)");
    $sql_token -> execute([$token, $admin_id['id'], $_SERVER['REMOTE_ADDR']]);

    setcookie('token', $token, time()+99999999, '/', $_SERVER['SERVER_NAME']);

    header("Location: admin.php");
}

if (isset($_COOKIE['token'])) // Checking cookies
{
    $sql = $db->prepare("SELECT u.id, token
        FROM tokens
        LEFT JOIN users u ON u.id=tokens.user_id
        WHERE token = ? AND admin = 1");
    $sql->execute([$_COOKIE['token']]);
    if ($sql->rowCount() == 0) // Login form to the admin panel
    {
        $content = file_get_contents('templates/admin/login.html');
        die ($content);
    } else // Админ залогинен
    {
        $result = $sql->fetch();
        $admin['id'] = $result['id'];
        $admin['token'] = $_COOKIE['token'];
        $modules_dir = scandir('modules');
        $admin_content = file_get_contents('templates/admin/index.html');
        $admin_menu = '';
        $module = 'account';
        $current_item_name = '';
        if (isset($_GET['module']))
            $module = $_GET['module'];
        foreach ($modules_dir as $admin_item) // displaying all modules in the menu
        {
            if (strpos($admin_item, '.') === FALSE)
            {
                if (file_exists('modules/' . $admin_item . '/admin.php'))
                {
                    unset($operator_access);
                    $module_status = 1;
                    if (file_exists('modules/' . $admin_item . '/config.php')) {
                        include('modules/' . $admin_item . '/config.php');
                    }
                    else $module_name = $admin_item;
                    if ($module_status == 0)
                        continue;
                    $admin_item_s = file_get_contents('templates/admin/one_module.html');
                    if ($module == $admin_item) {
                        $admin_item_s = str_replace('class="menu_item"', 'class="menu_item current_menu_item"', $admin_item_s);
                        $current_item_name = $item_name;
                    }
                    $admin_item_s = str_replace('<module_name_title />', $module_name, $admin_item_s);
                    $admin_item_s = str_replace('<module_name />', $admin_item, $admin_item_s);
                    $admin_menu .= $admin_item_s;
                }
            }
        }
        $admin_content = str_replace('<menu />', $admin_menu, $admin_content);
        $admin_content = str_replace('<current_item_name />', $current_item_name, $admin_content);
        $admin_content = str_replace('<current_module_name />', $module, $admin_content);
        $content = '';
        $pages_offset = 0;
        $site_title = 'Главная';
        if (isset($_GET['offset'])) $pages_offset = $_GET['offset'];
        if (file_exists('modules/' . $module . '/config.php')) {
            include('modules/' . $module . '/config.php');
        }
        if (file_exists('modules/' . $module . '/functions.php'))
            include('modules/' . $module . '/functions.php');

        if (($module != '') and (file_exists('modules/' . $module . '/admin.php'))) // connecting the module
            include('modules/' . $module . '/admin.php');
        elseif ($module == '')
            $content = file_get_contents('templates/admin/main.html');

        $admin_content = str_replace('<current_module />', $content, $admin_content);
        $admin_content = str_replace("<site_title />", $site_title, $admin_content);

        echo $admin_content;
    }
}
else {
    $content = file_get_contents('templates/admin/login.html');
    $content = str_replace("<site_title />", 'Вход', $content);
    die ($content);
}

?>