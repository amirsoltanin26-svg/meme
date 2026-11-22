FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev python3 python3-venv \
    && docker-php-ext-install pdo_mysql curl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# محیط پایتون برای ربات Pyrogram
COPY bot/requirements.txt /tmp/requirements.txt
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --no-cache-dir -r /tmp/requirements.txt

WORKDIR /var/www/html
COPY . /var/www/html/
RUN mkdir -p /opt/bot && cp /var/www/html/bot/bot.py /opt/bot/bot.py && rm -rf /var/www/html/bot
COPY apache-security.conf /etc/apache2/conf-available/security-files.conf
RUN a2enconf security-files && chown -R www-data:www-data /var/www/html

COPY railway-start.sh /usr/local/bin/railway-start
RUN chmod +x /usr/local/bin/railway-start

EXPOSE 8080
CMD ["/usr/local/bin/railway-start"]
