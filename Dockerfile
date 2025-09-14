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
    && curl -fsSL https://www.mongodb.org/static/pgp/server-6.0.asc | gpg -o /usr/share/keyrings/mongodb-server-6.0.gpg --dearmor \
    && echo "deb [signed-by=/usr/share/keyrings/mongodb-server-6.0.gpg] https://repo.mongodb.org/apt/debian bookworm/mongodb-org/6.0 main" | tee /etc/apt/sources.list.d/mongodb-org-6.0.list \
    && apt-get update \
    && pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini \
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
RUN mkdir -p public && chown -R www-data:www-data public

# Generate autoload
RUN composer dump-autoload --optimize

# Create test files
RUN echo "<?php echo 'API is working! Server is running.'; phpinfo(); ?>" > public/index.php \
    && echo "<?php echo 'Docker debug: Working at ' . date('Y-m-d H:i:s'); ?>" > public/debug.php \
    && echo "<?php phpinfo(); ?>" > public/phpinfo.php \
    && echo "<?php \
    echo '<h2>MongoDB Extension Test</h2>'; \
    echo '<p>PHP Version: ' . phpversion() . '</p>'; \
    echo '<p>Server Time: ' . date('Y-m-d H:i:s') . '</p>'; \
    if (extension_loaded('mongodb')) { \
    echo '<p style=\"color: green;\">✅ MongoDB extension is loaded</p>'; \
    echo '<p>MongoDB Extension Version: ' . phpversion('mongodb') . '</p>'; \
    \$mongoUri = getenv('MONGODB_URI'); \
    echo '<p>MongoDB URI: ' . (\$mongoUri ? preg_replace('/mongodb\\+srv:\\/\\/[^@]+@/', 'mongodb+srv://****:****@', \$mongoUri) : 'Not set') . '</p>'; \
    if (\$mongoUri) { \
    try { \
    \$mongo = new MongoDB\\Client(\$mongoUri); \
    \$dbs = \$mongo->listDatabases(); \
    echo '<p style=\"color: green;\">✅ MongoDB connection successful</p>'; \
    echo '<p>Available databases:</p><ul>'; \
    foreach (\$dbs as \$db) { \
    echo '<li>' . \$db->getName() . '</li>'; \
    } \
    echo '</ul>'; \
    } catch (Exception \$e) { \
    echo '<p style=\"color: orange;\">⚠️ MongoDB connection failed: ' . htmlspecialchars(\$e->getMessage()) . '</p>'; \
    } \
    } else { \
    echo '<p style=\"color: orange;\">⚠️ MONGODB_URI environment variable not set</p>'; \
    } \
    } else { \
    echo '<p style=\"color: red;\">❌ MongoDB extension is not loaded</p>'; \
    echo '<p>Available extensions:</p><ul>'; \
    \$extensions = get_loaded_extensions(); \
    sort(\$extensions); \
    foreach (\$extensions as \$ext) { \
    echo '<li>' . \$ext . '</li>'; \
    } \
    echo '</ul>'; \
    } \
    ?>" > public/mongodb_test.php

# Set document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p storage \
    && chmod -R 775 storage

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/debug.php || exit 1

# Start Apache
CMD ["apache2-foreground"]