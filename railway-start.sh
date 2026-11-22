#!/bin/sh
set -eu
PORT="${PORT:-8080}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s#<VirtualHost \*:80>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/000-default.conf

# ربات Pyrogram در پس‌زمینه؛ اگر قطع شد خودکار دوباره اجرا می‌شود
( while true; do
    /opt/venv/bin/python /opt/bot/bot.py || true
    echo "[start] bot exited, restarting in 5s"
    sleep 5
  done ) &

exec apache2-foreground
