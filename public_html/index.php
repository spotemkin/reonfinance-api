<?php

function hi() {}

include('kernel/core.php');

$modules_dir = scandir('modules');

$api_content = file_get_contents('templates/index/index.html');
$api_menu = '';
$module = '';
if (isset($_GET['module']))
    $module = $_GET['module'];
$objects = file_get_contents('templates/index/objects.html');
$object_s = '';
foreach ($modules_dir as $menu_item) //displaying all modules in the menu
{
    $methods = '';
    $api_doc = array();
    $api_objects = array();

    if (((strpos($menu_item, '.')) === FALSE) and ((strpos($menu_item, '/')) === FALSE) and ($menu_item != '') and (file_exists('modules/' . $menu_item . '/index.php'))) // подключение модуля
    {
        include('modules/' . $menu_item . '/index.php');
        $menu_item_s = file_get_contents('templates/index/one_module.html');
        if (($menu_item == $module) and (isset($_GET['method'])))
            $menu_item_s = str_replace('class="one_module_methods"', 'class="one_module_methods" style="display: inherit;"', $menu_item_s);
        elseif (($menu_item == $module) and (isset($_GET['object'])))
            $objects = str_replace('class="one_module_methods"', 'class="one_module_methods" style="display: inherit;"', $objects);
        foreach (array_keys($api_doc) as $method) {
            $methods .= file_get_contents('templates/index/method.html');
            if ((isset($_GET['method'])) and ($method == $_GET['method']))
            {
                $methods = str_replace('><method_name />', '><b><method_name /></b>', $methods);
            }
            $methods = str_replace('<method_name />', $method, $methods);
        }
        foreach (array_keys($api_objects) as $object) {
            $object_s .= file_get_contents('templates/index/object.html');
            if ((isset($_GET['object'])) and ($object == $_GET['object']))
            {
                $object_s = str_replace('><object_name />', '><b><object_name /></b>', $object_s);
            }
            $object_s = str_replace('<object_name />', $object, $object_s);
            $object_s = str_replace('<module_name />', $menu_item, $object_s);
        }
        $api_menu .= $menu_item_s;
        $api_menu = str_replace('<methods />', $methods, $api_menu);
        if ($module == $menu_item)
            $api_menu = str_replace('<module_name />', $menu_item, $api_menu);
        $api_menu = str_replace('<module_name />', $menu_item, $api_menu);
    }
}
$api_content = str_replace('<menu />', $api_menu, $api_content);
$api_content = str_replace('<objects />', $objects, $api_content);
$api_content = str_replace('<objects />', $object_s, $api_content);

$api_doc = array();

if ((isset($_GET['method'])) and ($module != '')) {
    $content = file_get_contents('templates/index/current_module.html');

    if (((strpos($module, '.')) === FALSE) and ((strpos($module, '/')) === FALSE) and ($module != '') and (file_exists('modules/' . $module . '/index.php'))) // подключение модуля
        include('modules/' . $module . '/index.php');

    $method = $_GET['method'];

    $api_content = str_replace('<site_title />', $module.'.'.$method.' - '.$site_title, $api_content);

    $content = str_replace('<module />', $module, $content);
    $content = str_replace('<method />', $method, $content);

    if (!array_key_exists($method, $api_doc)) {
        header('Location: index.php');
    }
    $content = str_replace('<method_discription />', $api_doc[$method]['description'], $content);
    $params = '';
    $form = '';
    if (!array_key_exists('params', $api_doc[$method]))
    {
        $params = 'Отсутствуют';
        $content = str_replace('<no_params />', 'display: none', $content);
    }
    else
        foreach (array_keys($api_doc[$method]['params']) as $param) {
            $params .= file_get_contents('templates/index/param.html');
            $params = str_replace('<param_name />', $param, $params);
            $params = str_replace('<param_description />', $api_doc[$method]['params'][$param]['description'], $params);
            if (($api_doc[$method]['params'][$param]['type'] == 'text') or ($api_doc[$method]['params'][$param]['type'] == 'select'))
                $params = str_replace('<param_type />', 'Строка', $params);
            elseif ($api_doc[$method]['params'][$param]['type'] == 'file')
                $params = str_replace('<param_type />', "Файл", $params);
            elseif ($api_doc[$method]['params'][$param]['type'] == 'int')
                $params = str_replace('<param_type />', "Число", $params);
            else
                $params = str_replace('<param_type />', $api_doc[$method]['params'][$param]['type'], $params);
            if ($api_doc[$method]['params'][$param]['needed'] == 1)
                $params = str_replace('<param_needed />', 'Да', $params);
            else
                $params = str_replace('<param_needed />', 'Нет', $params);

            $form .= $test_forms[$api_doc[$method]['params'][$param]['type']];
            $form = str_replace('<name />', $param, $form);
            if (($param == 'token') and (isset($_COOKIE['token'])))
                $form = str_replace('name="token"', 'name="token" value="'.$_COOKIE['token'].'"', $form);
            if ($api_doc[$method]['params'][$param]['needed'] == 1)
                $form = str_replace('<required />', 'required', $form);
            else
                $form = str_replace('<required />', '', $form);
            if ($api_doc[$method]['params'][$param]['type'] == 'select') {
                $options = '';
                foreach (array_keys($api_doc[$method]['params'][$param]['params']) as $option) {
                    $options .= $test_forms['option'];
                    $options = str_replace('<option_name />', $api_doc[$method]['params'][$param]['params'][$option], $options);
                }
                $form = str_replace('<options />', $options, $form);
            }
            else
                $form = str_replace('<type />', $api_doc[$method]['params'][$param]['type'], $form);
        }
    $content = str_replace('<params />', $params, $content);
    $content = str_replace('<test_form />', $form, $content);
    if (!array_key_exists('result', $api_doc[$method]))
        $api_doc[$method]['result'] = 'Отсутствует';
    $content = str_replace('<result />', $api_doc[$method]['result'], $content);
    $errors = '';
    if (!array_key_exists('errors', $api_doc[$method]))
        $errors = 'Отсутствуют';
    else
        foreach (array_keys($api_doc[$method]['errors']) as $error) {
            $errors .= file_get_contents('templates/index/error.html');
            $errors = str_replace('<error_name />', $error, $errors);
            $errors = str_replace('<error_description />', $api_doc[$method]['errors'][$error], $errors);
        }
    $content = str_replace('<errors />', $errors, $content);

}
elseif ((isset($_GET['object'])) and ($module != ''))
{
    $content = file_get_contents('templates/index/current_object.html');

    if (((strpos($module, '.')) === FALSE) and ((strpos($module, '/')) === FALSE) and ($module != '') and (file_exists('modules/' . $module . '/index.php'))) // подключение модуля
        include('modules/' . $module . '/index.php');
    $object = $_GET['object'];
    $api_content = str_replace('<site_title />', $module.'.'.$object.' - '.$site_title, $api_content);
    $content = str_replace('<object />',   'object.' . $object, $content);
    if (!array_key_exists($object, $api_objects)) {
        header('Location: index.php');
    }
    $content = str_replace('<object_discription />', $api_objects[$object]['description'], $content);
    $params = '';
    if (!array_key_exists('params', $api_objects[$object]))
    {
        $params = 'Отсутствует';
        $content = str_replace('<no_params />', 'display: none', $content);
    }
    else
        foreach (array_keys($api_objects[$object]['params']) as $param) {
            $params .= file_get_contents('templates/index/param.html');
            $params = str_replace('<param_name />', $param, $params);
            $params = str_replace('<param_description />', $api_objects[$object]['params'][$param]['description'], $params);
            if (($api_objects[$object]['params'][$param]['type'] == 'text') or ($api_objects[$object]['params'][$param]['type'] == 'select'))
                $params = str_replace('<param_type />', 'Строка', $params);
            elseif ($api_objects[$object]['params'][$param]['type'] == 'file')
                $params = str_replace('<param_type />', "Файл", $params);
            elseif ($api_objects[$object]['params'][$param]['type'] == 'int')
                $params = str_replace('<param_type />', "Число", $params);
            else
                $params = str_replace('<param_type />', $api_doc[$method]['params'][$param]['type'], $params);
            $params = str_replace('<td><param_needed /></td>', '', $params);
        }
    $content = str_replace('<params />', $params, $content);
}
else
{
    $api_content = str_replace('Главная', '<b>Главная</b>', $api_content);
    $api_content = str_replace('<site_title />', $site_title, $api_content);
    $content = file_get_contents('templates/index/main.html');
    $content = str_replace('<site_name_long />', 'https://'.$_SERVER['SERVER_NAME'].'/api/[название модуля]/[название метода]', $content);
    $content = str_replace('<site_name />', preg_replace('/^(api.)/', '', $_SERVER['SERVER_NAME']), $content);
}

$api_content = str_replace('<current_module />', $content, $api_content);


echo $api_content;


?>