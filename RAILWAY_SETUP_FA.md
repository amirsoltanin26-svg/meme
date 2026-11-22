# راه‌اندازی روی Railway

## 1) سرویس وب
این پوشه را به GitHub متصل کن و در Railway به عنوان یک Service دیپلوی کن. وجود Dockerfile باعث می‌شود Railway از Dockerfile استفاده کند و دیگر نیازی به تشخیص خودکار PHP/Railpack نیست.

## 2) دیتابیس
یک MySQL Service جداگانه در همان Railway Project بساز. متغیرهای MySQL سرویس را به Web Service ارجاع بده یا این متغیرها را در Web Service تنظیم کن:
- MYSQLHOST
- MYSQLPORT
- MYSQLDATABASE
- MYSQLUSER
- MYSQLPASSWORD

سپس schema اصلی سایت و فایل telegram_migration.sql را روی همین دیتابیس اجرا کن. فایل migration تلگرام جایگزین ساختار اصلی users/chats/messages نیست.

## 3) متغیرهای وب‌سرویس
در Variables سرویس وب تنظیم کن:
- AVALAI_API_KEY = کلید جدید AvalAI
- AVALAI_MODEL = مدل فعال حساب AvalAI (در صورت نیاز)
- TELEGRAM_BOT_TOKEN = توکن ربات
- TELEGRAM_WEBHOOK_SECRET = یک مقدار تصادفی محرمانه (اختیاری ولی توصیه‌شده)

کلید API قدیمی که قبلاً داخل فایل اولیه بوده را تعویض کن؛ آن کلید را دوباره استفاده نکن.

## 4) پورت و دامنه
Dockerfile و railway-start.sh وب‌سرور Apache را روی مقدار PORT که Railway تزریق می‌کند اجرا می‌کنند. بعد از Deploy دامنه عمومی Railway را فعال کن.

## 5) Webhook تلگرام
بعد از فعال شدن دامنه HTTPS، Webhook را روی آدرس زیر تنظیم کن:
https://YOUR-DOMAIN/telegram_webhook.php
اگر TELEGRAM_WEBHOOK_SECRET تنظیم کرده‌ای، هنگام ثبت Webhook همان مقدار را به عنوان secret_token بفرست.

## نکته
قبل از اجرای سایت، دیتابیس اصلی باید شامل جدول‌های users، chats و messages باشد. ساختار اصلی دیتابیس در این ZIP وجود ندارد؛ آن را از دیتابیس فعلی سایت Export و به MySQL Railway Import کن.
