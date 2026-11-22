# راه‌اندازی (سایت PHP + ربات Pyrogram در یک سرویس)

1. همه‌ی فایل‌ها (به‌جز `.env`) را در ریشه‌ی ریپو GitHub بگذار و در Railway دیپلوی کن.
2. یک MySQL در همان پروژه اضافه کن و متغیرهای `MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD` را به سرویس وب وصل کن.
3. در Variables سرویس وب این‌ها را بگذار:
   - `TELEGRAM_BOT_TOKEN` (از BotFather)
   - `TELEGRAM_API_ID` و `TELEGRAM_API_HASH` (از https://my.telegram.org → API development tools)
   - `AVALAI_API_KEY` و در صورت نیاز `AVALAI_MODEL`
4. دامنه‌ی عمومی بساز (برای سایت). Webhook لازم نیست؛ Pyrogram خودش به تلگرام وصل می‌شود.
5. تست: `https://دامنه/health.php?token=توکن_ربات`
