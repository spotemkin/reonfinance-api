<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: ".$_SERVER['HTTP_REFERER']);
    else
        header("Location: admin.php");

switch ($act) {

    case "edit":

        if (!isset($_GET['id']) ){
            echo 'error';
            break;
        }

        $params = array();
        $params_list = '';
        foreach (array_keys($_POST) as $post){
            if ($post != 'id' && $post != 'admin')
            {
                array_push($params, ($_POST[$post]));
                $params_list .= $post . '=?,';
            }
        }
        //die($params_list.'111'.implode($params));
        array_push($params, $_GET['id']);
        if ($params_list != ''){
            $params_list = substr($params_list, 0, strlen($params_list) - 1);
            $sql_edit = $db->prepare("UPDATE users SET $params_list WHERE id = ?");
            $sql_edit->execute($params);
        }

        if ($_POST['status'] == 'active'){
            activate($_GET['id']);
        }

        if (isset($_POST['admin']) and $_POST['admin'] == 'on'){
            $sql = $db->prepare("UPDATE users SET admin = 1 WHERE id = ?");
            $sql->execute(array($_GET['id']));
        }
        else{
            $sql = $db->prepare("UPDATE users SET admin = 0 WHERE id = ?");
            $sql->execute(array($_GET['id']));
        }

        print_r($_POST);

        break;

    case "delete":

        if ($id == '')
            break;

        if (!\Account\delete_user($id, $admin['id'])) {
            echo 'error';
            break;
        }

        echo $id;
        break;

    case 'delete_token':

        if ($id == '')
            break;

        $sql_user = $db -> prepare("DELETE FROM tokens WHERE id=?");
        $sql_user->execute([$id]);
        echo $id;
        break;

    case "edit_task":

        if (!isset($_GET['id']) ){
            echo 'error';
            break;
        }

        if (!isset($_POST['task_id']) || !isset($_POST['value'])){
            echo 'error';
            break;
        }

        $sql = $db->prepare("INSERT INTO tasks_roles SET user_id = ?, task_id = ?, status = ?
            ON DUPLICATE KEY UPDATE status = VALUES(status)");
        $sql->execute([$_GET['id'], $_POST['task_id'], $_POST['value']]);

        print_r($_POST);
//        print_r($_GET);

//        print_r($sql->errorInfo());

        break;

    default:

        echo 'not such method';

        break;

}
