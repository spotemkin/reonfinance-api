<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: " . $_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");

$content = file_get_contents('modules/analitics/templates/index.html');
$type = 'u_registration_date';
if ((isset($_GET['type'])) and ($_GET['type'] != '')) $type = $_GET['type'];
$content = str_replace('<option t value="'.$type.'">', '<option selected t value="'.$type.'">', $content);

if ($type != 'u_registration_date')
    $content = str_replace('Количество зарегистрированных пользователей: <b><users_count /></b>', '', $content);

if (in_array($type, array('u_last_act_date', 'u_registration_date', 'с_income_sales')))
    $content = str_replace('<date_from_to />', '<div>
        Дата от: <input name="from" id="from" style="width: 80px" type="text" placeholder="От" value="<from />" />
        <script>
            let date = \'<from1 />\';
            if (\'<from1 />\' === \'\') date = new Date();
            const myDatePicker = MCDatepicker.create({
                el: \'#from\',
                selectedDate: new Date(date),
                dateFormat: \'DD.MM.YYYY\'
            })
        </script>
до: <input name="to" id="to" type="text" style="width: 80px" placeholder="До" value="<to />" />
        <script>
let date1 = \'<to1 />\';
            if (\'<to1 />\' === \'\') date1 = new Date();
            const myDatePicker1 = MCDatepicker.create({
                el: \'#to\',
                selectedDate: new Date(date1),
                dateFormat: \'DD.MM.YYYY\'
            })
        </script>
        <input type="button" value="Применить" onclick="this.form.submit() ">
    </div>', $content);

switch ($act) {

    case '':

        switch ($type) {

            case 'u_sex':

                $sql = $db->prepare("SELECT
                   count(sex), sex
                FROM users GROUP BY sex");
                $sql->execute([]);

                $sex = $sql->fetchAll(2);
                $data = array();
                $labels = array();
                $colors = array();
                    //print_r($sex);
                foreach (array_keys($sex) as $key) {
                    array_push($data, $sex[$key]['count(sex)']);
                    array_push($labels, $sexes_arr[$sex[$key]['sex']]['label']);
                    array_push($colors, $sexes_arr[$sex[$key]['sex']]['color']);
                }

                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"pie"', $content);
                $content = str_replace('<options />', '', $content);
                $content = str_replace('<display />', 'true', $content);

                break;

            case 'u_age':

                $sql = $db->prepare("SELECT
                    birth_date AS data
                FROM users WHERE birth_date != '1970-01-01'");
                $sql->execute([]);

                $dates = $sql->fetchAll(2);
                foreach (array_keys($dates) as $key){
                    $dates[$key] = getAge($dates[$key]['data']);
                }
                $dates = array_count_values($dates);
                ksort($dates);
                $data = array();
                $labels = array();
                $colors = array('rgb(22, 164, 224, 1)');

                for($i = 10; $i < 50; ++$i){
                    if (key_exists($i, $dates)){
                        array_push($data, $dates[$i]);
                        array_push($labels, $i);
                    }
                    else{
                        array_push($data, 0);
                        array_push($labels, $i);
                    }
                }

                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"line"', $content);
                $content = str_replace('<display />', 'false', $content);
                $content = str_replace('<options />',
                    'scales: {
                                  yAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                            beginAtZero: true,
                                            callback: function(value) {if (value % 1 === 0) {return value;}},
                                        }
                                  }],
                                  xAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                        }
                                  }]
                                }',
                    $content);

                break;

            case 'u_registration_date':

                $from = date('Y-m-d', time() - 60 * 60 * 24 * 60); // 2 мес назад
                if ((isset($_GET['from'])) and ($_GET['from'] != ''))
                    $from = restyle_date($_GET['from'], 'Y-m-d');

                $to = date('Y-m-d', time()); // 2 мес назад
                if ((isset($_GET['to'])) and ($_GET['to'] != ''))
                    $to = restyle_date($_GET['to'], 'Y-m-d');

                $content = str_replace("<from1 />", restyle_date($from, 'Y-m-d'), $content);
                $content = str_replace("<from />", restyle_date($from, 'd.m.Y'), $content);

                $content = str_replace("<to1 />", restyle_date($to, 'Y-m-d'), $content);
                $content = str_replace("<to />", restyle_date($to, 'd.m.Y'), $content);

                $sql = $db->prepare("SELECT COUNT(id) AS a FROM users WHERE status IN (2,3) AND DATE(registration_date) >= ?
                                   AND DATE(registration_date) <= ?");
                $sql->execute([$from, $to]);
                $users_count = $sql->fetch(2)['a'];

                $sql = $db->prepare("SELECT count(registration_date) AS data, DATE(registration_date) AS label
                FROM users WHERE status IN (2,3) AND DATE(registration_date) >= ? AND DATE(registration_date) <= ? GROUP BY DATE(registration_date)");
                $sql->execute([$from, $to]);

                $sex = $sql->fetchAll(2);

                $data = array();
                $labels = array();
                $colors = array('rgb(22, 164, 224, 1)');
                //print_r($sex);
                foreach (array_keys($sex) as $key) {
                    array_push($data, $sex[$key]['data']);
                    array_push($labels, restyle_date($sex[$key]['label'], 'd.m'));
                    //array_push($colors, $sexes_arr[$sex[$key]['sex']]['color']);
                }

                $content = str_replace('<users_count />', $users_count, $content);
                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"line"', $content);
                $content = str_replace('<options />',
                    'scales: {
                                  yAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                            beginAtZero: true,
                                            callback: function(value) {if (value % 1 === 0) {return value;}},
                                        }
                                  }],
                                  xAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                        }
                                  }]
                                }',
                    $content);
                $content = str_replace('<display />', 'false', $content);

                break;

            case 'u_last_act_date':

                $from = date('Y-m-d h:i:s', time() - 60 * 60 * 24 * 60); // 2 мес назад
                if ((isset($_GET['from'])) and ($_GET['from'] != ''))
                    $from = restyle_date($_GET['from'], 'Y-m-d h:i:s');

                $to = date('Y-m-d h:i:s', time()); // 2 мес назад
                if ((isset($_GET['to'])) and ($_GET['to'] != ''))
                    $to = restyle_date($_GET['to'], 'Y-m-d h:i:s');

                $content = str_replace("<from1 />", restyle_date($from, 'Y-m-d'), $content);
                $content = str_replace("<from />", restyle_date($from, 'd.m.Y'), $content);

                $content = str_replace("<to1 />", restyle_date($to, 'Y-m-d'), $content);
                $content = str_replace("<to />", restyle_date($to, 'd.m.Y'), $content);

                $sql = $db->prepare("SELECT DATE(MAX(last_act_date)) as data FROM tokens
                    WHERE DATE(last_act_date) >= ? AND DATE(last_act_date) <= ?
                    GROUP BY user_id");
                $sql->execute([$from, $to]);

                $sex = $sql->fetchAll(2);
                foreach (array_keys($sex) as $key){
                    $sex[$key] = $sex[$key]['data'];
                }


                $sex = array_count_values($sex);
                ksort($sex);
                //print_r($sex);
                $data = array();
                $labels = array();
                $colors = array('rgb(22, 164, 224, 1)');
                $points_colors = array();

                foreach (array_keys($sex) as $key) {
                    array_push($data, $sex[$key]);
                    array_push($labels, restyle_date($key, 'd.m'));
                    array_push($points_colors, 'rgba(160, 22, 224, 1)');
                    //array_push($colors, $sexes_arr[$sex[$key]['sex']]['color']);
                }

                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                //$content = str_replace('<point_bg_color />', json_encode($points_colors), $content);
                $content = str_replace('<type />', '"line"', $content);
                $content = str_replace('<options />',
                    'scales: {
                                  yAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                            beginAtZero: true,
                                            callback: function(value) {if (value % 1 === 0) {return value;}},
                                        }
                                  }],
                                  xAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                        }
                                  }]
                                }',
                    $content);
                $content = str_replace('<display />', 'false', $content);

                break;

            case 'c_users':

                $sql = $db->prepare("SELECT title as label, students as data
                    FROM courses c 
                    LEFT OUTER JOIN roles r2 ON (c.id = r2.course_id) AND (r2.role=1) 
                    LEFT OUTER JOIN users u ON r2.user_id = u.id 
                    GROUP BY c.id ORDER BY students DESC");
                $sql->execute([]);

                $divisions = 10;

                $data = array();
                $labels = array();
                $colors = array();
                $others = 0;

                $sex = $sql->fetchAll(2);
                //print_r($sex);
                foreach (array_keys($sex) as $key){
                    if ($key < $divisions) {
                        array_push($data, $sex[$key]['data']);
                        array_push($labels, $sex[$key]['label']);
                        array_push($colors, 'rgba('.
                            strval((124 + intval(((241 - 124) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            strval((0 + intval(((92  - 0) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            strval((135 + intval(((255 - 135) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            '1)');
                    }
                    else{
                        $others += $sex[$key]['data'];
                    }
                }
                array_push($data, $others);
                array_push($labels, 'Остальные');
                array_push($colors, 'rgba(93,0,102, 1)');

                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"pie"', $content);
                $content = str_replace('<options />', '', $content);
                $content = str_replace('<display />', 'false', $content);

                break;

            case 'с_courses':

                $sql = $db->prepare("SELECT c.title as label, SUM(p.price) as data
                    FROM payments p
                    LEFT OUTER JOIN courses c ON (c.id = p.course_id)
                    WHERE (p.price > 0) AND (p.type IN (1, 2)) AND (p.status = 1)
                    GROUP BY c.id ORDER BY data DESC");
                $sql->execute([]);

                $divisions = 10;

                $data = array();
                $labels = array();
                $colors = array();
                $others = 0;

                $sex = $sql->fetchAll(2);
                //print_r($sex);
                foreach (array_keys($sex) as $key){
                    if ($key < $divisions) {
                        array_push($data, $sex[$key]['data']);
                        array_push($labels, $sex[$key]['label']);
                        array_push($colors, 'rgba('.
                            strval((124 + intval(((241 - 124) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            strval((0 + intval(((92  - 0) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            strval((135 + intval(((255 - 135) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            '1)');
                    }
                    else{
                        $others += $sex[$key]['data'];
                    }
                }
                array_push($data, strval($others));
                array_push($labels, 'Остальные');
                array_push($colors, 'rgba(93,0,102, 1)');

                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"pie"', $content);
                $content = str_replace('<options />', '', $content);
                $content = str_replace('<display />', 'false', $content);

                break;

            case 'с_income':

                $sql = $db->prepare("SELECT c.title as label, SUM(p.price) as data
                    FROM payments p
                    LEFT OUTER JOIN courses c ON (c.id = p.course_id)
                    WHERE (p.type IN (4)) AND (p.status = 1)
                    GROUP BY c.id ORDER BY data DESC");
                $sql->execute([]);

                $divisions = 10;

                $data = array();
                $labels = array();
                $colors = array();
                $others = 0;

                $sex = $sql->fetchAll(2);
                //print_r($sex);
                foreach (array_keys($sex) as $key){
                    if ($key < $divisions) {
                        array_push($data, abs($sex[$key]['data']));
                        array_push($labels, $sex[$key]['label']);
                        array_push($colors, 'rgba('.
                            strval((124 + intval(((241 - 124) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            strval((0 + intval(((92  - 0) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            strval((135 + intval(((255 - 135) * $key / min($divisions + 1, count($sex) + 1))))).','.
                            '1)');
                    }
                    else{
                        $others += $sex[$key]['data'];
                    }
                }
                array_push($data, strval(abs($others)));
                array_push($labels, 'Остальные');
                array_push($colors, 'rgba(93,0,102, 1)');

                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"pie"', $content);
                $content = str_replace('<options />', '', $content);
                $content = str_replace('<display />', 'false', $content);

                break;

            case 'с_income_sales':

                $from = date('Y-m-d', time() - 60 * 60 * 24 * 60); // 2 мес назад
                if ((isset($_GET['from'])) and ($_GET['from'] != ''))
                    $from = restyle_date($_GET['from'], 'Y-m-d');

                $to = date('Y-m-d', time()); // 2 мес назад
                if ((isset($_GET['to'])) and ($_GET['to'] != ''))
                    $to = restyle_date($_GET['to'], 'Y-m-d');

                $content = str_replace("<from1 />", restyle_date($from, 'Y-m-d'), $content);
                $content = str_replace("<from />", restyle_date($from, 'd.m.Y'), $content);

                $content = str_replace("<to1 />", restyle_date($to, 'Y-m-d'), $content);
                $content = str_replace("<to />", restyle_date($to, 'd.m.Y'), $content);

                $sql = $db->prepare("SELECT SUM(p.price) as data, p.date as label
                FROM payments p 
                    WHERE type IN (1,2) AND status = 1 AND DATE(date) >= ? AND DATE(date) <= ? GROUP BY DATE(date)");
                $sql->execute([$from, $to]);

                $sex = $sql->fetchAll(2);

                $data = array();
                $labels = array();
                $colors = array('rgb(22, 164, 224, 1)');
                //print_r($sex);
                foreach (array_keys($sex) as $key) {
                    array_push($data, $sex[$key]['data']);
                    array_push($labels, restyle_date($sex[$key]['label'], 'd.m'));
                    //array_push($colors, $sexes_arr[$sex[$key]['sex']]['color']);
                }

                $content = str_replace('<users_count />', $users_count, $content);
                $content = str_replace('<data />', json_encode($data), $content);
                $content = str_replace('<labels />', json_encode($labels), $content);
                $content = str_replace('<label />', '"sex"', $content);
                $content = str_replace('<bg_color />', json_encode($colors), $content);
                $content = str_replace('<type />', '"line"', $content);
                $content = str_replace('<options />',
                    'scales: {
                                  yAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                            beginAtZero: true,
                                            callback: function(value) {if (value % 1 === 0) {return value;}},
                                        }
                                  }],
                                  xAxes: [{
                                        ticks: {
                                            fontColor: "rgba(235, 238, 244, 1)",
                                        }
                                  }]
                                }',
                    $content);
                $content = str_replace('<display />', 'false', $content);

                break;

        }

        break;

}