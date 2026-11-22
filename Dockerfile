FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql curl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY railway-start.sh /usr/local/bin/railway-start
RUN chmod +x /usr/local/bin/railway-start

EXPOSE 8080
CMD ["/usr/local/bin/railway-start"]
