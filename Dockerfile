# Use the PHP 8.3 + Apache Bookworm image
FROM php:8.3

ENV PORT=8080 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install dependencies and PHP extensions
RUN apt-get update \
 && apt-get -y upgrade \
 && apt-get install -y --no-install-recommends \
      unzip ca-certificates curl default-mysql-client \
      libzip-dev libssl-dev \
 && docker-php-ext-install pdo_mysql mysqli zip \
 && apt-get purge -y --auto-remove \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y apache2 libapache2-mod-php \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
COPY . /var/www/html

# Create storage directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} storage/app/public bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Entrypoint & permissions
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
 && chown -R www-data:www-data /var/www/html

EXPOSE ${PORT}

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:${PORT} || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]