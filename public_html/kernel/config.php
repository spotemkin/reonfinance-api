<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
    header("Location: ".$_SERVER['HTTP_REFERER']);
else
    header("Location: index.php");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

$mainDomain = getenv('MAIN_DOMAIN');

$site_title = 'Api';

$dbhost = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$dbuser = getenv('DB_USER');
$dbpass = getenv('DB_PASS');

$farmPeriod = 8;
$farmCoinsPerPeriod = 600;

$maxtps = 20; // Max taps per sec
$scale_max = 500; // Energy volume
$rewards = [3000, 8500, 3500, 4000, 4500, 5000, 5500, 6000, 6500, 7500, 8000]; // Consecutive Days Rewards
$back_url = getenv('BACK_URL');

$invite_link_salt = 'cnjvjxnv'; // not used after set invite code in to DB

$bot_url = getenv('BOT_URL'); // Bot URL Front

$telegram_callback_token = getenv('TG_CALLBACK_TOKEN');
$webapp_url = getenv('MINIAPP_URL'); // API URL
$photo_webbapp_url = '/images/logo_01.jpg'; // image path on backend
$caption_webapp_text = 'REONFinance Click to Earn!'; // Inline menu welcome text
$launch_webapp_button_text = 'Click and Earn'; // Text for URL inline menu
$channel_url = 'https://t.me/REONFinance'; // Channel URL for Inline menu

$join_channel_text = 'Join Community';


$cron_token = '';
$admin_password = '';

$paymentSystemUsername = '';
$paymentSystemPassword = '';
$paymentSystemCert_password = '';

$db_time_offset = 4 * 60 * 60; // not used now

$push_api_key = '';

$google_api_key = '';


$mailfrom = 'prsl.ru@gmail.com';
$mailfrom_short = 'Finance';

$mail_header = '
<body style="/*background: <background_color />; color: <text_color />*/">
<table role="presentation" width="100%" style="width:100%; margin:0; padding:0" cellpadding="0" cellspacing="0" border="0">
<tr>
<td align="center" width="100%" height="100%" valign="top" style="padding: 10px;">
<!--[if gte mso 9]>
<v:fill  opacity="0%" />
<v:textbox inset="0,0,0,0">
<![endif]-->
<table role="presentation" width="100%" style="width:100%" cellpadding="0" cellspacing="0" border="0">
<tr>
        <td>
            <img src="'.$back_url.'/templates/admin/images/logo.png" alt="" style="display:block; width: 418px"/>
        </td>
    </tr>
    </table>
<table role="presentation" width="100%" style="width:100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td style="height: 30px">
        </td>
    </tr>
';

$mail_footer = '
    <tr>
        <td>
            © <a href="https://reoninance.io/">REONFinance</a>. Unsibscribe <a href="https://REONFinance.io/#unsubscribe">Here</a>.
        </td>
    </tr>
</table>
<!--[if gte mso 9]>
</v:textbox>
</v:fill>
</v:rect>
</v:image>
<![endif]-->
</td>
</tr>
</table>
</body>
';


$limit_of_pages = 40;

$expansion = ['.png', '.jpeg', '.svg', '.jpg']; // Pictures extensions

$pass_to_image_url = sprintf(
    "%s://%s",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME']
);

$roles_arr[0]['value'] = '0';
$roles_arr[0]['name'] = 'User';

$roles_arr[1]['value'] = '1';
$roles_arr[1]['name'] = 'Admin';

$roles_arr[2]['value'] = '-1';
$roles_arr[2]['name'] = 'Banned';

$icon_width = 200;
$icon_height = 200;

$max_width = 1200;
$max_height = 1200;

$languages_array = array(
    'ru',
    'en'
);

$privacy_profile_types_array = array(
    'nobody',
    'followers',
    'anybody'
);

$privacy_albums_types_array = array(
    'nobody',
    'followers',
    'anybody'
);

$notifications_types_array = array(
    0,
    1,
);

$sexes_array = array(
    'none',
    'male',
    'female'
);


$XT_logic_token = '';

$Riverbank_token = '';
$Riverbank_shop_id = '';

$gracePeriodTap = 60; //Process data from BD from past to present - grace period in minute

$just_click_task_ids = [2, 3, 5, 9, 10]; // set ID task for Just Click Done Task. Still not use in codeminiapp@tma:~/web/showapi.potemki.com/public_html/kernel$
