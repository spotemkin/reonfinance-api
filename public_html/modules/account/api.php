<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: " . $_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");
header('Content-Type: application/json');

switch ($method) {

case "profile":
    $type = 'object';
    $log = date('Y-m-d H:i:s') . " - profile called for user_id: {$user['id']}\n";

    if ($user['id'] == null) {
        $params['error'] = "TOKEN_NOT_FOUND";
        $log .= "Error: TOKEN_NOT_FOUND\n";
    } else {
        $params = \Account\get_user($user['id']);
        if (key_exists('error', $params)) {
            $log .= "Error: " . $params['error'] . "\n";
        } else {
            $referralData = \Account\get_user_referrals($user['id']);
            $params['referrals'] = $referralData;

            $log .= "Referrals coins to collect: " . $params['referrals']['referrals_coins_to_collect'] . "\n";
            $log .= "Detailed referrals:\n" . print_r($params['referrals']['referrals'], true) . "\n";
        }
    }

    $log .= "Response params:\n" . print_r($params, true) . "\n";
    file_put_contents(__DIR__ . '/api_profile.log', $log, FILE_APPEND);
    break;

    case "application":

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        $sql = $db->prepare("UPDATE users SET mail = ?, status = 'active' WHERE id = ?");
        $sql->execute(['', $user['id']]);

        $sql = $db->prepare("UPDATE tokens SET ip = ? WHERE token = ?");
        $sql->execute([$_SERVER['REMOTE_ADDR'], $user['token']]);

        $params = \Account\get_user($user['id']);
        if (key_exists('error', $params))
            break;

        break;

    case "farmStart":

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        if ($user['status'] == 'apply'){
            $params['error'] = "APPLY";
            break;
        }

        if (strtotime($user['farm_start_date']) > 0){
            $params['error'] = "FARMING";
            break;
        }

        $sql = $db->prepare("UPDATE users SET farm_start_date = current_timestamp WHERE id = ?");
        $sql->execute([$user['id']]);

        $params = \Account\get_user($user['id']);


        break;

    case "farmCollect":

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        if ($user['status'] == 'apply'){
            $params['error'] = "APPLY";
            break;
        }

        if (strtotime($user['farm_start_date']) < 0){
            $params['error'] = "FARM";
            break;
        }

        if (time() - strtotime($user['farm_start_date']) < $farmPeriod * 60 * 60){
            $params['error'] = "TIME";
            break;
        }

        $sql = $db->prepare("UPDATE users SET farm_start_date = DEFAULT, coins = coins + ? WHERE id = ?");
        $sql->execute([$farmCoinsPerPeriod, $user['id']]);

        if ($user['invited_by_id'] != 0){
            \Account\referrers_send_coins($user['id'], $farmCoinsPerPeriod);
        }

        $params = \Account\get_user($user['id']);

        break;

    case "taskStart":

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        if (!isset($_POST['id']) || $_POST['id'] == ''){
            $params['error'] = 'ID_NOT_FOUND';
            break;
        }

        $f = false;
        foreach ($tasks_array as $v){
            if ($v['id'] == $_POST['id']){
                $f = true;
            }
        }

        if (!$f){
            $params['error'] = 'ID_NOT_FOUND';
            break;
        }

        $sql = $db->prepare("SELECT status FROM tasks_roles WHERE task_id = ? AND user_id = ?");
        $sql->execute([$_POST['id'], $user['id']]);

        if ($sql->rowCount() > 0 and $sql->fetch()['status'] == 'done'){
            $params['error'] = 'DONE';
            break;
        }

        $is_just_click_task = in_array($_POST['id'], [2, 3, 5, 9, 10]);
        $status = $is_just_click_task ? 'collect' : 'not_done';

        $sql = $db->prepare("INSERT INTO tasks_roles SET task_id = ?, user_id = ?, status = ?
            ON DUPLICATE KEY UPDATE status = VALUES(status)");
        $sql->execute([$_POST['id'], $user['id'], $status]);

        $params = \Account\get_user($user['id']);

        break;

    case "taskCollect":

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        if (!isset($_POST['id']) || $_POST['id'] == ''){
            $params['error'] = 'ID_NOT_FOUND';
            break;
        }

        $f = false;
        foreach ($tasks_array as $v){
            if ($v['id'] == $_POST['id']){
                $current_task = $v;
                $f = true;
                break;
            }
        }

        if (!$f){
            $params['error'] = 'ID_NOT_FOUND';
            break;
        }

        $sql = $db->prepare("SELECT status FROM tasks_roles WHERE task_id = ? AND user_id = ?");
        $sql->execute([$_POST['id'], $user['id']]);

        if ($sql->rowCount() > 0){
            $status = $sql->fetch()['status'];
            if ($status == 'done') {
                $params['error'] = 'DONE';
                break;
            }
            if ($status == 'not_done'){
                $params['error'] = 'NOT_COMPLETE';
                break;
            }
            if ($status == 'collect'){
                $sql = $db->prepare("UPDATE tasks_roles SET status = 'done' WHERE task_id = ? AND user_id = ?");
                $sql->execute([$_POST['id'], $user['id']]);

                $sql = $db->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $sql->execute([$current_task['coins'], $user['id']]);

                if ($user['invited_by_id'] != 0){
                    \Account\referrers_send_coins($user['id'], $current_task['coins']);
                }
            }
        }
        else{
            $params['error'] = 'NOT_COMPLETE';
            break;
        }

        $params = \Account\get_user($user['id']);

        break;

case 'referralsCollect':
    $type = 'object';
    $log = date('Y-m-d H:i:s') . " - referralsCollect called for user_id: {$user['id']}\n";

    if ($user['id'] == null) {
        $params['error'] = "TOKEN_NOT_FOUND";
        $log .= "Error: TOKEN_NOT_FOUND\n";
    } else {
        $referralData = \Account\get_user_referrals($user['id']);

        $totalCoinsToCollect = $referralData['referrals_coins_to_collect'];

        $log .= "Total coins to collect: $totalCoinsToCollect\n";
        $log .= "Detailed referrals:\n" . print_r($referralData['referrals'], true) . "\n";

        \Account\referrers_collect_coins($user['id']);

        $params = \Account\get_user($user['id']);

        $updatedReferralData = \Account\get_user_referrals($user['id']);
        $params['referrals'] = $updatedReferralData;

        $log .= "Coins collected successfully\n";
        $log .= "Total coins collected: $totalCoinsToCollect\n";
    }

    $log .= "Response params:\n" . print_r($params, true) . "\n";
    file_put_contents(__DIR__ . '/api_referralsCollect.log', $log, FILE_APPEND);
    break;

    case 'tapsCollect':

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        $duration = intval($_POST['duration']); 
        $duration = $duration > 0 ? $duration : 1;
        if ($duration == 1){
            $params['error'] = "DURATION_UNACCEPTABLE";
            break;
        }
        $taps = intval($_POST['taps']);
        $taps = $taps > 0 ? $taps : 1;
        if ($taps == 1){
            $params['error'] = "TAPS_UNACCEPTABLE";
            break;
        }

        $scale_current = intval($_POST['scale_current']);
        $scale_current = $scale_current > 0 ? $scale_current : 1;
        if ($scale_current == 1){
            $params['error'] = "SCALE_CURRENT_UNACCEPTABLE";
            break;
        }

        \Account\taps_collect_coins($user['id'], $duration, $taps, $scale_current);

        // $params = \Account\get_user($user['id']);

        break;

    case 'tapsStats':

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        $params = \Account\get_taps($user['id']);

        break;

    case 'dailyRewardCollect':

        $type = 'object';

        if ($user['id'] == null){
            $params['error'] = "TOKEN_NOT_FOUND";
            break;
        }

        \Account\daily_reward_collect_coins($user['id']);

        $params = \Account\get_user($user['id']);

        break;

    default:

        $params['error'] = 'INCORRECT_METHOD';
        header("HTTP/1.0 404 Not Found");
        break;
}