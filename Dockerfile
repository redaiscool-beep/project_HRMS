FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# ใส่เฉพาะไฟล์ที่ระบบใช้ (ไม่รวม .env, เอกสาร, วิดีโอ)
COPY config.php /var/www/html/config.php
COPY includes/ /var/www/html/includes/
COPY assets/ /var/www/html/assets/
COPY public/ /var/www/html/public/
RUN rm -rf /var/www/html/public/pic /var/www/html/public/icon

COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# Render กำหนด port ผ่านตัวแปร PORT
CMD sed -i "s/^Listen 80$/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && exec apache2-foreground
