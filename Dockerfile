FROM php:8.3-apache-bookworm-slim

ENV PORT=8080 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
 && apt-get -y upgrade \
 && apt-get install -y --no-install-recommends \
      libssl-dev libzip-dev unzip ca-certificates curl \
 && docker-php-ext-install pdo_mysql mysqli \
 && apt-get purge -y --auto-remove \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

RUN sed -ri "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/*.conf \
 && sed -ri "s|/var/www/|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
COPY . /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
 && chown -R www-data:www-data /var/www/html

RUN a2enmod rewrite
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
