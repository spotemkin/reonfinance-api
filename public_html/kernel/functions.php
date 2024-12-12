<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: ".$_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");


function mail_check($mail)
{
    if (preg_match("/^[а-яА-Яa-zA-Z0-9_\.\-\+]+@[а-яА-Яa-zA-Z0-9\-]+\.[а-яА-Яa-zA-Z\-\.]+$/Du", $mail) > 0)
        return true;
    else
        return false;
}

function random_string ($str_length) // random line generation
{
    $str_characters = array (0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

    // We return false if the first parameter is zero or is not an integer
    if (!is_int($str_length) || $str_length < 0)
    {
        return false;
    }

    // We count the actual number of characters involved in the formation of a random string and subtract 1
    $characters_length = count($str_characters) - 1;

    // Declaring a variable to store the final result
    $string = '';

    // Forming a random string in a loop
    for ($i = $str_length; $i > 0; $i--)
    {
        $string .= $str_characters[mt_rand(0, $characters_length)];
    }

    // Returning the result
    return $string;
}

function sms($message_text, $login, $pass, $from, $to)
{
    $e = true;
    try {
        $message = @file_get_contents('https://api.digital-direct.ru/submit_message?login=' . $login . '&pass=' . $pass . '&to=' . $to . '&text=' . $message_text);
        if ($message === false) {
            throw new Exception('phone_not_exists');
        }
    }
    catch (Exception $a) {
        $e = false;
    }
    return $e;
}


function write_log($file_name, $text, $dir = '') // log output
{
    $real_file_name = $dir.'logs/' . $file_name . '.log';
    $f = fopen($real_file_name, 'a');
    $str = '[' . date("d.m.y G:i:s", time()) . '] ' . $text . "\n";
    fwrite($f, $str);
    fclose($f);
}

function fix_phone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if ($phone == '') return '';
    return $phone;
}

function options_from_array($array)     // makes a select tag template from an array of html with options from an array
                                        // the input array looks like a = [id => [name = *name*,  value = *value*] ]
{
    $options = '<select>';
    foreach (array_keys($array) as $item)
    {
        $options .= '<option value="<option_value />"><option_name /></option>';
        $options = str_replace("<option_name />", $array[$item]['name'], $options);
        $options = str_replace("<option_value />", $array[$item]['value'], $options);
    }
    $options .= '</select>';
    return $options;
}
function paramswaytoimg($array, $params_array, $way){
    foreach (array_keys($array) as $key) {
        if ((in_array($key, $params_array)) and ($array[$key] != ''))
            $array[$key] = $way.$array[$key];
    }
    return $array;
}

function coordinates_check($lat, $lon)
{
    if (((!is_float($lat)) and (!is_numeric($lat))) or ((!is_float($lon)) and (!is_numeric($lon)))) {
        return false;
    }
    else return true;
}

function distance($lat_1, $lon_1, $lat_2, $lon_2) // The result is in m
{

    $lat_1_sin = sin($lat_1);
    $lat_2_sin = sin($lat_2);
    $lat_1_cos = cos($lat_1);
    $lat_2_cos = cos($lat_2);

    $delta = $lon_2 - $lon_1;

    $delta_cos = cos($delta);
    $delta_sin = sin($delta);

    $y = sqrt(pow($lat_2_cos * $delta_sin, 2) + pow($lat_1_cos * $lat_2_sin - $lat_2_cos * $lat_1_sin * $delta_cos, 2));
    $x = $lat_1_sin * $lat_2_sin + $lat_1_cos * $lat_2_cos * $delta_cos;
    $ad = atan2($y, $x);

    $rad = 6372795;

    return $ad * $rad / 100;

}

function restyle_date($date, $format = "d.m.y G:i") //
{
    return date($format , strtotime($date));
}

function page_menu($url, $sql, array $params)
{
    global $limit_of_pages, $db, $pages_offset;

    $pages = '';

    $pages_amount = $db->prepare($sql);
    $pages_amount->execute($params);
    $pages_amount = $pages_amount->rowCount();
    $pages_offset_amount = floor($pages_amount/$limit_of_pages);
    if ($pages_offset_amount * $limit_of_pages == $pages_amount) $pages_offset_amount--;

    for ($j = 0; $j < $pages_offset_amount + 1; $j++ ) // page by page
    {
        if ($pages_offset == $j)
            $pages .= "<a href='".$url.$j."'><b>".($j+1)."</b></a> ";
        else
            $pages .= "<a href='".$url.$j."'>".($j+1)."</a> ";
    }
    if ($pages_offset_amount == 0) $pages = '';

    $pages = "<div>".$pages."</div>";

    return $pages;
}

function imageCreateFromAny($filepath) {
    $type = exif_imagetype($filepath);
    $allowedTypes = array(
        1,  // [] gif
        2,  // [] jpg
        3,  // [] png
        6   // [] bmp
    );
    if (!in_array($type, $allowedTypes)) {
        return false;
    }
    switch ($type) {
        case 1 :
            $im = imageCreateFromGif($filepath);
            break;
        case 2 :
            $im = imageCreateFromJpeg($filepath);
            break;
        case 3 :
            $im = imageCreateFromPng($filepath);
            break;
        case 6 :
            $im = imageCreateFromBmp($filepath);
            break;
    }
    return $im;
}

// Image resize
function ResizeImage($image_file, $max_width, $max_height, $quality){

    $src_img=imagecreatefromany($image_file);
    if (!$src_img)
        return false;
    $src_width=ImagesX($src_img);
    $src_height=ImagesY($src_img);
    if($max_width == 0){
        if($src_height > $max_height){
            $new_height = $max_height;
            $new_width = $src_width/($src_height/$new_height);
            $resize = 1;
        }else{
            $resize = 0;
        }
    }elseif($max_height == 0){
        if($src_width > $max_width){
            $new_width = $max_width;
            $new_height = $src_height/($src_width/$new_width);
            $resize = 1;
        }else{
            $resize = 0;
        }
    }elseif($src_width > $max_width){
        $new_width = $max_width;
        $new_height = $src_height/($src_width/$new_width);
        if($new_height > $max_height){
            $new_width = $new_width/($new_height/$max_height);
            $new_height = $max_height;
        }
        $resize = 1;
    }else{
        if($src_height > $max_height){
            $new_height = $max_height;
            $new_width = $src_width/($src_height/$new_height);
            $resize = 1;
        }else{
            $resize = 0;
        }
    }
    if($resize == 1){
        $dest_img=ImageCreateTrueColor($new_width, $new_height);
        ImageCopyResampled($dest_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
        ImageJpeg($dest_img, $image_file, $quality);
        ImageDestroy($dest_img);
    }
    return true;
}

function phone_call($code, $phone, $type = 'sms', $custom_text = ''){

    global $send_sms_token, $send_message_from;

    $data = array(
        array(
            'channelType' => 'FLASHCALL',
            'senderName' => $send_message_from,
            'destination' => $phone,
            'content' => $code,
        )
    );
    if ($type == 'voicecode') {
        $data = array(
            array(
                'channelType' => 'VOICECODE',
                'senderName' => $send_message_from,
                'destination' => $phone,
                'content' => array(
                    'contentType' => 'text',
                    'text' => $code,
                ),
            )
        );
    }
    if ($type == 'sms' or
        ((str_starts_with($phone, '7'))
            and (!str_starts_with($phone, '77'))
            and (!str_starts_with($phone, '76')))
    )  {
        if ($custom_text == '')
            $custom_text = 'Authorization code ' . $code . chr(10) . chr(13) . '@' . $mainDomain . ' #' . $code;
        $data = array(
            array(
                'channelType' => 'SMS',
                'senderName' => $send_message_from,
                'destination' => $phone,
                'content' => $custom_text,
            )
        );
    }

    $headers = array('Authorization: Basic ' . $send_sms_token, 'Content-Type: application/json');

    $url = 'https://direct.i-dgtl.ru/api/v1/message';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);

    $result = json_decode($result, true);

    curl_close($ch);

    if (key_exists('error', $result)) {
        if (is_array($result['error']))
            return '';
        if ($result['errors'] == true)
            return '';
    }
    else {
        if (key_exists('messageUuid', $result))
            return $result['messageUuid'];
        if (key_exists('messageUuid', $result['items']))
            return $result['items']['messageUuid'];
        if (key_exists('messageUuid', $result['items'][0]))
            return $result['items'][0]['messageUuid'];
        return 1;
    }
}

function send_tg_mess_to_admin($chats, $text){

//    echo '123';

    $message = new Message();

    $message->setMethod('sendMessage');

    foreach ($chats as $a) {
        $message->setData(array(
            'text' => $text,
            'chat_id' => $a,
            'parse_mode' => 'HTML',
        ));

        $res = $message->send();
        file_put_contents('tg.txt', $res);
    }
}

function send_tg_mess_to_admin_image($chats, $text){

//    echo '123';

    $message = new Message();

    $message->setMethod('sendPhoto');

    foreach ($chats as $a) {
        $message->setData(array(
            'photo' => $text,
            'chat_id' => $a,
        ));

        $res = $message->send();
        file_put_contents('tg.txt', $res);
    }
}

function send_push_notification(
    $to = array(),
    $data = array(
        "body" => 'mesaage text',
        'title' => 'title',
        'priority' => 'high',
        'message' => 'test'
    ),
    $type = 'ios',
    $custom_data = array()
)
{
    global $push_api_key, $push_api_key_huawei, $huawei_app_id, $huawei_client_id;

    if ($type == 'huawei') {

        foreach ($data as $k => $v){
            if (!in_array($k, array('title', 'text', 'body')))
                unset($data[$k]);
        }

        $data['click_action'] = array("type" => 3);

        $fields = array(
            "message" => array(
                "notification" => $data,
                "android" => array(
                    "urgency" => "NORMAL",
                    "ttl" => "10000s",
                    "notification" => $data
                ),
                "token" => $to,
                "data" => json_encode($custom_data, JSON_UNESCAPED_UNICODE)
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $push_api_key_huawei,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://push-api.cloud.huawei.com/v1/' . $huawei_app_id . '/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        curl_close($ch);

        $data_ = json_decode($result, true);

        if ($data_['code'] != '80000000'){

            $fields_ = 'grant_type=client_credentials&'.'client_id='.$huawei_app_id.'&'.'client_secret='.$huawei_client_id;

            $headers = array(
                'Content-Type: application/x-www-form-urlencoded'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://oauth-login.cloud.huawei.com/oauth2/v3/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_);

            $result = curl_exec($ch);
            curl_close($ch);

            $new_token = json_decode($result, true)['access_token'];

            file_put_contents('./config_files/token.log', $new_token);

            $headers = array(
                'Authorization: Bearer ' . $new_token,
                'Content-Type: application/json'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://push-api.cloud.huawei.com/v1/' . $huawei_app_id . '/messages:send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            $result = curl_exec($ch);
            curl_close($ch);

        }
    }
    else {

        if ($custom_data != array())
            $fields = array('registration_ids' => $to, 'notification' => $data, 'data' => $custom_data);
        else
            $fields = array('registration_ids' => $to, 'notification' => $data);

        $headers = array(
            'Authorization: key=' . $push_api_key,
            'Content-Type: application/json'
        );

        $url = 'https://fcm.googleapis.com/fcm/send';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        curl_close($ch);
    }

    return json_decode($result, true);
}

function sendmail($mailto, $title, $message)
{
    global $mailfrom, $mailfrom_short, $mail_header, $mail_footer;

    $boundary = md5( uniqid() . microtime() );

    $headers = "Date: ".date("D, d M Y H:i:s")." UT\r\n";
    $headers.= "MIME-Version: 1.0\r\n";
    $headers.= "From: =?UTF-8?B?".base64_encode($mailfrom_short)."?= <".$mailfrom.">\r\n";
    $headers.= "Reply-To: $mailfrom\r\n";
    $headers.= "List-Unsubscribe: $mailfrom\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

    $title = "=?UTF-8?B?".base64_encode($title)."?=";

    $message = $mail_header.'<tr><td>'.$message.'<br />
            <br />
            <hr /></td></tr>'.$mail_footer;

    $body = "--$boundary\r\n" .
        "Content-Type: text/plain; charset=\"UTF-8\"\r\n" .
        "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split( base64_encode( strip_tags($message) ) );

    // HTML version of message
    $body .= "--$boundary\r\n" .
        "Content-Type: text/html; charset=\"UTF-8\"\r\n" .
        "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split( base64_encode( $message ) );

    $body .= "--$boundary--";

    mail($mailto, $title, $body, $headers, '-f '.$mailfrom);
}

function send_push_to_users($users_array, $data = array(), $custom_data = array()){
    global $db;

    $params_arr_pushes = array();
    //$ws_id_array = array();
    $query_pushes = '';
    foreach (array_keys($users_array) as $key){
        if ((is_array($users_array[$key])) and (key_exists('id', $users_array[$key])))
            array_push($params_arr_pushes, $users_array[$key]['id']);
        else
            array_push($params_arr_pushes, $users_array[$key]);
        $query_pushes .= '?,';
        //array_push($ws_id_array, $users_array[$key]['workspace_id']);
    }

    if ($params_arr_pushes != array()){
        $query_pushes = substr($query_pushes, 0, strlen($query_pushes) - 1);

        $sql_recipient = $db->prepare("SELECT push_token
        FROM tokens t
        WHERE user_id IN ($query_pushes) AND (push_token != '') AND device IN ('ios','android','desktop') GROUP BY push_token");
        $sql_recipient->execute($params_arr_pushes);

        $push_tokens = $sql_recipient->fetchAll(2);

        foreach (array_keys($push_tokens) as $key){
            $push_tokens[$key] = $push_tokens[$key]['push_token'];
        }

        for ($i = 0; $i < intval(count($push_tokens) / 1000) + 1; ++$i) {
            send_push_notification(
                array_slice($push_tokens, $i * 1000, min(1000, count($push_tokens) - $i * 1000)),
                $data,
                'ios',
                $custom_data
            );
        }

        $sql_recipient = $db->prepare("SELECT push_token
        FROM tokens t 
        WHERE user_id IN ($query_pushes) AND (push_token != '') AND device IN ('huawei') GROUP BY push_token");
        $sql_recipient->execute($params_arr_pushes);

        $push_tokens = $sql_recipient->fetchAll(2);

        foreach (array_keys($push_tokens) as $key){
            $push_tokens[$key] = $push_tokens[$key]['push_token'];
        }

        for ($i = 0; $i < intval(count($push_tokens) / 1000) + 1; ++$i) {
            send_push_notification(
                array_slice($push_tokens, $i * 1000, min(1000, count($push_tokens) - $i * 1000)),
                $data,
                'huawei',
                $custom_data
            );
        }
    }
}

function send_push_notification_new($to, $body = 'test body', $title = 'test title', $data = array())
{

    if (is_array($to))
    {
        $fields = array();
        foreach (array_keys($to) as $key){
            if ($data == array())
                array_push($fields, array(
                    "to" => $to[$key],
                    "body" => $body,
                    "title" => $title,
                    "sound" => "default"
                ));
            else
                array_push($fields, array(
                    "to" => $to[$key],
                    "body" => $body,
                    "title" => $title,
                    "sound" => "default",
                    'data' => $data
                ));
        }
    }
    else {

        if ($data == array())
            $fields = array(
                "to" => $to,
                "body" => $body,
                "title" => $title,
                "sound" => "default"
            );
        else
            $fields = array(
                "to" => $to,
                "body" => $body,
                "title" => $title,
                "sound" => "default",
                'data' => $data
            );
    }

    $headers = array('Content-Type: application/json', 'host: exp.host', 'accept: application/json', 'accept-encoding: gzip, deflate');

    $url = 'https://exp.host/--/api/v2/push/send';

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url);
    curl_setopt( $ch, CURLOPT_POST, true);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

if( !function_exists('apache_request_headers') ) {
    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }
}

function delete_last_symbols($str, $amount = 1){
    if ($str == '') return $str;
    return substr($str, 0, strlen($str) - $amount);
}

function arrayDocPrint($array, $no_key = 0){
    $str = '';
    foreach ($array as $k => $v){
        if ($no_key == 1)
            $str .= $v.'<br/>';
        else
            $str .= $k.' - '.$v.'<br/>';
    }
    return $str;
}

function valueExistsByKey($array, $key, $value){
    foreach ($array as $k => $v){
        if ($v[$key] == $value)
            return $k;
    }
    return false;
}

function dateToWrittenDate($date){

    $date = DateTime::createFromFormat('Y-m-d H:i', $date);
    $monthNames = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sen',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec',
    ];
    $dayOfWeek = [
        1 => '(Mo)',
        2 => '(Tu)',
        3 => '(We)',
        4 => '(Th)',
        5 => '(Fr)',
        6 => '(Sa)',
        7 => '(Sn)',
    ];

    return $date->format('d') . ' ' .
        $monthNames[$date->format('n')] . ' ' .
        $dayOfWeek[$date->format('N')] . ' at ' .
        $date->format('H:i');
}

function JWT_create($payload, $secret_key = '', $alg = 'ES256', $header = array()){

    global $examus_jwt_secret;

    if ($header == array()) {
        $header = [
            'alg' => 'RS256',
        ];
    }
    else{
        if ($alg != '')
            $header['alg'] = $alg;
    }

    pretty_print_array($header);
    pretty_print_array($payload);

    // JSON encoding of the header and payload
    $header = json_encode($header);
    $payload = json_encode($payload);

    // transfer to Base64Url
    $header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // Creating a signature
    $signature = '';
    $data = $header . '.' . $payload;
    openssl_sign($data, $signature, $secret_key, 'sha256');

    // Translation of the signature into Base64Url
    $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Assembling JWT
    $jwt = $header . '.' . $payload . '.' . $signature;
    return $jwt;
}

function pretty_print_array($array, $return = false){

    if ($return){
        return '<pre>' . print_r($array, true) . '</pre>';
    }

    echo '<pre>';
    print_r($array);
    echo '<pre>';
}

function paramstoint($array, $params_array){
    foreach (array_keys($array) as $key) {
        if (in_array($key, $params_array))
            $array[$key] = (int)$array[$key];
    }
    return $array;
}

function paymentSystemRequestDevlbox($path, $data, $method){
    global $paymentSystemUsername, $paymentSystemPassword, $paymentSystemCert_password;

    $url = 'https://api.devlbox.net'.$path;

    if (strtolower($method) == 'get' and $data != array()){
        $url .= '/'.$data['id'];
    }

    $ch = curl_init ($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_SSLCERT, 'config_files/desire-sb-api-20270206.crt.pem');
    curl_setopt ($ch, CURLOPT_SSLKEY, 'config_files/desire-sb-api-20270206.key.pem');
    curl_setopt ($ch, CURLOPT_USERPWD, $paymentSystemUsername.':'.$paymentSystemPassword);
    curl_setopt ($ch, CURLOPT_SSLCERTPASSWD, $paymentSystemCert_password);
    curl_setopt ($ch, CURLOPT_SSLKEYPASSWD, $paymentSystemCert_password);
    curl_setopt ($ch, CURLOPT_FAILONERROR, false);
    if (strtolower($method) == 'post'){
        curl_setopt ($ch, CURLOPT_POST, true);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $json = curl_exec ($ch);
    $responce = json_decode ($json, true);

    return $responce;

}

function paymentSystemRequestRiverbank($path, $data, $method){
    global $Riverbank_token, $Riverbank_shop_id;

    $url = 'https://gate.riverbanq.com/transactions/payments'.$path;
    $url = 'https://gate.riverbanq.com/transactions/payments';

    if (strtolower($method) == 'get' and $data != array()){
        $url .= '/'.$data['id'];
    }

    // Converting data to JSON format
    $dataString = json_encode($data, JSON_UNESCAPED_UNICODE);

// Username and password for basic authentication
    $username = $Riverbank_shop_id;
    $password = $Riverbank_token;

// Initializing cURL
    $ch = curl_init($url);

// Configuring cURL Parameters
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
//        'Content-Length: ' . strlen($dataString)
    ));
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

// Request execution
    $response = curl_exec($ch);
//    $response = json_decode ($response, true);

// Checking for errors
    if (curl_errno($ch)) {
        echo 'Ошибка cURL: ' . curl_error($ch);
    } else {
        // Getting the HTTP response code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Response output
        echo "HTTP-код: $httpCode\n";
        echo "Ответ: $response\n";
    }

// cURL Closure
    curl_close($ch);

    return $response;

}
function paymentSystemRequestXTLogic($uri, $data, $method = 'POST'){
    global $XT_logic_token;

    $url = 'https://demo.xt-logic.com:8443';
    $key = file_get_contents("config_files/XT-logic-p2p/PrivateKey.pem");

    if ($key === false) {
        die("Failed to read private key file.\n");
    }

//    $method = 'POST'; // Assuming POST method as both GET and POST were set in original code
//    $uri = '/api/v2/ping';

    $data['timer_id'] = round(microtime(true) * 1000);
    $Request = json_encode($data);
    $signature = XTLogicSign($method, $uri, $Request, $key, $XT_logic_token);

// Формирование команды cURL
    $curl_command = "curl -X $method \"$url$uri\" " .
        "-H \"X-Token-Auth: $XT_logic_token\" " .
        "-H \"X-Token-Sign: $signature\" " .
        "-H \"Content-Type: application/json; charset=UTF-8\" " .
        "-k "; // -k to ignore SSL verification

    if ($method == 'POST') {
        $curl_command .= "-d '" . $Request . "' ";
    }

    exec($curl_command . " -i", $output, $return_var);

    $response = implode("\n", $output);

    return $response;
}


function transliterate($input){
    $gost = array(
        "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
        "е"=>"e", "ё"=>"yo","ж"=>"j","з"=>"z","и"=>"i",
        "й"=>"i","к"=>"k","л"=>"l", "м"=>"m","н"=>"n",
        "о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t",
        "у"=>"y","ф"=>"f","х"=>"h","ц"=>"c","ч"=>"ch",
        "ш"=>"sh","щ"=>"sh","ы"=>"i","э"=>"e","ю"=>"u",
        "я"=>"ya",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
        "Е"=>"E","Ё"=>"Yo","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"I","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"Y","Ф"=>"F","Х"=>"H","Ц"=>"C","Ч"=>"Ch",
        "Ш"=>"Sh","Щ"=>"Sh","Ы"=>"I","Э"=>"E","Ю"=>"U",
        "Я"=>"Ya",
        "ь"=>"","Ь"=>"","ъ"=>"","Ъ"=>"",
        "ї"=>"j","і"=>"i","ґ"=>"g","є"=>"ye",
        "Ї"=>"J","І"=>"I","Ґ"=>"G","Є"=>"YE"
    );
    return strtr($input, $gost);
}

function XTLogicSign(string $method, string $uri, string $data, string $private_key_pem, string $token): string {
    if ($method == 'GET') {
        $key = "{$method}\n{$token}\n{$uri}";
    } else {
        $key = "{$method}\n{$token}\n{$data}";
    }
    openssl_sign($key, $signature, $private_key_pem, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
}


function activate($id): void
{
    global $db, $webapp_url, $channel_url;

    $tg_message = new TelegramBotMessage();

    $sql = $db->prepare("SELECT id, tg_id, invited_by_id, name FROM users WHERE id = ?");
    $sql->execute([$id]);

    $user = $sql->fetch(2);

    $user_name = $user['name'];

    $sql = $db->prepare("SELECT token FROM tokens WHERE user_id = :id LIMIT 1");
    $sql->execute(['id' => $id]);

    if ($sql->rowCount() < 1) {
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
        $sql->execute([$id, $token]);
    }
    else{
        $token = $sql->fetch(2)['token'];
    }

    $tg_message->setData([
        'chat_id' => $user['tg_id'],
        'text' => 'Your account activated, now you can use the app',
        'parse_mode' => 'HTML',
        'reply_markup' => [
            'inline_keyboard' => [
                [[
                    'text' => 'Launch REONFinance',
                    'web_app' => [ 'url' => $webapp_url.'#token='.$token ]
                ]],
                [[
                    'text' => 'Join community',
                    'url' => $channel_url
                ]]
            ]
        ]
    ]);

    $res = $tg_message->send();

    if ($user['invited_by_id'] != 0){
        $sql = $db->prepare("SELECT id, tg_id, invited_by_id, name FROM users WHERE id = ?");
        $sql->execute([$user['invited_by_id']]);

        if ($sql->rowCount() < 1){
            return;
        }

        $user = $sql->fetch(2);

        $sql = $db->prepare("SELECT token FROM tokens WHERE user_id = :id LIMIT 1");
        $sql->execute(['id' => $user['id']]);

        if ($sql->rowCount() < 1) {
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
        }
        else{
            $token = $sql->fetch(2)['token'];
        }

        $tg_message->setData([
            'chat_id' => $user['tg_id'],
            'text' => "Finally! {$user_name} joined team REONFinance!",
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [
                    [[
                        'text' => 'Explore your frens',
                        'web_app' => [ 'url' => $webapp_url.'#token='.$token.'#friends' ]
                    ]]
                ]
            ]
        ]);

        $res = $tg_message->send();
    }
}

function customHexConversion($decimal) {
    $chars = 'ABDFHKLMNPRSTUVXZ123456789';
    $base = strlen($chars);
    $result = '';

    $decimal = intval($decimal);

    if ($decimal === 0) {
        return $chars[0];
    }

    while ($decimal > 0) {
        $remainder = $decimal % $base;
        $result = $chars[$remainder] . $result;
        $decimal = intdiv($decimal, $base);
    }

    return $result;
}