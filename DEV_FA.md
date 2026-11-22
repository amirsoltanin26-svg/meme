# راهنمای توسعه (چت‌بات وب + ربات تلگرام)

## ساختار پروژه
| فایل | نقش |
|---|---|
| `telegram_webhook.php` | دریافت Update از تلگرام، دستورات `/start CODE`، `/new`، `/help`، ذخیره پیام‌ها و پاسخ AI |
| `telegram_link.php` | ساخت کد یک‌بارمصرف ۱۰ دقیقه‌ای برای اتصال حساب سایت به تلگرام |
| `telegram_config.php` | توکن و secret از Environment |
| `ai.php` / `ai_config.php` | فراخوانی `chat/completions` در AvalAI با کل تاریخچه چت |
| `chat.php`, `login.php`, `register.php`, `index.html` | وب‌اپ (سشن PHP) |
| `config.php` | اتصال PDO به MySQL (`MYSQL*` یا `DB_*`) |
| `schema.sql` | جدول‌های users/chats/messages (استخراج‌شده از کد) |
| `telegram_migration.sql` | `telegram_links` و `telegram_link_codes` |

جریان: کاربر در سایت کد می‌گیرد ← `/start CODE` در ربات ← ردیف در `telegram_links` ← هر پیام متنی در همان `chats/messages` سایت ذخیره می‌شود و `askAI()` پاسخ می‌دهد.

## اجرای محلی
```bash
cp .env.example .env        # کلیدها را پر کن
docker compose up --build   # سایت: http://localhost:8080
```
تلگرام فقط به HTTPS عمومی webhook می‌فرستد؛ برای تست محلی تونل بزن:
```bash
cloudflared tunnel --url http://localhost:8080     # یا ngrok http 8080
scripts/webhook.sh set https://<آدرس-تونل>
scripts/webhook.sh info
```
لاگ خطاها: `docker compose logs -f web`

## دیپلوی Railway
مطابق `RAILWAY_SETUP_FA.md`. اگر دیتابیس Railway خالی است، اول `schema.sql` و بعد `telegram_migration.sql` را اجرا کن.

## کارهایی که انجام شده
- Dedupe: Updateهای تکراری تلگرام (با `update_id`) دوباره پردازش نمی‌شوند.
- Secret webhook همیشه فعال است (خودکار از توکن ساخته می‌شود؛ یا با `TELEGRAM_WEBHOOK_SECRET` دستی).
- نشانگر «در حال تایپ»، دستور `/unlink`، فقط ۳۰ پیام آخر به AI می‌رود.
- CSRF در `chat.php` و `telegram_link.php`، `session_regenerate_id` بعد از ورود، و عدم نمایش جزئیات خطا به کاربر.
- جدول‌ها خودکار ساخته می‌شوند (`db_init.php`)، و `set_webhook.php` ثبت webhook را با یک کلیک انجام می‌دهد.
- Dockerfile: `AllowOverride All` تا `.htaccess` کار کند؛ `.htaccess` فایل‌های حساس را می‌بندد؛ باگ پیام خالی در `chat.php` رفع شد.

## پیشنهاد بعدی
`/chats` برای انتخاب گفتگو، پشتیبانی عکس/ویس، استریم پاسخ، CSRF برای فرم ورود/ثبت‌نام (الان `index.html` استاتیک است).
