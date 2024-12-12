<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: ".$_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");


$test_forms['text'] = '<div><name />:</div><div><input id="<name />" name="<name />" type="text" value="" /></div>';

$test_forms['int'] = '<div><name />:</div><div><input id="<name />" name="<name />" type="number" value="" /></div>';

$test_forms['select'] = '<div><name />:</div><div><select id="<name />" name="<name />"><options /></select></div>';
$test_forms['option'] = '<option><option_name /></option>';

$test_forms['file'] = '<div><name />:</div><div><input id="<name />" name="<name />" type="file"></div>';


