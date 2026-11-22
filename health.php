<?php
/**
 * صفحه‌ی تشخیص مشکل. در مرورگر باز کن:
 *   https://دامنه-تو/health.php?token=توکن_ربات
 */
require_once __DIR__ . "/telegram_config.php";
header("Content-Type: text/plain; charset=utf-8");

if (TELEGRAM_BOT_TOKEN === '' || !hash_equals(TELEGRAM_BOT_TOKEN, (string)($_GET["token"] ?? ""))) {
    http_response_code(403);
    exit("توکن نادرست است (یا TELEGRAM_BOT_TOKEN ست نشده).");
}

function line(string $label, bool $ok, string $detail = ''): void {
    echo ($ok ? "[OK]   " : "[FAIL] ") . $label . ($detail !== '' ? " -> " . $detail : "") . "\n";
}

// 1) دیتابیس
try {
    require __DIR__ . "/config.php";
    $n = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    line("اتصال دیتابیس", true, "$n جدول");
} catch (Throwable $e) {
    line("اتصال دیتابیس", false, $e->getMessage());
}

// 2) متغیرها
require_once __DIR__ . "/ai_config.php";
line("AVALAI_API_KEY ست شده", $OPENAI_API_KEY !== '');
echo "      مدل: $OPENAI_MODEL | آدرس: $OPENAI_BASE_URL\n";

// 3) تست واقعی API هوش مصنوعی
if ($OPENAI_API_KEY !== '') {
    $ch = curl_init($OPENAI_BASE_URL . "/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json", "Authorization: Bearer " . trim($OPENAI_API_KEY)],
        CURLOPT_POSTFIELDS => json_encode(["model" => $OPENAI_MODEL, "messages" => [["role" => "user", "content" => "say hi"]], "max_tokens" => 20]),
        CURLOPT_TIMEOUT => 40,
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        line("تماس با AvalAI", false, $err);
    } else {
        $j = json_decode($raw, true);
        $ok = $code >= 200 && $code < 300 && !empty($j["choices"][0]["message"]["content"]);
        line("تماس با AvalAI (HTTP $code)", $ok, $ok ? "پاسخ دریافت شد" : substr($raw, 0, 300));
        if (!$ok) {
            echo "      اگر خطا درباره‌ی model است، AVALAI_MODEL را روی مدل فعال حساب خودت بگذار.\n";
        }
    }
}

// 4) تلگرام (Pyrogram)
line("TELEGRAM_API_ID ست شده", (getenv('TELEGRAM_API_ID') ?: '') !== '');
line("TELEGRAM_API_HASH ست شده", (getenv('TELEGRAM_API_HASH') ?: '') !== '');
$me = json_decode((string)@file_get_contents("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getMe"), true);
line("توکن ربات تلگرام", !empty($me["ok"]), !empty($me["ok"]) ? "@" . $me["result"]["username"] : "توکن نامعتبر");
echo "\nاگر همه OK هستند ولی ربات جواب نمی‌دهد، لاگ سرویس در Railway را ببین (خطوط [bot]).\n";
