FROM php:8.3-apache

WORKDIR /var/www/html

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    curl \
    git \
    gnupg \
    wget \
    pkg-config \
    libssl-dev \
    libcurl4-openssl-dev \
    libmongoc-dev \
    libbson-dev \
    ca-certificates \
    netcat-openbsd \
    && curl -fsSL https://www.mongodb.org/static/pgp/server-6.0.asc | gpg -o /usr/share/keyrings/mongodb-server-6.0.gpg --dearmor \
    && echo "deb [signed-by=/usr/share/keyrings/mongodb-server-6.0.gpg] https://repo.mongodb.org/apt/debian bookworm/mongodb-org/6.0 main" | tee /etc/apt/sources.list.d/mongodb-org-6.0.list \
    && apt-get update

# Install MongoDB extension
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Install other PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    gd \
    sockets \
    && a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Copy application code
COPY . .

# Create public directory and set permissions
RUN mkdir -p public storage && chown -R www-data:www-data public storage

# Generate autoload
RUN composer dump-autoload --optimize

# Create test files
RUN echo "<?php echo 'API is working! Server is running.'; ?>" > public/index.php \
    && echo "<?php echo 'Docker debug: Working at ' . date('Y-m-d H:i:s'); ?>" > public/debug.php \
    && echo "<?php phpinfo(); ?>" > public/phpinfo.php \
    && echo "<?php \
    require_once '../vendor/autoload.php'; \
    require_once '../config/database.php'; \
    header('Content-Type: application/json'); \
    try { \
        \$db = getDatabase(); \
        \$clientType = getDatabaseClientType(); \
        echo json_encode([ \
            'status' => 'success', \
            'client_type' => \$clientType, \
            'database' => 'connected', \
            'timestamp' => date('Y-m-d H:i:s') \
        ]); \
    } catch (Exception \$e) { \
        echo json_encode([ \
            'status' => 'error', \
            'message' => \$e->getMessage(), \
            'timestamp' => date('Y-m-d H:i:s') \
        ]); \
    } \
    ?>" > public/test-db.php

# Set document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage

# Copy the startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/debug.php || exit 1

# Use our startup script as the entrypoint
CMD ["docker-entrypoint.sh"]