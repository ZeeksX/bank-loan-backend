# Use official PHP Apache image
FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

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

# Install PHP dependencies (if composer files exist)
RUN if [ -f "composer.json" ]; then \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

# Copy application code
COPY . .

# Install PHP dependencies and generate autoload
RUN if [ -f "composer.json" ]; then \
    composer dump-autoload --optimize; \
    fi

# Set document root to public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create custom Apache configuration
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create storage directory if it doesn't exist and set permissions
RUN if [ ! -d "storage" ]; then \
    mkdir -p storage; \
    fi && \
    chmod -R 775 storage

# Create entrypoint script
RUN echo '#!/bin/bash\n\
\n\
# Wait for MySQL to be ready (if DB_HOST is set)\n\
if [ ! -z "$DB_HOST" ]; then\n\
    echo "Waiting for MySQL to be ready..."\n\
    while ! php -r "new PDO(\"mysql:host=$DB_HOST;dbname=$DB_DATABASE\", \"$DB_USERNAME\", \"$DB_PASSWORD\");" 2>/dev/null; do\n\
        echo "MySQL is unavailable - sleeping"\n\
        sleep 1\n\
    done\n\
    echo "MySQL is up - executing migrations"\n\
    \n\
    # Run migrations if migrations.php exists\n\
    if [ -f "migrations.php" ]; then\n\
        php migrations.php\n\
    fi\n\
fi\n\
\n\
# Start Apache in foreground\n\
exec apache2-foreground' > /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Use entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]