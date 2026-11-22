<?php
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
// اگر secret دستی ست نشده باشد، به‌صورت خودکار از توکن ربات ساخته می‌شود (همیشه فعال).
$__tgSecret = getenv('TELEGRAM_WEBHOOK_SECRET') ?: '';
if ($__tgSecret === '' && TELEGRAM_BOT_TOKEN !== '') {
    $__tgSecret = substr(hash('sha256', 'tgsecret:' . TELEGRAM_BOT_TOKEN), 0, 48);
}
define('TELEGRAM_WEBHOOK_SECRET', $__tgSecret);
