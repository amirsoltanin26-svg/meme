<?php
/**
 * ثبت خودکار webhook تلگرام.
 * بعد از دیپلوی، این آدرس را یک‌بار در مرورگر باز کن:
 *   https://دامنه-تو/set_webhook.php?token=توکن_ربات
 * (فقط کسی که توکن ربات را دارد می‌تواند از آن استفاده کند)
 */
require_once __DIR__ . "/telegram_config.php";
header("Content-Type: text/plain; charset=utf-8");

if (TELEGRAM_BOT_TOKEN === '') {
    exit("TELEGRAM_BOT_TOKEN در Variables سرویس تنظیم نشده است.");
}
if (!hash_equals(TELEGRAM_BOT_TOKEN, (string)($_GET["token"] ?? ""))) {
    http_response_code(403);
    exit("توکن نادرست است.");
}

$host = $_SERVER["HTTP_X_FORWARDED_HOST"] ?? $_SERVER["HTTP_HOST"] ?? "";
$url = "https://" . $host . "/telegram_webhook.php";

$ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        "url" => $url,
        "secret_token" => TELEGRAM_WEBHOOK_SECRET,
        "allowed_updates" => json_encode(["message"]),
        "drop_pending_updates" => "true",
    ],
    CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch);
echo "Webhook URL: $url\n\n";
echo $res === false ? "خطا: " . curl_error($ch) : $res;
curl_close($ch);
