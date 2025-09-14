# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Set defaults
ENV PORT=8080
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install system deps, build tools, and enable mongodb extension
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
       git curl unzip ca-certificates \
       libssl-dev pkg-config libcurl4-openssl-dev zlib1g-dev libzip-dev \
       build-essential autoconf automake libtool \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Set Apache document root to public directory
RUN sed -ri -e "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s|/var/www/|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy composer binary from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only composer files first (cache deps) then install
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy application code
COPY . /var/www/html

# Ensure entrypoint is executable and permissions sane
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
  && chown -R www-data:www-data /var/www/html

# Enable Apache rewrite module
RUN a2enmod rewrite

# Expose default HTTP port (the entrypoint will adjust Apache to $PORT at runtime)
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]