#!/usr/bin/env bash
set -e

# Helper: update apache listening port to $PORT (if PORT set)
update_apache_port() {
  if [ -n "$PORT" ]; then
    echo "Setting Apache to listen on port $PORT"
    # Update ports.conf
    sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf || true
    # Update virtual host (000-default.conf)
    sed -ri "s/<VirtualHost \*:([0-9]+)>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true
    # Also update other default conf files if they exist
    if [ -f /etc/apache2/sites-available/default-ssl.conf ]; then
      sed -ri "s/<VirtualHost \*:([0-9]+)>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/default-ssl.conf || true
    fi
  fi
}

# Helper: update Apache document root if needed
update_apache_document_root() {
  if [ -n "$APACHE_DOCUMENT_ROOT" ] && [ "$APACHE_DOCUMENT_ROOT" != "/var/www/html" ]; then
    echo "Setting Apache document root to $APACHE_DOCUMENT_ROOT"
    # Update configuration files if they exist
    for conf_file in /etc/apache2/sites-available/*.conf /etc/apache2/conf-available/*.conf /etc/apache2/apache2.conf; do
      [ -f "$conf_file" ] && sed -ri "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" "$conf_file" 2>/dev/null || true
    done
  fi
}

# Wait for MySQL to be reachable
wait_for_mysql() {
  local timeout=${DB_WAIT_TIMEOUT:-60}
  local start=$(date +%s)
  echo "Waiting for MySQL to be reachable (timeout ${timeout}s)..."
  
  # Check if mysqladmin is available
  if ! command -v mysqladmin &> /dev/null; then
    echo "mysqladmin not found, skipping MySQL connection check"
    return 0
  fi
  
  while true; do
    # Use shorter timeout for mysqladmin to avoid hanging
    if timeout 5 mysqladmin ping -h"$MYSQL_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --silent 2>/dev/null; then
      echo "MySQL reachable"
      return 0
    fi
    
    now=$(date +%s)
    elapsed=$((now - start))
    if [ "$elapsed" -ge "$timeout" ]; then
      echo "Timed out waiting for MySQL after ${timeout}s"
      return 1
    fi
    echo "Waiting for MySQL... (${elapsed}s elapsed)"
    sleep 2
  done
}

# Run database migrations
run_migrations() {
  if [ -f "./migrations.php" ]; then
    echo "Running migrations.php ..."
    # Allow multiple attempts for migrations in case of temporary DB issues
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
      echo "Migration attempt $attempt of $max_attempts"
      if php migrations.php; then
        echo "Migrations completed successfully"
        return 0
      else
        echo "Migration attempt $attempt failed"
        if [ $attempt -eq $max_attempts ]; then
          echo "All migration attempts failed. Continuing startup..."
          return 1
        fi
        sleep 5
        attempt=$((attempt + 1))
      fi
    done
  fi
  return 0
}

# Set file permissions
set_permissions() {
  echo "Setting file permissions..."
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
}

# Clear cache
clear_cache() {
  if [ -f "artisan" ]; then
    echo "Clearing Laravel cache..."
    php artisan cache:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
  fi
}

# Main execution
echo ">>> Container entrypoint starting"

# Update Apache document root if specified
update_apache_document_root

# Set file permissions
set_permissions

# Clear cache (if Laravel)
clear_cache

# Ensure correct apache port
update_apache_port

# Set ServerName to avoid Apache warning
if ! grep -q "ServerName" /etc/apache2/apache2.conf; then
  echo "ServerName localhost" >> /etc/apache2/apache2.conf
fi

# Try to reach MySQL (best-effort). If MYSQL_HOST missing, skip.
if [ -n "$MYSQL_HOST" ] && [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASSWORD" ]; then
  wait_for_mysql || echo "Warning: MySQL not reachable (timed out). Continuing startup..."
else
  echo "MySQL connection details not fully set — skipping DB ping"
fi

# Run PHP migrations
run_migrations || echo "Migrations completed with warnings"

# Start Apache (apache2-foreground is CMD in Dockerfile; call exec to replace shell)
if command -v apache2-foreground >/dev/null 2>&1; then
  echo "Starting Apache..."
  exec apache2-foreground
else
  PORT=${PORT:-8080}
  echo "apache2-foreground not found; starting PHP built-in server at 0.0.0.0:${PORT}"
  exec php -S 0.0.0.0:${PORT} -t public
fi