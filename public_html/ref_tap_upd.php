<?php
try {
    $dbhost = getenv('DB_HOST');
    $dbname = getenv('DB_NAME');
    $dbuser = getenv('DB_USER');
    $dbpass = getenv('DB_PASS');

    $db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$gracePeriodTap = 150;
$batchSize = 5000; // batch size

function referrers_send_tap_coins(): void {
    global $db, $gracePeriodTap, $batchSize;

    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'time_threshold' => '',
        'records_selected' => 0,
        'processed_records' => [],
        'records_deleted' => 0
    ];

    $threshold_time = date('Y-m-d H:i:s', strtotime("-$gracePeriodTap minutes"));
    $log_data['time_threshold'] = $threshold_time;

    do {
        $db->beginTransaction();
        try {
            $sql = $db->prepare("
                SELECT t.id, t.user_id, t.coins, t.timestamp,
                       u1.invited_by_id as level1_referrer,
                       u2.invited_by_id as level2_referrer
                FROM taps t
                JOIN users u1 ON t.user_id = u1.id
                LEFT JOIN users u2 ON u1.invited_by_id = u2.id
                WHERE t.timestamp <= ? AND t.session_msg = 1
                LIMIT $batchSize
            ");

            $sql->execute([$threshold_time]);
            $tap_records = $sql->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tap_records)) {
                break;
            }

            $level1_values = [];
            $level2_values = [];
            $tap_ids = [];

            foreach ($tap_records as $record) {
                $tap_ids[] = $record['id'];

                $log_record = [
                    'id' => $record['id'],
                    'user_id' => $record['user_id'],
                    'coins' => $record['coins'],
                    'timestamp' => $record['timestamp'],
                    'level' => 0
                ];

                if ($record['level1_referrer']) {
                    $level1_values[] = "({$record['level1_referrer']}, {$record['user_id']}, {$record['coins']}, 1)";
                    $log_record['level'] = 1;

                    if ($record['level2_referrer']) {
                        $level2_values[] = "({$record['level2_referrer']}, {$record['user_id']}, {$record['coins']}, 2)";
                        $log_record['level'] = 2;
                    }
                }

                $log_data['processed_records'][] = $log_record;
            }

            if (!empty($level1_values)) {
                $db->exec("INSERT INTO referrals_income (user_id, referral_id, coins, level)
                          VALUES " . implode(',', $level1_values) .
                          " ON DUPLICATE KEY UPDATE coins = coins + VALUES(coins)");
            }

            if (!empty($level2_values)) {
                $db->exec("INSERT INTO referrals_income (user_id, referral_id, coins, level)
                          VALUES " . implode(',', $level2_values) .
                          " ON DUPLICATE KEY UPDATE coins = coins + VALUES(coins)");
            }

            // Удаляем записи пакетами
            $chunks = array_chunk($tap_ids, 1000);
            foreach ($chunks as $chunk) {
                $placeholders = str_repeat('?,', count($chunk) - 1) . '?';
                $sql = $db->prepare("DELETE FROM taps WHERE id IN ($placeholders)");
                $sql->execute($chunk);
                $log_data['records_deleted'] += $sql->rowCount();
            }

            $log_data['records_selected'] += count($tap_records);
            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } while (!empty($tap_records));

    $log_json = json_encode($log_data, JSON_PRETTY_PRINT) . "\n";
    file_put_contents('ref_tap_upd.log', $log_json, FILE_APPEND);
}

referrers_send_tap_coins();