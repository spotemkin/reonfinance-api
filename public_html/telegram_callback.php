<?php

function hi(){}
include 'kernel/core.php';
//write_log('1', print_r($_GET, 1));

$data = json_decode(file_get_contents('php://input'), true);
//write_log('2', print_r($data, 1));

if (!isset($_GET['k']) || $telegram_callback_token != $_GET['k']){

    die();
}

$bot_token = getenv('BOT_TOKEN');

$is_admin = 0;
$chat_id = 0;
$tg_login = '';
$callback = false;
$user_id = 0;
$user_name = '';
$message_id = 0;
$message_text = '';
$language = 'ru';
$message_type = '';
$pre_checkout_query_id = 0;
$payment_id = 0;
$ukassa_payment_id = '';
$message_text_before_callback = '';
$task_invite_3_frens = 4;

$tg_message = new TelegramBotMessage();

// Input user data, chat data, etc
if (
    (key_exists('message', $data)) and
    (key_exists('chat', $data['message'])) and
    (key_exists('id', $data['message']['chat']))
)
{
    $tg_login = $data['message']['chat']['username'] ?? '';
    $chat_id = $data['message']['chat']['id'];

    if (
        (key_exists('from', $data['message'])) and
        (key_exists('id', $data['message']['from']))
    )
        $user_id = $data['message']['from']['id'];

    if (
        (key_exists('from', $data['message'])) and
        (key_exists('language_code', $data['message']['from'])) and
        in_array($data['message']['from']['language_code'], array_keys($languages_array))
    )
        $language = $data['message']['from']['language_code'];

    if (
        (key_exists('from', $data['message'])) and
        (key_exists('first_name', $data['message']['from']))
    )
        $user_name = $data['message']['from']['first_name'];

    if (
        (key_exists('text', $data['message']))
    )
        $message_text = $data['message']['text'];

    if (
        (key_exists('message_id', $data['message']))
    )
        $message_id = $data['message']['message_id'];

    if (
        (key_exists('successful_payment', $data['message']))
    ){
        $payment_id = $data['message']['successful_payment']['invoice_payload'];
        $ukassa_payment_id = $data['message']['successful_payment']['provider_payment_charge_id'];
        $message_type = 'successful_payment';
    }
}
elseif (
    (key_exists('callback_query', $data)) and
    (key_exists('message', $data['callback_query'])) and
    (key_exists('chat', $data['callback_query']['message'])) and
    (key_exists('id', $data['callback_query']['message']['chat']))
)
{
    $tg_login = $data['callback_query']['message']['chat']['username'] ?? '';
    $chat_id = $data['callback_query']['message']['chat']['id'];
    $callback = true;

    if (
        (key_exists('from', $data['callback_query'])) and
        (key_exists('id', $data['callback_query']['from']))
    )
        $user_id = $data['callback_query']['from']['id'];

    if (
        (key_exists('from', $data['callback_query'])) and
        (key_exists('first_name', $data['callback_query']['from']))
    )
        $user_name = $data['callback_query']['from']['first_name'];

    if (
        (key_exists('text', $data['callback_query']['message']))
    )
        $message_text_before_callback = $data['callback_query']['message']['text'];
    if (
        (key_exists('caption', $data['callback_query']['message']))
    )
        $message_text_before_callback = $data['callback_query']['message']['caption'];

    if (
        (key_exists('data', $data['callback_query']))
    )
        $message_text = $data['callback_query']['data'];

    if (
        (key_exists('message', $data['callback_query'])) and
        (key_exists('message_id', $data['callback_query']['message']))
    )
        $message_id = $data['callback_query']['message']['message_id'];
}
elseif (
    (key_exists('chat_member', $data)) and
    (key_exists('chat', $data['chat_member'])) and
    (key_exists('id', $data['chat_member']['chat'])) and
    (key_exists('new_chat_member', $data['chat_member'])) and
    (key_exists('status', $data['chat_member']['new_chat_member'])) and
    (key_exists('user', $data['chat_member']['new_chat_member'])) and
    (key_exists('id', $data['chat_member']['new_chat_member']['user']))
)
{
    write_log('2', print_r($data, 1));
    if ($data['chat_member']['new_chat_member']['status'] == 'member'){

        $sql = $db->prepare("SELECT id FROM users WHERE tg_id = ?");
        $sql->execute([$data['chat_member']['new_chat_member']['user']['id']]);

        if ($sql->rowCount() > 0){
            $user_id = $sql->fetch(2)['id'];

            $sql = $db->prepare("SELECT id FROM tasks_roles
                WHERE user_id = ? AND task_id = 1 AND status = 'done'");
            $sql->execute([$user_id]);

            if ($sql->rowCount() < 1) {
                $sql = $db->prepare("INSERT INTO tasks_roles
                    SET user_id = ?, task_id = 1, status = 'collect'
                    ON DUPLICATE KEY UPDATE status = VALUES(status)");
                $sql->execute([$user_id]);
            }
        }
    }
    $chat_check = true;

    die();
}

if ($chat_id == 0){
    die();
}

$sql = $db->prepare("SELECT id, mode, tg_login FROM users WHERE tg_id = ?");
$sql->execute([$user_id]);

$registration = false;
if ($sql->rowCount() < 1) {
    if ($user_name && strlen($user_name) > 50) {
        error_log('TooLongTgUserName. TgLogin: '.$tg_login.'. TgUserName: '.$user_name);
        $user_name = $tg_login;
    }

    // generate invite_code from tg_id
    global $customHexConversion;
    $invite_code = customHexConversion($user_id);

    $sql = $db->prepare("INSERT INTO users SET tg_id = ?, tg_login = ?, name = ?, status = 'not_active', invite_code = ?");
    $sql->execute([$user_id, $tg_login, $user_name, $invite_code]);
    $registration = true;
}
else {
    $user = $sql->fetch(2);
    if ($user['tg_login'] != $tg_login && $tg_login != '') {
        $sql = $db->prepare("UPDATE users SET tg_login = ? WHERE tg_id = ?");
        $sql->execute([$tg_login, $user_id]);
    }

    $sql = $db->prepare("UPDATE users SET last_act_date = current_timestamp WHERE id = ?");
    $sql->execute([$user['id']]);
}

$apiUrl = "https://api.telegram.org/bot$bot_token/";

$userProfilePhotosUrl = $apiUrl . "getUserProfilePhotos?user_id=" . $user_id;
$userProfilePhotosResponse = file_get_contents($userProfilePhotosUrl);
$userProfilePhotosArray = json_decode($userProfilePhotosResponse, TRUE);

if ($userProfilePhotosArray['ok'] && $userProfilePhotosArray['result']['total_count'] > 0) {
    // Get latest Photo
    $photo = $userProfilePhotosArray['result']['photos'][0][0];

    // Get Data Photo
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

        // Save Photo on server
        file_put_contents('images/account/'.$photo, $photoData);

        $sql = $db->prepare("UPDATE users SET photo = ? WHERE tg_id = ?");
        $sql->execute([$photo, $user_id]);

        echo "User Photo Save as 'user_photo.jpg'";
    } else {
        echo "Failed to get the file.";
    }
} else {
    echo "The user does not have any profile photos.";
}

$sql = $db->prepare("SELECT u.`id`, u.`name`, u.`tg_login`, u.`tg_id`, u.invited_by_id
    FROM users u
    WHERE tg_id = ?");
$sql->execute([$user_id]);

$user = $sql->fetch(2);

// Generate and save app token
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
$sql->execute([$user['id'], $token]);

// Check "start" param and crop invite link and make menu for bot
if ($message_text == '/start' || str_starts_with($message_text, '/start')){

    if (str_contains($message_text, '/start ')) {
        $utm = str_replace('/start ', '', $message_text);
    } else {
        $utm = str_replace('/start', '', $message_text);
    }

    if ($utm != '' && $registration) {
        $sql = $db->prepare("SELECT id FROM users WHERE invite_code = ? AND id != ? AND invites_left > 0");
        $sql->execute([$utm, $user['id']]);

        if ($sql->rowCount() > 0) {
            $invited_by_id = $sql->fetch()['id'];

            $sql = $db->prepare("UPDATE users SET invited_by_id = ? WHERE id = ?");
            $sql->execute([$invited_by_id, $user['id']]);

            $sql = $db->prepare("UPDATE users SET invites_left = invites_left - 1 WHERE id = ?");
            $sql->execute([$invited_by_id]);

            $sql = $db->prepare("SELECT status FROM tasks_roles WHERE user_id = ? AND task_id = ?");
            $sql->execute([$invited_by_id, $task_invite_3_frens]);
            $result = $sql->fetch();
            if ($result) {
                $status = $result['status'];
                if ($status == 'not_done') {
                    $sql = $db->prepare("SELECT COUNT(*) as invite_count FROM users WHERE invited_by_id = ?");
                    $sql->execute([$invited_by_id]);
                    $invite_count = $sql->fetch()['invite_count'];

                    if ($invite_count > 2) {
                        $sql = $db->prepare("UPDATE tasks_roles SET status = 'collect' WHERE user_id = ? AND task_id = ?");
                        $sql->execute([$invited_by_id, $task_invite_3_frens]);
                    }
                }
            }
        }
    }

    $tg_message->setMethod('sendPhoto');
    $tg_message->setData(array(
        'chat_id' => $chat_id,
        'photo' => $back_url . $photo_webbapp_url,
        'caption' => $caption_webapp_text,
        'reply_markup' => [
            'inline_keyboard' => [
                [[
                    'text' => $launch_webapp_button_text,
                    'web_app' => [ 'url' => $webapp_url.'#token='.$token ]
                ]],
                [[
                    'text' => $join_channel_text,
                    'url' => $channel_url
                ]]
            ]
        ]
    ));

    $res = $tg_message->send();
}
