#!/bin/bash

# Wait for MySQL to be ready (if DB_HOST is set)
if [ ! -z "$DB_HOST" ]; then
    echo "Waiting for MySQL to be ready..."
    while ! php -r "new PDO(\"mysql:host=$DB_HOST;dbname=$DB_DATABASE\", \"$DB_USERNAME\", \"$DB_PASSWORD\");" 2>/dev/null; do
        echo "MySQL is unavailable - sleeping"
        sleep 1
    done
    echo "MySQL is up - executing migrations"
    
    # Run migrations if migrations.php exists
    if [ -f "migrations.php" ]; then
        php migrations.php
    fi
fi

# Start Apache in foreground
exec apache2-foreground