#!/bin/bash
# file: docker-entrypoint.sh
# Only wait for MongoDB if using local MongoDB
# Check if MONGODB_URI contains localhost or mongodb (local container)
if echo "$MONGODB_URI" | grep -q "mongodb://localhost\|mongodb://mongodb"; then
    echo "Waiting for MongoDB to start..."
    while ! nc -z mongodb 27017; do
        sleep 1
    done
    echo "MongoDB is up!"
else
    echo "Using remote MongoDB, skipping wait..."
fi

# Run database migrations if available
if [ -f "migrations.php" ]; then
    echo "Running database migrations..."
    php migrations.php
else
    echo "No migrations.php found, skipping migrations"
fi

# Start Apache in the foreground
echo "Starting Apache server..."
exec apache2-foreground