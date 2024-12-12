<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: " . $_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");

switch ($act) {

    case '':

        $content = file_get_contents('modules/account/templates/index.html');

        $mail_search = $_GET['mail_search'] ?? ($_POST['mail_search'] ?? '');
        $tg_search = $_GET['tg_search'] ?? ($_POST['tg_search'] ?? '');

        $name_search = '';
        if (isset($_POST['name_search'])) $name_search = $_POST['name_search'];
        if (isset($_GET['name_search'])) $name_search = $_GET['name_search'];

        $type_search = '';
        if (isset($_POST['type_search'])) $type_search = $_POST['type_search'];
        if (isset($_GET['type_search'])) $type_search = $_GET['type_search'];

        $str = '';
//        $order = '';
//        $coach_search = "IF(coach = 1, 0, '-')";
        if ($type_search == '1'){
            $str = " AND (status = 'apply') ";
        }
        if ($type_search == '2'){
            $str = " AND (status = 'not_active') ";
        }
//        if ($type_search == '3'){
//            $str = " AND (status > 999 OR token IS NULL) ";
//        }

        $ts = '%' . $tg_search . '%';
        $ns = '%' . $name_search . '%';
        $ms = '%' . $mail_search . '%';
        $off = $limit_of_pages * $pages_offset;

        $sql_users = $db->prepare("SELECT u.id, u.tg_login,
                u.name, u.mail, status, last_act_date, registration_date
            FROM users u
            WHERE name LIKE :ns AND tg_login LIKE :ts AND 
                mail LIKE :ms $str
            ORDER BY last_act_date DESC
            LIMIT :lim OFFSET :off ");
        $sql_users->bindParam(":ts", $ts, 2);
        $sql_users->bindParam(":ns", $ns, 2);
        $sql_users->bindParam(":ms", $ms, 2);
        $sql_users->bindParam(":lim", $limit_of_pages, 1);
        $sql_users->bindParam(":off", $off, 1);
        $sql_users->execute();
        $users = '';

//        echo '<pre>';
//        print_r($sql_users);
//        echo '</pre>';

        if ($sql_users->rowCount() > 0) {
            for ($i = 0;
                 $i < $sql_users->rowCount();
                 $i++) {
                $sql_user = $sql_users->fetch(2);
                $user = file_get_contents("modules/account/templates/user.html");

                foreach (array_keys($sql_user) as $key_user) {
                    if (in_array($key_user, array('apple', 'google', 'telegram'))){
                        if ($sql_user[$key_user] == 0)
                            $user = str_replace("<user_" . $key_user . " />", '', $user);
                        else
                            $user = str_replace("<user_" . $key_user . " />", '<i class="fa fa-check" aria-hidden="true"></i>', $user);
                    }
                    else
                        $user = str_replace("<user_" . $key_user . " />", htmlspecialchars($sql_user[$key_user]), $user);
                }

                $users .= $user;
            }

            $content = str_replace("<pages />",
                page_menu('admin.php?module=account'.
                '&tg_search=' . $tg_search .
                '&mail_search=' . $mail_search .
                '&name_search=' . $name_search .
                '&type_search=' . $type_search .
                '&offset=',
                "    SELECT u.id, t.id as token 
                            FROM users u
                            LEFT OUTER JOIN tokens t on u.id = t.user_id
                            WHERE (tg_login LIKE ?) AND (name LIKE ?) AND (mail LIKE ?) $str 
                            ORDER BY u.last_act_date DESC",
                    [$ts, $ns, $ms]),
                $content);
            $content = str_replace("<users />", $users, $content);
        } else {
            $content = str_replace("<pages />", '', $content);
            $content = str_replace("<users />", '', $content);
        }

        $sql = $db->prepare("SELECT count(id) as users_count FROM users");
        $sql->execute([]);
        $users_count = $sql->fetch(2)['users_count'];
        $content = str_replace("<users_count />", $users_count, $content);

        $sql = $db->prepare("SELECT count(id) as users_count FROM users WHERE status = 'apply'");
        $sql->execute([]);
        $users_count = $sql->fetch(2)['users_count'];
        $content = str_replace("<apply />", $users_count, $content);

        $sql = $db->prepare("SELECT count(id) as users_count FROM users WHERE status = 'not_active'");
        $sql->execute([]);
        $users_count = $sql->fetch(2)['users_count'];
        $content = str_replace("<not_activated />", $users_count, $content);

        $content = str_replace("<tg_search />", $tg_search, $content);
        $content = str_replace("<name_search />", $name_search, $content);
        $content = str_replace("<mail_search />", $mail_search, $content);
        $content = str_replace('option value="'.$type_search.'" type', 'option selected value="'.$type_search.'" type', $content);

        break;

    case 'edit':

        if ((!isset($_GET['id'])) or ($_GET['id'] == '')){
            $content = file_get_contents('modules/account/templates/error.html');
            $content = str_replace('<error />', "Id пользователя не найден", $content);
            break;
        }

        $sql_user = $db->prepare("SELECT u.id, u.tg_login,
                u.name, u.mail, status, last_act_date, registration_date,
                u.admin, coins, invites_left
            FROM users u WHERE id = ?");
        $sql_user->execute([$_GET['id']]);

        if ($sql_user->rowCount() < 1){
            $content = file_get_contents('modules/account/templates/error.html');
            $content = str_replace('<error />', "Id пользователя не найден", $content);
            break;
        }

        $content = file_get_contents('modules/account/templates/edit.html');
        $user = $sql_user->fetch(2);

        foreach (array_keys($user) as $user_key){
            if ($user_key == 'status'){
                if ($user['admin'] == 1){
                    $content = str_replace('<input name="admin" type="checkbox" />', '<input name="admin" type="checkbox" checked />', $content);
                }

                if ($user['status'] == 'banned'){
                    $content = str_replace('<option value="banned">Заблокирован</option>', '<option selected value="banned">Заблокирован</option>', $content);
                }
                elseif ($user['status'] == 'apply'){
                    $content = str_replace('<option value="apply">Заявка</option>', '<option selected value="apply">Заявка</option>', $content);
                }
                elseif ($user['status'] == 'not_active'){
                    $content = str_replace('<option value="not_active">Не активирован</option>', '<option selected value="not_active">Не активирован</option>', $content);
                }
            }
            $content = str_replace("<$user_key />", htmlspecialchars($user[$user_key]), $content);
        }

        $sql_tokens = $db->prepare("SELECT id, token, last_act_date, ip 
            FROM tokens WHERE user_id = ? ORDER BY last_act_date DESC");
        $sql_tokens->execute([$_GET['id']]);

        if ($sql_tokens->rowCount() < 1){

            $content = str_replace("<tokens_table />", '<b>У пользователя нет активных сеансов</b>', $content);

        }
        else {
            $content = str_replace("<tokens_table />", '<table class="items_table">
                                                                        <tr style="transform: none">
                                                                            <td>
                                                                                <b>Токен</b>
                                                                            </td>
                                                                            <td>
                                                                                <b>IP</b>
                                                                            </td>
                                                                            <td>
                                                                                <b>Дата последней активности</b>
                                                                            </td>
                                                                            <td>
                                                                                <b>Действия</b>
                                                                            </td>
                                                                        </tr>
                                                                        <tokens />
                                                                    </table>',
                $content);

            $tokens = $sql_tokens->fetchAll(2);

            $tokens_content = '';

            foreach (array_keys($tokens) as $token) {
                $tokens_content .= file_get_contents('modules/account/templates/token.html');
                $tokens[$token]['last_act_date'] = restyle_date($tokens[$token]['last_act_date']);
                foreach (array_keys($tokens[$token]) as $token_key) {
                    $tokens_content = str_replace("<$token_key />", htmlspecialchars($tokens[$token][$token_key]), $tokens_content);
                }
            }

            $content = str_replace("<tokens />", $tokens_content, $content);
        }

        $tasks = \Account\get_user_tasks($_GET['id']);

        $tasks_content = '<table class="items_table">
            <tr style="transform: none">
                <td>
                    <b>Задание</b>
                </td>
                <td>
                    <b>Статус</b>
                </td>
            </tr>';
        foreach ($tasks as $t){
            $status_text = $t['status'] == 'done' ? 'Выполнено' : ($t['status'] == 'not_done' ? 'Не выполнено' : 'Сбор монеток');
            $status = '
                <select onchange="edit_task(this, '.$t['id'].')">
                    <option value="done">Выполнено</option>
                    <option value="collect">Сбор монеток</option>
                    <option value="not_done">Не выполнено</option>
                </select>';

            $status = str_replace('value="'.$t['status'].'"', ' selected value="'.$t['status'].'"', $status);

            $tasks_content .= ' <tr style="transform: none">
                <td>
                    <b>'.$t['title'].'</b>
                </td>
                <td>
                    '.$status.'
                </td>
            </tr>';
        }
        $tasks_content .= '</table>';

        $content = str_replace("<tasks_table />", $tasks_content, $content);

        break;

}
