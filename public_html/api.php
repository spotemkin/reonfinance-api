<?php

$start_time = microtime(true);

function hi() {}

// function for direct doping to a file
function direct_log($type, $message) {
    $log_file = __DIR__ . '/api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] [$type] $message\n", FILE_APPEND);
}

// functions for Telegram data processing
function transformInitData($initData) {
    $data = [];
    $pairs = explode('&', $initData);
    foreach ($pairs as $pair) {
        $parts = explode('=', $pair, 2);
        if (count($parts) == 2) {
            $key = urldecode($parts[0]);
            $value = urldecode($parts[1]);
            $data[$key] = $value;
        }
    }
    // Logging of all global arrays and all others
    direct_log('request_data', 'GET: ' . print_r($_GET, true));
    direct_log('request_data', 'POST: ' . print_r($_POST, true));
    direct_log('request_data', 'COOKIE: ' . print_r($_COOKIE, true));
    direct_log('request_data', 'FILES: ' . print_r($_FILES, true));

    $headers = getallheaders();
    direct_log('request_data', 'Headers: ' . print_r($headers, true));

    $raw_input = file_get_contents('php://input');
    direct_log('request_data', 'Raw input: ' . $raw_input);

    // Attempt to decode JSON
    $json_data = json_decode($raw_input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        direct_log('request_data', 'Decoded JSON input: ' . print_r($json_data, true));
    }

    // Log specific info
    direct_log('request_data', 'HTTP Method: ' . $_SERVER['REQUEST_METHOD']);
    direct_log('request_data', 'Query string: ' . $_SERVER['QUERY_STRING']);
    direct_log('request_data', 'Request URI: ' . $_SERVER['REQUEST_URI']);

    // Log Auth
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        direct_log('request_data', 'PHP_AUTH_USER: ' . $_SERVER['PHP_AUTH_USER']);
    }
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        direct_log('request_data', 'HTTP_AUTHORIZATION: ' . $_SERVER['HTTP_AUTHORIZATION']);
    }

    // Log InitData
    if (isset($initData)) {
        direct_log('telegram_auth', 'Raw initData: ' . $initData);
    }
    if (isset($data)) {
        direct_log('telegram_auth', 'Transformed initData: ' . print_r($data, true));
    }

    // Log Client Info
    direct_log('request_data', 'Client IP: ' . $_SERVER['REMOTE_ADDR']);
    direct_log('request_data', 'User Agent: ' . $_SERVER['HTTP_USER_AGENT']);

    return $data;
}

function validate($data, $botToken) {
    direct_log('telegram_auth', 'Data for validation: ' . print_r($data, true));

    if (!isset($data['hash']) || !isset($data['auth_date']) || !isset($data['user'])) {
        direct_log('telegram_auth', 'Error: required fields are missing in data');
        return false;
    }

    $check_hash = $data['hash'];
    unset($data['hash']);
    ksort($data);
    $checkString = '';
    foreach ($data as $key => $value) {
        $checkString .= "$key=$value\n";
    }
    $checkString = rtrim($checkString, "\n");

    direct_log('telegram_auth', 'Check string: ' . $checkString);

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

    $isValid = hash_equals($calculatedHash, $check_hash);
    direct_log('telegram_auth', 'Validation result: ' . ($isValid ? 'Valid' : 'Invalid') . '. Calculated hash: ' . $calculatedHash . '. Received hash: ' . $check_hash);
    return $isValid;
}

function get_user_profile_photo($tg_id, $bot_token) {
    $apiUrl = "https://api.telegram.org/bot$bot_token/";
    $userProfilePhotosUrl = $apiUrl . "getUserProfilePhotos?user_id=" . $tg_id;
    $userProfilePhotosResponse = file_get_contents($userProfilePhotosUrl);
    $userProfilePhotosArray = json_decode($userProfilePhotosResponse, TRUE);

    if ($userProfilePhotosArray['ok'] && $userProfilePhotosArray['result']['total_count'] > 0) {
        $photo = $userProfilePhotosArray['result']['photos'][0][0];
        $fileId = $photo['file_id'];
        $fileUrl = $apiUrl . "getFile?file_id=" . $fileId;
        $fileResponse = file_get_contents($fileUrl);
        $fileArray = json_decode($fileResponse, TRUE);

        if ($fileArray['ok']) {
            $filePath = $fileArray['result']['file_path'];
            $fileDownloadUrl = "https://api.telegram.org/file/bot$bot_token/$filePath";
            $photoData = file_get_contents($fileDownloadUrl);

            $photo = random_string(20).'.jpg';
            while (file_exists('images/account/'.$photo))
                $photo = random_string(20).'.jpg';

            file_put_contents('images/account/'.$photo, $photoData);

            return $photo;
        }
    }
    return false;
}

function process_user_data($tg_id, $tg_login, $user_name, $db, $bot_token) {
    // We receive user data in one request
    $sql = $db->prepare("
        SELECT
            u.id,
            u.mode,
            u.tg_login,
            u.status,
            u.photo,
            (SELECT COUNT(*) FROM users r WHERE r.invited_by_id = u.id) as referral_count
        FROM users u 
        WHERE u.tg_id = ?
    ");
    $sql->execute([$tg_id]);

    $registration = false;
    if ($sql->rowCount() < 1) {
        if ($user_name && strlen($user_name) > 50) {
            direct_log('TooLongTgUserName', 'TgLogin: '.$tg_login.'. TgUserName: '.$user_name);
            $user_name = $tg_login;
        }

        // Generating invite_code
        global $customHexConversion;
        $invite_code = customHexConversion($tg_id);

        // Create new user
        $sql = $db->prepare("
            INSERT INTO users
            SET tg_id = ?,
                tg_login = ?,
                name = ?,
                status = 'not_active',
                invite_code = ?,
                last_act_date = CURRENT_TIMESTAMP
        ");
        $sql->execute([$tg_id, $tg_login, $user_name, $invite_code]);

        $user_id = $db->lastInsertId();
        $registration = true;
    } else {
        $user = $sql->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];

        // We update only if the username has changed
        if ($user['tg_login'] !== $tg_login && $tg_login !== '') {
            $sql = $db->prepare("
                UPDATE users 
                SET tg_login = ?,
                    last_act_date = CURRENT_TIMESTAMP 
                WHERE tg_id = ?
            ");
            $sql->execute([$tg_login, $tg_id]);
        } else {
            // Just updating the time of the last activity
            $sql = $db->prepare("UPDATE users SET last_act_date = CURRENT_TIMESTAMP WHERE id = ?");
            $sql->execute([$user_id]);
        }
    }

    // We receive and update photos only if necessary
    $photo = get_user_profile_photo($tg_id, $bot_token);
    if ($photo) {
        $sql = $db->prepare("UPDATE users SET photo = ? WHERE tg_id = ?");
        $sql->execute([$photo, $tg_id]);
    }

    return array('user_id' => $user_id, 'registration' => $registration);
}

function process_invite($user_id, $utm, $db) {
    direct_log('process_invite', "Function called with user_id: $user_id, utm: $utm");
    if ($utm != ''){
        $sql = $db->prepare("SELECT id FROM users WHERE invite_code = ? AND id != ? AND invites_left > 0");
        $sql->execute([$utm, $user_id]);

        direct_log('process_invite', "SQL query executed. Row count: " . $sql->rowCount());

        if ($sql->rowCount() > 0){
            $invited_by_id = $sql->fetch(PDO::FETCH_ASSOC)['id'];
            direct_log('process_invite', "Inviter found. invited_by_id: " . $invited_by_id);

            $sql = $db->prepare("UPDATE users SET invited_by_id = ? WHERE id = ?");
            $sql->execute([$invited_by_id, $user_id]);

            $sql = $db->prepare("UPDATE users SET invites_left = invites_left - 1 WHERE id = ?");
            $sql->execute([$invited_by_id]);

            direct_log('process_invite', "Calling update_invite_task for invited_by_id: " . $invited_by_id);
            update_invite_task($invited_by_id, $db);
        } else {
            direct_log('process_invite', "No matching inviter found or inviter has no invites left.");
        }
    } else {
        direct_log('process_invite', "UTM is empty, skipping invite processing");
    }
}

function update_invite_task($invited_by_id, $db) {
    $task_invite_3_frens = 4;

    $sql = $db->prepare("SELECT status FROM tasks_roles WHERE user_id = ? AND task_id = ?");
    $sql->execute([$invited_by_id, $task_invite_3_frens]);
    $result = $sql->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $status = $result['status'];
        if ($status == 'not_done') {
            // Counting the number of users invited by this user
            $sql = $db->prepare("SELECT COUNT(*) as invite_count FROM users WHERE invited_by_id = ?");
            $sql->execute([$invited_by_id]);
            $invite_count = $sql->fetch(PDO::FETCH_ASSOC)['invite_count'];

            if ($invite_count > 2){
                // We update the status of the task to 'collect' if 3 or more friends are invited
                $sql = $db->prepare("UPDATE tasks_roles SET status = 'collect' WHERE user_id = ? AND task_id = ?");
                $sql->execute([$invited_by_id, $task_invite_3_frens]);
            }
        }
    }
}

include ('kernel/core.php');

header('Content-Type: application/json; charset=UTF-8');

header("Cache-Control: no-cache");

$method = '';
if (isset($_GET['method'])) $method = $_GET['method'];
$module = '';
if (isset($_GET['module'])) $module = $_GET['module'];

if ($_GET == array())
    die();

if (preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/", $method) < 1)
    die ('ERROR');
else
{

    $post_line = '';
    $files_line = '';
    foreach (array_keys($_POST) as $post)
        $post_line .= ' '.$post.' = '.$_POST[$post]."\n";

    $headers = apache_request_headers();

    $text_header = 'Headers: '."\n";
    foreach (array_keys($headers) as $header)
        $text_header .= ' '.$header.' = '.$headers[$header]."\n";

    direct_log('api_check', 'module = '.$module.'; method = '.$method.';'."\n".'POST: '."\n".$post_line.'FILES:'."\n".$files_line.$text_header);

    $params = array();
    $type = 'array';
    $user = array();

    $user['id'] = null;

    //$token = '';
    if (
        (
            (key_exists('Authorization', $headers)) and
            (!in_array($headers['Authorization'], array('Bearer', '')))
        ) or
        (
            (key_exists('authorization', $headers)) and
            (!in_array($headers['authorization'], array('Bearer', '')))
        )
    ) {
        if ((key_exists('Authorization', $headers)) and ($headers['Authorization'] != 'Bearer'))
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        else if (key_exists('authorization', $headers))
            $token = str_replace('Bearer ', '', $headers['authorization']);
        else
            $token = '';

        $token = urldecode($token);

        direct_log('api_auth', 'Received token: ' . $token);

        // Checking for a token
        $sql = $db->prepare("SELECT u.id, u.name, u.mail, u.registration_date,
                u.coins, u.status, u.farm_start_date, u.invited_by_id, u.last_act_date,
                tg_id, photo
            FROM tokens
            INNER JOIN users u
            ON user_id = u.id
            WHERE token = ?");
        $sql->execute([$token]);

        if ($sql->rowCount() <= 0) {
            direct_log('api_auth', 'Token not found in database, trying Telegram data');
            // If the token is not found, we check for Telegram data
            $botToken = getenv('BOT_TOKEN');
            $data = transformInitData($token);

        if (!empty($data) && validate($data, $botToken)) {
            // If validation was successful, we extract the tg_id
            if (isset($data['user'])) {
                $user_data = json_decode($data['user'], true);
                if (isset($user_data['id'])) {
                    $tg_id = $user_data['id'];
                    $tg_login = $user_data['username'] ?? '';
                    $user_name = $user_data['first_name'] ?? '';
                    direct_log('telegram_auth', 'Telegram data validated. Extracted tg_id: ' . $tg_id);

                    // Check if user exists
                    $sql = $db->prepare("SELECT id FROM users WHERE tg_id = ?");
                    $sql->execute([$tg_id]);

                    if ($sql->rowCount() == 0) {
                        // User doesn't exist, process new user
                        $result = process_user_data($tg_id, $tg_login, $user_name, $db, $botToken);
                        $user_id = $result['user_id'];
                        $registration = true;

                        // Send Greeting message for new user
                        $bot_token = getenv('BOT_TOKEN');
                        $apiUrl = "https://api.telegram.org/bot$bot_token/sendPhoto";
                        $messageData = [
                            'chat_id' => $tg_id,
                            'photo' => $back_url . $photo_webbapp_url,
                            'caption' => $caption_webapp_text,
                            'reply_markup' => json_encode([
                                'inline_keyboard' => [
                                    [[
                                        'text' => $launch_webapp_button_text,
                                        'web_app' => [ 'url' => $webapp_url]
                                    ]],
                                    [[
                                        'text' => $join_channel_text,
                                        'url' => $channel_url
                                    ]]
                                ]
                            ])
                        ];
                        $ch = curl_init($apiUrl);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $response = curl_exec($ch);
                        curl_close($ch);
                        direct_log('welcome_message', 'Sent welcome message to new user: ' . $tg_id . '. Response: ' . $response);
                    } else {
                        // User exists
                        $user = $sql->fetch(2);
                        $user_id = $user['id'];
                        $registration = false;
                        direct_log('welcome_message', 'User already exists, skipping welcome message for: ' . $tg_id);
                    }


                        // Processing UTM tags
                        if (isset($data['start_param'])) {
                            $utm = $data['start_param'];
                            direct_log('utm_processing', 'UTM before process_invite: ' . $utm);
                            process_invite($user_id, $utm, $db);
                        }

                        // Generating and saving a token
                        $token = random_string(32);
                        $token_check = $db->prepare("SELECT id FROM tokens WHERE token=?");
                        $token_check->execute([$token]);
                        while ($token_check->rowCount() > 0)
                        {
                            $token = random_string(32);
                            $token_check = $db->prepare("SELECT id FROM tokens WHERE token=?");
                            $token_check->execute([$token]);
                        }

                        $sql = $db->prepare("INSERT INTO tokens SET user_id = ?, token = ?, last_act_date = current_timestamp");
                        $sql->execute([$user_id, $token]);

                        // Getting user data
                        $sql = $db->prepare("SELECT u.id, u.name, u.mail, u.registration_date,
                            u.coins, u.status, u.farm_start_date, u.invited_by_id, u.last_act_date,
                            tg_id, photo
                            FROM users u
                            WHERE u.id = ?");
                        $sql->execute([$user_id]);
                    } else {
                        direct_log('telegram_auth', 'Error: id not found in user data');
                        $params['error'] = 'INVALID_DATA';
                        die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    }
                } else {
                    direct_log('telegram_auth', 'Error: user data not found');
                    $params['error'] = 'INVALID_DATA';
                    die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }
            } else {
                direct_log('telegram_auth', 'Telegram data validation failed or data is empty');
                $params['error'] = 'TOKEN_NOT_FOUND';
                die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        } else {
            direct_log('api_auth', 'Token found in database');
        }

        $user = $sql->fetch(2);
        direct_log('api_auth', 'User data fetched: ' . print_r($user, true));
        //            print_r($user);
        $user['token'] = $token;

        if ($user['status'] == 'banned') {
            direct_log('api_auth', 'User is banned: ' . $user['id']);
            $params['error'] = 'BANNED_USER';
            die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if (time() - strtotime($user['last_act_date']) > 60 * 10) {
            direct_log('api_auth', 'Updating last activity for user: ' . $user['id']);
            $sql_last_login = $db->prepare("UPDATE tokens SET last_act_date = current_timestamp WHERE token = ?");
            $sql_last_login->execute([$token]);
            $sql_last_login = $db->prepare("UPDATE users SET last_act_date = current_timestamp WHERE id = ?");
            $sql_last_login->execute([$user['id']]);
        }
    } else {
        direct_log('api_auth', 'No valid Authorization header found');
        $params['error'] = 'TOKEN_NOT_FOUND';
        die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

//    print_r($user);

    if (file_exists('modules/' . $module . '/config.php'))
        include('modules/' . $module . '/config.php');

    $modules_dir = scandir('modules');

    foreach ($modules_dir as $menu_item) //displaying all modules in the menu
    {
        if (file_exists('modules/' . $menu_item . '/functions.php'))
            include_once('modules/' . $menu_item . '/functions.php');
    }

    if (file_exists('modules/' . $module . '/functions.php'))
        include_once('modules/' . $module . '/functions.php');


    if (file_exists('modules/' . $module . '/index.php'))
        include('modules/' . $module . '/api.php');
    else {
        direct_log('api_error', 'Incorrect module: ' . $module);
        $params['1'] = $module;
        $params['error'] = 'INCORRECT_MODULE';
        //header("HTTP/1.0 404 Not Found");
        die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    switch ($type){
        case 'array':
            if (!array_key_exists('error', $params)) {
                $json[0] = '[';
                $json[strlen($json) - 1] = ']';
            }
            break;
        case 'object':
            $json[0] = '{';
            $json[strlen($json)-1] = '}';
            break;
        case 'none':

            break;
    }
//    echo microtime(true)-$start_time."\n";

    direct_log('api_response', 'Final JSON response: ' . $json);
    echo $json;

}