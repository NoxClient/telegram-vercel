<?php
/**
 * Bot handler for callback queries
 */

define('BOT_TOKEN', '8541613029:AAF9uWzlAYEJy1kNM89yQfMtIz3bh53AOo4');

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $data = $callback['data'];
    $chat_id = $callback['message']['chat']['id'];
    $message_id = $callback['message']['message_id'];
    
    if (strpos($data, 'copy_') === 0) {
        $token = substr($data, 5);
        
        // Ответ с токеном
        $answer = [
            'callback_query_id' => $callback['id'],
            'text' => "✅ Токен скопирован: " . $token,
            'show_alert' => true
        ];
        
        file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery?" . http_build_query($answer));
        
        // Редактируем сообщение
        $edit = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "🔑 Токен:\n<code>$token</code>\n\nСкопируй его и используй для входа:",
            'parse_mode' => 'HTML'
        ];
        
        file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText?" . http_build_query($edit));
    }
}

http_response_code(200);
?>