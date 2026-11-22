# راه‌اندازی روی Railway (ساده)

1. پروژه را روی GitHub بگذار و در Railway به‌صورت Service دیپلوی کن (Dockerfile خودکار شناسایی می‌شود).
2. در همان پروژه یک **MySQL** اضافه کن. در Variables سرویس وب، متغیرهای MySQL را Reference کن:
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`
3. این‌ها را هم در Variables سرویس وب بگذار:
   - `AVALAI_API_KEY` = کلید جدید AvalAI
   - `AVALAI_MODEL` = مدل فعال حساب (اختیاری)
   - `TELEGRAM_BOT_TOKEN` = توکن ربات
4. در Settings → Networking دامنه عمومی بساز.
5. یک بار این آدرس را در مرورگر باز کن (webhook خودکار ثبت می‌شود):
   `https://دامنه-تو/set_webhook.php?token=توکن_ربات`
6. تمام. **جدول‌ها خودکار ساخته می‌شوند؛ نیازی به اجرای SQL نیست.**

> اگر قبلاً دیتابیس قدیمی سایت را داری، Import کن؛ جدول‌های موجود دست نمی‌خورند.
> کلید API قدیمی را که لو رفته عوض کن.
