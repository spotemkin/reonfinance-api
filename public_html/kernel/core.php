<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: ".$_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");

date_default_timezone_set('Europe/Berlin');

include ('config.php');
include ('db.php');
include ('functions.php');
include('templates/index/templates.php');
include('SimpleXLSXGen.php');
include('SimpleXLSX.php');
if (file_exists('kernel/TelegramBot.php'))
    include('TelegramBot.php');


