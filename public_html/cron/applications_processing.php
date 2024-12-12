<?php

if (!function_exists('hi'))
    die();

$start_time = time();

$sql = $db->prepare("SELECT a.id, user_id, `date`, u.tg_id
    FROM applications a 
    INNER JOIN users u ON a.user_id = u.id
    WHERE `date` <= NOW() - INTERVAL 1 second ");
$sql->execute([]);

$items = $sql->fetchAll(2);

foreach ($items as $i) {
    $sql = $db->prepare("UPDATE users SET status = 'active' WHERE id = :id AND status = 'apply'");
    $sql->execute(['id' => $i['user_id']]);

    $sql = $db->prepare("DELETE FROM applications WHERE user_id = :id");
    $sql->execute(['id' => $i['user_id']]);

    activate($i['user_id']);
}
