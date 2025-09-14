#!/bin/bash
# File: docker-start.sh

# Wait for MongoDB to be ready
echo "Waiting for MongoDB to start..."
while ! nc -z mongodb 27017; do
  sleep 1
done

echo "MongoDB is up - running migrations..."

# Run database migrations
php migrations.php

# Start Apache
apache2-foreground