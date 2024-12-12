<?php
if (!function_exists('hi'))
    if (!empty($_SERVER['HTTP_REFERER']))
        header("Location: ".$_SERVER['HTTP_REFERER']);
    else
        header("Location: index.php");

// ------------------------------------------------------------------------------------------------ \\
// --------------------------------------------- Методы ------------------------------------------- \\
// ------------------------------------------------------------------------------------------------ \\

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['application'] = array(
    'description' => 'Регистрация/отправка заявки в лист ожидания',
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
        'mail' => array(
            'description' => 'Почта пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'MAIL' => 'Передан неверный mail.',
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['profile'] = array(
    'description' => 'Главная страница',
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['farmStart'] = array(
    'description' => 'Начать фармить',
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
        'APPLY' => 'Пользователь имеет статус apply',
        'FARMING' => 'Пользователь уже фармит',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['farmCollect'] = array(
    'description' => "Собрать {$farmCoinsPerPeriod} монеток спустя {$farmPeriod} часа фарма",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
        'APPLY' => 'Пользователь имеет статус apply',
        'TIME' => 'Сутки еще не прошли',
        'FARM' => 'Пользователь еще не фармит',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['taskStart'] = array(
    'description' => "Начать выполнение задания",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
        'id' => array(
            'description' => 'ID задачи',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
        'ID_NOT_FOUND' => 'Передан неверный id',
        'DONE' => 'Задание уже выполнено',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['taskCollect'] = array(
    'description' => "Получить награду за задание",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
        'id' => array(
            'description' => 'ID задачи',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
        'ID_NOT_FOUND' => 'Передан неверный id',
        'DONE' => 'Задание уже выполнено',
        'NOT_COMPLETE' => 'Задание не выполнено',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['referralsCollect'] = array(
    'description' => "Получить награду за рефералов",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['tapsCollect'] = array(
    'description' => "Получить награду за нажатия",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
        'duration' => array(
            'description' => 'Продолжительность сессии нажатий в секундах',
            'type' => 'text',
            'needed' => '1',
        ),
        'taps' => array(
            'description' => 'Количество нажатий за сессию',
            'type' => 'text',
            'needed' => '1',
        ),
        'scale_current' => array(
            'description' => 'Текущее значение шкалы',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
        'DURATION_UNACCEPTABLE' => 'Передано недопустимое значение duration',
        'TAPS_UNACCEPTABLE' => 'Передано недопустимое значение taps',

    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['tapsStats'] = array(
    'description' => "Получить статистику нажатий",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_taps">Текущая статистика нажатий</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
    )
);

// Выход ---------------------------------------------------------------------------------------- \\

$api_doc['dailyRewardCollect'] = array(
    'description' => "Получить награду за eжедневную активность",
    'params' => array(
        'token' => array(
            'description' => 'Токен пользователя',
            'type' => 'text',
            'needed' => '1',
        ),
    ),
    'result' => 'В случае успеха будет возвращен объект <a href="account_object_profile">Профиль</a>',
    'errors' => array(
        'TOKEN_NOT_FOUND' => 'Передан неверный token',
    )
);

// ------------------------------------------------------------------------------------------------ \\
// ----------------------------------------------- Объекты ---------------------------------------- \\
// ------------------------------------------------------------------------------------------------ \\

// Профиль ---------------------------------------------------------------------------------------- \\

$api_objects['profile'] = array(
    'description' => 'Информация о пользователе',
    'params' => array(
        'id' => array(
            'type' => 'text',
            'description' => 'ID',
        ),
        'name' => array(
            'type' => 'text',
            'description' => 'Имя',
        ),
        'tg_login' => array(
            'type' => 'text',
            'description' => 'Логин телеграм',
        ),
        'mail' => [
            'type' => 'text',
            'description' => 'Почта',
        ],
        'photo' => [
            'type' => 'text',
            'description' => 'Фотка пользователя',
        ],
        'status' => [
            'type' => 'text',
            'description' => 'Статус<br>
                apply - подал заявку<br>
                active - пользователь активен<br>
                not_active - пользователь не активен<br>
                banned - заблокирован',
        ],
        'coins' => [
            'type' => 'text',
            'description' => 'Баланс',
        ],
        'farm_start_date' => [
            'type' => 'text',
            'description' => 'Дата начала фарма. Unix. Если значение меньше 0, то пользователь не фармит в текущий момент',
        ],
        'tasks' => [
            'type' => 'text',
            'description' => 'Массив объектов <a href="account_object_task">Задание</a>',
        ],
        'invite_link' => [
            'type' => 'text',
            'description' => 'Ссылка для инвайта людей',
        ],
        'invites_left' => [
            'type' => 'text',
            'description' => 'Сколько еще людей можно пригласить',
        ],
        'referrals' => [
            'type' => 'text',
            'description' => 'Объект <a href="account_object_referrals">Рефералы</a>',
        ],
        'farm_period' => [
            'type' => 'text',
            'description' => 'Раз в сколько часов можно собрать урожай',
        ],
        'farm_coins_per_period' => [
            'type' => 'text',
            'description' => 'Сколько монеток падает за период',
        ],
        'scale_max' => [
            'type' => 'text',
            'description' => 'Максимальное значение шкалы',
        ],
        'scale_current' => [
            'type' => 'text',
            'description' => 'Текущее значение шкалы',
        ],
        'last_taps_session_msg' => [
            'type' => 'text',
            'description' => 'Сообщение о последней сессии нажатий',
        ],
        'consecutive_days' => [
            'type' => 'text',
            'description' => 'Количество последовательных дней активности',
        ],
        'is_daily_reward' => [
            'type' => 'text',
            'description' => 'Доступно ли получение ежедневной награды',
        ],
        'daily_reward' => [
            'type' => 'text',
            'description' => 'Размер ежедневной награды',
        ],
        'last_daily_reward' => [
            'type' => 'text',
            'description' => 'Время последнего получения ежедневной награды',
        ],
    )
);

// Задача ---------------------------------------------------------------------------------------- \\

$api_objects['task'] = array(
    'description' => 'Информация о задании',
    'params' => array(
        'id' => array(
            'type' => 'text',
            'description' => 'ID задания',
        ),
        'title' => array(
            'type' => 'text',
            'description' => 'Текст задания',
        ),
        'icon' => [
            'type' => 'text',
            'description' => 'Ссылка на иконку',
        ],
        'coins' => [
            'type' => 'text',
            'description' => 'Награда',
        ],
        'status' => [
            'type' => 'text',
            'description' => 'Статус<br>
                done - Задание выполнено<br>
                not_done - Задание не выполнено<br>
                collect - Задание выполнено, можно собрать монетки<br>
                ',
        ],
        'additional_data' => [
            'type' => 'text',
            'description' => 'Объект с дополнительными данными о задании<br>
                action - ссылка для перенаправления пользователя при нажатии "start"<br>
                ',
        ],
    )
);

// Рефералы ---------------------------------------------------------------------------------------- \\

$api_objects['referrals'] = array(
    'description' => 'Информация о рефералах',
    'params' => array(
        'referrals' => array(
            'type' => 'text',
            'description' => 'Массив объектов <a href="account_object_referral">Реферал</a>',
        ),
        'referrals_coins_to_collect' => [
            'type' => 'text',
            'description' => 'Сколько монеток можно собрать с рефералов',
        ],
    )
);

// Реферал ---------------------------------------------------------------------------------------- \\

$api_objects['referral'] = array(
    'description' => 'Информация о реферале',
    'params' => array(
        'id' => array(
            'type' => 'text',
            'description' => 'ID задания',
        ),
        'name' => array(
            'type' => 'text',
            'description' => 'ФИО',
        ),
        'photo' => [
            'type' => 'text',
            'description' => 'Ссылка на фотку',
        ],
        'coins' => [
            'type' => 'text',
            'description' => 'Сколько монет можно собрать с данного реферала',
        ],
        'referrals' => [
            'type' => 'text',
            'description' => 'Количество пользователей, приглашенных данным рефералом',
        ],
    )
);

// Нажатия ---------------------------------------------------------------------------------------- \\

$api_objects['taps'] = array(
    'description' => 'Информация о нажатиях',
    'params' => array(
        'total' => array(
            'type' => 'text',
            'description' => 'Total',
        ),
        'total_touches' => array(
            'type' => 'text',
            'description' => 'Total Touches',
        ),
        'total_players' => array(
            'type' => 'text',
            'description' => 'Total Players',
        ),
        'daily_users' => [
            'type' => 'text',
            'description' => 'Daily Users',
        ],
        'online_players' => [
            'type' => 'text',
            'description' => 'Online Players',
        ],
    )
);

