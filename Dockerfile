# Use the correct PHP 8.3 Apache base image with Debian Bookworm
FROM php:8.3

# Set environment variables
ENV PORT=8080 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install dependencies and PHP extensions
RUN apt-get update \
 && apt-get -y upgrade \
 && apt-get install -y --no-install-recommends \
      unzip ca-certificates curl default-mysql-client \
 && docker-php-ext-install pdo_mysql mysqli \
 && apt-get purge -y --auto-remove \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

 # Install mysql-client for mysqladmin
RUN apt-get update && apt-get install -y --no-install-recommends \
    default-mysql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
    
# Update Apache document root
RUN sed -ri "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/*.conf \
 && sed -ri "s|/var/www/|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies (cache layer)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Copy application code
COPY . /var/www/html

# Copy and configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
 && chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \; \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Enable Apache rewrite module
RUN a2enmod rewrite

# Expose the port defined in $PORT
EXPOSE ${PORT}

# Add healthcheck for Render
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:${PORT}/health || exit 1

# Set entrypoint and default command
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]