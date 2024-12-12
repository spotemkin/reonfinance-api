<?php

namespace Account;

if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: " . $_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");

function get_user($id) {
  global $db, $bot_url, $back_url, $farmPeriod, $farmCoinsPerPeriod, $scale_max, $rewards;

  $sql = $db->prepare("
      SELECT
          u.*,
          t.scale_current,
          t.session_msg
      FROM users u
      LEFT JOIN (
          SELECT user_id, scale_current, session_msg
          FROM taps
          WHERE (user_id, timestamp) IN (
              SELECT user_id, MAX(timestamp)
              FROM taps
              GROUP BY user_id
          )
      ) t ON t.user_id = u.id
      WHERE u.id = :id
  ");

  $sql->execute(['id' => $id]);

  if ($sql->rowCount() < 1) {
      return ["error" => 'ID_NOT_FOUND'];
  }

  $user = $sql->fetch(2);
  if (!$user['id']) {
      return ["error" => 'ID_NOT_FOUND']; 
  }

  $user['farm_start_date'] = strtotime($user['farm_start_date']);
  $user['last_daily_reward'] = date('Y-m-d', strtotime($user['last_daily_reward']));

  if (!empty($user['photo'])) {
      $user['photo'] = $back_url.'/images/account/'.$user['photo'];
  }

  $today = date('Y-m-d');
  $consecutive_days = get_consecutive_days($id);

  return array_merge($user, [
      'invite_link' => $bot_url.'?start='.$user['invite_code'],
      'tasks' => get_user_tasks($id),
      'referrals' => get_user_referrals($user['id']),
      'farm_period' => $farmPeriod,
      'farm_coins_per_period' => $farmCoinsPerPeriod,
      'scale_max' => $scale_max,
      'scale_current' => $user['scale_current'] ?? 0,
      'session_msg' => $user['session_msg'] ?? "Uninit. No taps session found.",
      'consecutive_days' => $consecutive_days,
      'is_daily_reward' => $consecutive_days > 0 && $user['last_daily_reward'] < $today,
      'daily_reward' => $rewards[$consecutive_days] ?? 0
  ]);
}

function get_user_tasks($id): array
{

    global $db, $tasks_array;
    $user_tasks = $tasks_array;

    foreach ($user_tasks as $k => $v){
        $sql = $db->prepare("SELECT status FROM tasks_roles WHERE user_id = :id AND task_id = :task_id");
        $sql->execute(['id' => $id, 'task_id' => $v['id']]);

        if ($sql->rowCount() < 1){
            $user_tasks[$k]['status'] = 'not_done';
        }
        else{
            $user_tasks[$k]['status'] = $sql->fetch(2)['status'];
        }
    }

    usort($user_tasks, function($a, $b) {
        $order = ['collect' => 0, 'not_done' => 0, 'done' => 1];
        return $order[$a['status']] - $order[$b['status']];
    });

    return $user_tasks;
}

function get_user_referrals($id): array {
    global $db, $back_url;

    $sql = $db->prepare("
        WITH level2_coins AS (
            SELECT u2.invited_by_id as referrer_id, SUM(ri2.coins) as coins
            FROM users u2
            JOIN referrals_income ri2 ON ri2.referral_id = u2.id 
            WHERE ri2.user_id = :id AND ri2.level = 2
            GROUP BY u2.invited_by_id
        )
        SELECT 
            u.id,
            u.name,
            u.photo,
            ri.coins as level1_coins,
            COALESCE(l2.coins, 0) as level2_coins,
            (SELECT COUNT(*) FROM users u2 WHERE u2.invited_by_id = u.id) as referrals_count
        FROM users u
        LEFT JOIN referrals_income ri ON (ri.user_id = :id AND ri.referral_id = u.id AND ri.level = 1)
        LEFT JOIN level2_coins l2 ON l2.referrer_id = u.id
        WHERE u.invited_by_id = :id
    ");

    $sql->execute(['id' => $id]);
    $referrals = $sql->fetchAll(2);

    $total_coins = 0;
    foreach($referrals as $k => $v) {
        if($v['photo']) {
            $referrals[$k]['photo'] = $back_url.'/images/account/'.$v['photo'];
        }
        $referrals[$k]['coins_from_level1'] = ($v['level1_coins'] ?? 0) * 0.1;
        $referrals[$k]['coins_from_level2'] = ($v['level2_coins'] ?? 0) * 0.025;
        $referrals[$k]['referrals'] = $v['referrals_count'];

        $total_coins += $referrals[$k]['coins_from_level1'] + $referrals[$k]['coins_from_level2']; 
    }

    return [
        'referrals' => $referrals,
        'referrals_coins_to_collect' => (int)$total_coins
    ];
}

function referrers_send_coins($id, $coins): void
{
    global $db;

    $sql = $db->prepare("SELECT invited_by_id, id FROM users WHERE id = (SELECT invited_by_id FROM users WHERE id = ?)");
    $sql->execute([$id]);

    if ($sql->rowCount() < 1){
        return;
    }

    $referrer = $sql->fetch();

    $sql = $db->prepare("INSERT INTO referrals_income SET user_id = ?, referral_id = ?, coins = ?, level = 1
        ON DUPLICATE KEY UPDATE coins = coins + VALUES(coins)");
    $sql->execute([$referrer['id'], $id, $coins]);

    if ($referrer['invited_by_id'] == 0)
        return;

    $sql = $db->prepare("SELECT id FROM users WHERE id = ?");
    $sql->execute([$referrer['invited_by_id']]);

    if ($sql->rowCount() < 1)
        return;

    $sql = $db->prepare("INSERT INTO referrals_income SET user_id = ?, referral_id = ?, coins = ?, level = 2
        ON DUPLICATE KEY UPDATE coins = coins + VALUES(coins)");
    $sql->execute([$referrer['invited_by_id'], $id, $coins]);

}

function referrers_collect_coins($id): void {
    global $db;

    try {
        $db->beginTransaction();

        $sql = $db->prepare("
            UPDATE users u
            SET u.coins = u.coins + (
                SELECT COALESCE(
                    SUM(CASE
                        WHEN ri.level = 1 THEN ri.coins * 0.1
                        WHEN ri.level = 2 THEN ri.coins * 0.025
                    END), 0
                )
                FROM referrals_income ri
                WHERE ri.user_id = u.id
            )
            WHERE u.id = :user_id
        ");

        $sql->execute(['user_id' => $id]);

        $sql = $db->prepare("DELETE FROM referrals_income WHERE user_id = ?");
        $sql->execute([$id]);

        $db->commit();
    } catch(Exception $e) {
        $db->rollBack();
        throw $e;
    }
 }

function taps_collect_coins($id, $duration, $taps, $scale_current): void {
    global $db, $scale_max, $maxtps;
    $tps = $taps / $duration;

    $sql = $db->prepare("SELECT timestamp, duration FROM taps WHERE user_id = ? ORDER BY timestamp DESC LIMIT 1");
    $sql->execute([$id]);
    $last_taps_session = $sql->fetch(2);

    $timestamp_end_last = $last_taps_session ? strtotime($last_taps_session['timestamp']) : 0;
    $timestamp_start_this = time() - $duration;

    $status = ($tps <= $maxtps && $timestamp_start_this >= $timestamp_end_last) ? 1 : 0;
    $coins = $status ? ($taps * 1) : 0;

    $sql = $db->prepare("INSERT INTO taps SET user_id = ?, duration = ?, taps = ?, coins = ?, scale_current = ?, scale_max = ?, session_msg = ?");
    $sql->execute([$id, $duration, $taps, $coins, $scale_current, $scale_max, $status]);

    if ($coins > 0) {
        $sql = $db->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
        $sql->execute([$coins, $id]);
    }
 }

function delete_user($whom, $who){

    global $db;

    if ($whom == ''){
        return 0;
    }

    if ($whom != $who) {
        $sql_check = $db->prepare("SELECT id FROM users WHERE id = ? AND admin = 1");
        $sql_check->execute([$who]);
        if ($sql_check->rowCount() < 1)
            return 0;
    }
    else{
        setcookie("token", '', time(), '/', $_SERVER['SERVER_NAME']);
    }

    $sql_user = $db -> prepare("DELETE FROM users WHERE id=?");
    $sql_user->execute([$whom]);

    $sql_user = $db -> prepare("DELETE FROM tokens WHERE user_id=?");
    $sql_user->execute([$whom]);

    $sql_user = $db -> prepare("DELETE FROM tasks_roles WHERE user_id=?");
    $sql_user->execute([$whom]);

    $sql_user = $db -> prepare("DELETE FROM applications WHERE user_id=?");
    $sql_user->execute([$whom]);

    $sql_user = $db -> prepare("DELETE FROM referrals_income WHERE user_id=? OR referral_id = ?");
    $sql_user->execute([$whom, $whom]);

    return 1;
}

function get_taps($id){

    global $db;

    $sql_user = $db->prepare("SELECT u.id, u.name, u.mail,
            u.coins, u.status, u.farm_start_date, u.invites_left, u.photo,
            tg_login
        FROM users u
        WHERE u.id = :id");
    $sql_user->execute(['id' => $id]);

    if ($sql_user->rowCount() < 1){
        return array("error" => 'ID_NOT_FOUND');
    }

    $user = $sql_user->fetch(2);

    if ($user['id'] == '' || $user['id'] == null){
        return array("error" => 'ID_NOT_FOUND');
    }

    if ($user == array())
        return array("error" => 'ID_NOT_FOUND');

    $taps['total'] = $user['coins'];

    $isEnabled = apcu_enabled();
    $ttl = 60; // 1 minute.
    $total_touches_cache_key = 'total_touches';
    if ($isEnabled && apcu_exists($total_touches_cache_key)) {
        $total_touches = apcu_fetch($total_touches_cache_key);
    } else {
        $sql = $db->prepare("SELECT SUM(taps) as qty FROM taps");
        $sql->execute([]);
        $total_touches = $sql->fetch(2);
        if ($isEnabled) {
            apcu_store($total_touches_cache_key, $total_touches, $ttl);
        }
    }
    $taps['total_touches'] = $total_touches['qty'];
    $total_players_cache_key = 'total_players';
    if ($isEnabled && apcu_exists($total_players_cache_key)) {
        $total_players = apcu_fetch($total_players_cache_key);
    } else {
        $sql = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM taps");
        $sql->execute([]);
        $total_players = $sql->fetch(2);
        if ($isEnabled) {
            apcu_store($total_players_cache_key, $total_players, $ttl);
        }
    }
    $taps['total_players'] = $total_players['count'];
    $daily_users_cache_key = 'daily_users';
    if ($isEnabled && apcu_exists($daily_users_cache_key)) {
        $daily_users = apcu_fetch($daily_users_cache_key);
    } else {
        $sql = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM taps WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $sql->execute([]);
        $daily_users = $sql->fetch(2);
        if ($isEnabled) {
            apcu_store($daily_users_cache_key, $daily_users, $ttl);
        }
    }
    $taps['daily_users'] = $daily_users['count'];
    $online_players_cache_key = 'online_players';
    if ($isEnabled && apcu_exists($online_players_cache_key)) {
        $online_players = apcu_fetch($online_players_cache_key);
    } else {
        $sql = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM taps WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $sql->execute([]);
        $online_players = $sql->fetch(2);
        if ($isEnabled) {
            apcu_store($online_players_cache_key, $online_players, $ttl);
        }
    }
    $taps['online_players'] = $online_players['count'];

    return $taps;
}

function get_consecutive_days($id): int {
    global $db;

    // Fetch the last_daily_count from the users table for the given user_id
    $sql = $db->prepare("SELECT last_daily_count FROM users WHERE id = ?");
    $sql->execute([$id]);
    $sql_result = $sql->fetch(2);

    // If a result is found, increment the last_daily_count by 1; otherwise, set to 0
    $consecutive_days = $sql_result ? $sql_result['last_daily_count'] + 1 : 0;

    return $consecutive_days;
}


function daily_reward_collect_coins($id): void
{
    global $db, $rewards;

    // We get the date of the last receipt of the award and the number of consecutive days
    $sql = $db->prepare("SELECT last_daily_reward, last_daily_count FROM users WHERE id = ?");
    $sql->execute([$id]);
    $sql_result = $sql->fetch(2);

    $last_daily_reward = $sql_result && $sql_result['last_daily_reward'] ? date('Y-m-d', strtotime($sql_result['last_daily_reward'])) : '0000-00-00';
    $last_daily_count = $sql_result && $sql_result['last_daily_count'] ? (int)$sql_result['last_daily_count'] : 0;

    $today = date('Y-m-d');

    // If the reward has already been collected today, exit the function
    if ($last_daily_reward == $today) {
        return;
    }
    if ($last_daily_count == 0) {
        $last_daily_count = 1;
    } else if ($last_daily_reward != '0000-00-00' && (strtotime($today) - strtotime($last_daily_reward)) >= (60 * 60 * 24 * 2)) {
        // If the reward was collected more than two days ago, reset the counter to 1
        $last_daily_count = 1;
    } else {
        $last_daily_count += 1;
    }

    // We update the number of days in a circle (1-10) using 9 + 1
    $consecutive_days = $last_daily_count > 0 ? (($last_daily_count - 1) % 9) + 1 : 0;
    $coins = isset($rewards[$consecutive_days]) ? $rewards[$consecutive_days] : 0;

    // If there is an award, add it and update the date of the last receipt of the award
    if ($coins > 0) {
        $sql = $db->prepare("UPDATE users SET coins = coins + ?, last_daily_reward = NOW(), last_daily_count = ? WHERE id = ?");
        $sql->execute([$coins, $last_daily_count, $id]);
    } else {
        // We only update the date of the last receipt of the award and the day counter
        $sql = $db->prepare("UPDATE users SET last_daily_reward = NOW(), last_daily_count = ? WHERE id = ?");
        $sql->execute([$last_daily_count, $id]);
    }
}