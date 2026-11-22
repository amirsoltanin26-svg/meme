<?php
declare(strict_types=1);

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/ai.php";
require_once __DIR__ . "/telegram_config.php";

header("Content-Type: application/json; charset=utf-8");

function tgRequest(string $method, array $data): array
{
    if (!TELEGRAM_BOT_TOKEN) {
        throw new RuntimeException("TELEGRAM_BOT_TOKEN تنظیم نشده است.");
    }
    $ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/" . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 25
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($error);
    }
    curl_close($ch);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function tgSend(int|string $chatId, string $text): void
{
    // تلگرام برای هر پیام متنی محدودیت طول دارد.
    $chunks = preg_split('/(?<=\n)/u', $text) ?: [$text];
    $buffer = '';
    foreach ($chunks as $chunk) {
        if ((function_exists('mb_strlen') ? mb_strlen($buffer . $chunk, 'UTF-8') : strlen($buffer . $chunk)) > 3900 && $buffer !== '') {
            tgRequest("sendMessage", ["chat_id" => $chatId, "text" => $buffer]);
            $buffer = '';
        }
        $buffer .= $chunk;
    }
    if ($buffer !== '') {
        tgRequest("sendMessage", ["chat_id" => $chatId, "text" => $buffer]);
    }
}

function createChat(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("INSERT INTO chats (user_id, title) VALUES (?, ?)");
    $stmt->execute([$userId, "New Chat"]);
    return (int)$pdo->lastInsertId();
}

try {
    if (TELEGRAM_WEBHOOK_SECRET !== '') {
        $received = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        if (!hash_equals(TELEGRAM_WEBHOOK_SECRET, $received)) {
            http_response_code(403);
            echo json_encode(["ok" => false]);
            exit;
        }
    }

    $update = json_decode(file_get_contents("php://input"), true) ?: [];
    $message = $update["message"] ?? null;
    if (!$message || !isset($message["chat"]["id"], $message["from"]["id"])) {
        echo json_encode(["ok" => true]);
        exit;
    }

    $telegramChatId = (int)$message["chat"]["id"];
    $telegramUserId = (int)$message["from"]["id"];
    $text = trim((string)($message["text"] ?? ''));

    if ($text === '') {
        tgSend($telegramChatId, "فعلاً فقط پیام متنی پشتیبانی می‌شود.");
        echo json_encode(["ok" => true]); exit;
    }

    // دستور اتصال حساب سایت: /start CODE
    if (preg_match('/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]{6,80}))?$/u', $text, $match)) {
        $code = $match[1] ?? '';
        if ($code !== '') {
            $hash = hash('sha256', $code);
            $stmt = $pdo->prepare(
                "SELECT user_id FROM telegram_link_codes WHERE code_hash = ? AND expires_at > NOW() LIMIT 1"
            );
            $stmt->execute([$hash]);
            $linkCode = $stmt->fetch();
            if (!$linkCode) {
                tgSend($telegramChatId, "کد اتصال نامعتبر یا منقضی شده است. از داخل سایت کد جدید بساز.");
            } else {
                $userId = (int)$linkCode["user_id"];
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    "INSERT INTO telegram_links (telegram_user_id, telegram_chat_id, user_id, active_chat_id)
                     VALUES (?, ?, ?, NULL)
                     ON DUPLICATE KEY UPDATE telegram_chat_id = VALUES(telegram_chat_id), user_id = VALUES(user_id), active_chat_id = NULL"
                );
                $stmt->execute([$telegramUserId, $telegramChatId, $userId]);
                $pdo->prepare("DELETE FROM telegram_link_codes WHERE code_hash = ?")->execute([$hash]);
                $pdo->commit();
                tgSend($telegramChatId, "حساب سایت با موفقیت به تلگرام متصل شد.\nاز این به بعد پیام‌هایت در همان حساب و دیتابیس ذخیره می‌شوند.\nبرای گفت‌وگوی جدید /new را بفرست.");
            }
        } else {
            tgSend($telegramChatId, "سلام! برای اتصال، ابتدا وارد سایت شو و از بخش «اتصال تلگرام» کد بگیر؛ سپس این دستور را همراه کد بفرست:\n/start YOUR_CODE");
        }
        echo json_encode(["ok" => true]); exit;
    }

    $stmt = $pdo->prepare("SELECT user_id, active_chat_id FROM telegram_links WHERE telegram_user_id = ? LIMIT 1");
    $stmt->execute([$telegramUserId]);
    $link = $stmt->fetch();
    if (!$link) {
        tgSend($telegramChatId, "برای استفاده ابتدا حساب سایتت را متصل کن.\n/start");
        echo json_encode(["ok" => true]); exit;
    }
    $userId = (int)$link["user_id"];

    if (preg_match('/^\/help(?:@\w+)?$/u', $text)) {
        tgSend($telegramChatId, "راهنما:\n/new - شروع گفت‌وگوی جدید\n/help - نمایش راهنما\nهر پیام متنی دیگری را بفرستی، هوش مصنوعی پاسخ می‌دهد.");
        echo json_encode(["ok" => true]); exit;
    }

    $activeChatId = (int)($link["active_chat_id"] ?? 0);
    if (preg_match('/^\/new(?:@\w+)?$/u', $text)) {
        $newId = createChat($pdo, $userId);
        $stmt = $pdo->prepare("UPDATE telegram_links SET active_chat_id = ? WHERE telegram_user_id = ?");
        $stmt->execute([$newId, $telegramUserId]);
        tgSend($telegramChatId, "گفت‌وگوی جدید ساخته شد.");
        echo json_encode(["ok" => true]); exit;
    }

    if ($activeChatId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$activeChatId, $userId]);
        if (!$stmt->fetch()) $activeChatId = 0;
    }
    if ($activeChatId <= 0) {
        $stmt = $pdo->prepare("SELECT id FROM chats WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $latest = $stmt->fetch();
        $activeChatId = $latest ? (int)$latest["id"] : createChat($pdo, $userId);
        $stmt = $pdo->prepare("UPDATE telegram_links SET active_chat_id = ? WHERE telegram_user_id = ?");
        $stmt->execute([$activeChatId, $telegramUserId]);
    }

    // پیام کاربر را ذخیره کن؛ askAI تاریخچه مشترک همین گفتگو را می‌خواند.
    $stmt = $pdo->prepare("INSERT INTO messages (chat_id, role, content) VALUES (?, 'user', ?)");
    $stmt->execute([$activeChatId, $text]);

    $stmt = $pdo->prepare("SELECT title FROM chats WHERE id = ? AND user_id = ?");
    $stmt->execute([$activeChatId, $userId]);
    $chat = $stmt->fetch();
    if ($chat && $chat["title"] === "New Chat") {
        $title = function_exists('mb_substr') ? mb_substr($text, 0, 40) : substr($text, 0, 40);
        $stmt = $pdo->prepare("UPDATE chats SET title = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $activeChatId, $userId]);
    }

    try {
        $answer = askAI($pdo, $activeChatId, $text);
    } catch (Throwable $e) {
        error_log("Telegram AI error: " . $e->getMessage());
        $answer = "در ارتباط با هوش مصنوعی مشکلی پیش آمد. لطفاً کمی بعد دوباره تلاش کن.";
    }

    $stmt = $pdo->prepare("INSERT INTO messages (chat_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt->execute([$activeChatId, $answer]);
    $stmt = $pdo->prepare("UPDATE chats SET updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    $stmt->execute([$activeChatId, $userId]);

    tgSend($telegramChatId, $answer);
    echo json_encode(["ok" => true]);
} catch (Throwable $e) {
    error_log("Telegram webhook error: " . $e->getMessage());
    http_response_code(200);
    echo json_encode(["ok" => true]);
}
