<?php

function hi(){}
include 'kernel/core.php';

//write_log('cron', print_r($_GET, 1));

if (!isset($_GET['key']) || $_GET['key'] != $cron_token){
    die();
}

$crons = scandir('cron');
foreach ($crons as $k => $v){
    if ($v == '.' || $v == '..')
        unset($crons[$k]);
}

if (!isset($_GET['file']) || !in_array($_GET['file'], $crons)){
    die();
}



include 'cron/'.$_GET['file'];
