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
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    gd \
    && a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN if [ -f "composer.json" ]; then \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

# Copy application code
COPY . .

# Create public directory if it doesn't exist
RUN mkdir -p public && chown -R www-data:www-data public

# Generate autoload
RUN if [ -f "composer.json" ]; then \
    composer dump-autoload --optimize; \
    fi

# Create a simple test file if no index.php exists
RUN if [ ! -f "public/index.php" ]; then \
    echo "<?php echo 'API is working! Server is running.'; phpinfo(); ?>" > public/index.php; \
    fi

# Set document root to public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create storage directory if it doesn't exist
RUN if [ ! -d "storage" ]; then \
    mkdir -p storage; \
    fi && \
    chmod -R 775 storage

# Create debug file
RUN echo "<?php echo 'Docker debug: Working at ' . date('Y-m-d H:i:s'); ?>" > /var/www/html/public/debug.php

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/debug.php || exit 1

# Start Apache
CMD ["apache2-foreground"]