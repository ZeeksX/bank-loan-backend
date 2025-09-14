# Use official PHP 8.3 Apache image
FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Set ServerName to suppress Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install system dependencies and PHP build tools for PECL
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
    php-pear \
    autoconf \
    pkg-config \
    libssl-dev \
    && curl -fsSL https://www.mongodb.org/static/pgp/server-6.0.asc | gpg -o /usr/share/keyrings/mongodb-server-6.0.gpg --dearmor \
    && echo "deb [signed-by=/usr/share/keyrings/mongodb-server-6.0.gpg] https://repo.mongodb.org/apt/debian bookworm/mongodb-org/6.0 main" | tee /etc/apt/sources.list.d/mongodb-org-6.0.list \
    && apt-get update && apt-get install -y mongodb-mongosh \
    # Install MongoDB PHP extension
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    # Enable GD with JPEG/Freetype
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        gd \
        sockets \
    # Enable Apache mod_rewrite
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Create public directory and set permissions
RUN mkdir -p public \
    && chown -R www-data:www-data public

# Generate optimized autoload
RUN composer dump-autoload --optimize

# Create index.php if it doesn't exist
RUN if [ ! -f "public/index.php" ]; then \
    echo "<?php echo 'API is working! Server is running.'; phpinfo(); ?>" > public/index.php; \
    fi

# Set document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create storage directory
RUN mkdir -p storage && chmod -R 775 storage

# Debug file
RUN echo "<?php echo 'Docker debug: Working at ' . date('Y-m-d H:i:s'); ?>" > public/debug.php

# MongoDB test script
RUN echo "<?php \
    require 'vendor/autoload.php'; \
    echo '<h2>MongoDB Extension Test</h2>'; \
    if (extension_loaded('mongodb')) { \
        echo '<p style=\"color: green;\">✅ MongoDB extension is loaded</p>'; \
        try { \
            \$mongo = new MongoDB\Client(getenv('MONGODB_URI')); \
            \$dbs = \$mongo->listDatabases(); \
            echo '<p style=\"color: green;\">✅ MongoDB connection successful</p>'; \
        } catch (Exception \$e) { \
            echo '<p style=\"color: orange;\">⚠️ MongoDB connection failed: ' . htmlspecialchars(\$e->getMessage()) . '</p>'; \
        } \
    } else { \
        echo '<p style=\"color: red;\">❌ MongoDB extension is not loaded</p>'; \
    } \
    phpinfo(INFO_MODULES); \
?>" > public/mongodb_test.php

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/debug.php || exit 1

# Start Apache
CMD ["apache2-foreground"]