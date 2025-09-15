# Use the official PHP 8.3 image that includes Apache
FROM php:8.3-apache

ENV PORT=8080 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install system packages and PHP extensions
RUN apt-get update \
 && apt-get -y upgrade \
 && apt-get install -y --no-install-recommends \
      unzip ca-certificates curl default-mysql-client \
      libzip-dev libssl-dev \
 && docker-php-ext-install pdo_mysql mysqli zip \
 && apt-get purge -y --auto-remove \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Update Apache document root (vhosts exist in this image)
RUN sed -ri "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/*.conf \
 && sed -ri "s|/var/www/|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer install (cacheable layer)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev \
 && rm -rf ~/.composer/cache

# Copy application
COPY . /var/www/html

# Create app directories & set permissions (Laravel-like)
RUN mkdir -p storage/framework/{sessions,views,cache} storage/app/public bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache /var/www/html \
 && chmod -R 775 storage bootstrap/cache || true

# Entrypoint & permissions
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
 && chown -R www-data:www-data /var/www/html

# Enable rewrite (available in this base image)
RUN a2enmod rewrite

# Expose port — entrypoint will update Apache to listen on $PORT if you set it
EXPOSE ${PORT}

# Healthcheck — ensure the path exists in your app or adjust it
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:${PORT}/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
