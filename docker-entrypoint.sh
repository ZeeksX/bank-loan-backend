#!/usr/bin/env bash
set -e

# Helper: update apache listening port to $PORT (if PORT set)
update_apache_port() {
  if [ -n "$PORT" ]; then
    echo "Setting Apache to listen on port $PORT"
    # Update ports.conf
    sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf || true
    # Update virtual host (000-default.conf)
    sed -ri "s/<VirtualHost \*:([0-9]+)>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true
  fi
}

# Wait for MySQL to be reachable
wait_for_mysql() {
  local timeout=${DB_WAIT_TIMEOUT:-60}
  local start=$(date +%s)
  echo "Waiting for MySQL to be reachable (timeout ${timeout}s)..."
  while true; do
    mysqladmin ping -h"$MYSQL_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --silent
    if [ $? -eq 0 ]; then
      echo "MySQL reachable"
      return 0
    fi
    now=$(date +%s)
    elapsed=$((now - start))
    if [ "$elapsed" -ge "$timeout" ]; then
      echo "Timed out waiting for MySQL after ${timeout}s"
      return 1
    fi
    sleep 1
  done
}

# Run setup
echo ">>> container entrypoint starting"

# Ensure correct apache port
update_apache_port

# Set ServerName to avoid Apache warning
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Try to reach MySQL (best-effort). If MYSQL_HOST missing, skip.
if [ -n "$MYSQL_HOST" ]; then
  wait_for_mysql || echo "Warning: MySQL not reachable (timed out). Continuing startup..."
else
  echo "MYSQL_HOST not set — skipping DB ping (ensure you have a DB or MYSQL_HOST set)"
fi

# Run PHP migrations (do not fail the container on migration warnings)
if [ -f "./migrations.php" ]; then
  echo "Running migrations.php ..."
  php migrations.php || echo "migrations finished with warnings"
fi

# Start Apache (apache2-foreground is CMD in Dockerfile; call exec to replace shell)
if command -v apache2-foreground >/dev/null 2>&1; then
  echo "Starting Apache..."
  exec apache2-foreground
else
  PORT=${PORT:-8080}
  echo "apache2-foreground not found; starting PHP built-in at 0.0.0.0:${PORT}"
  exec php -S 0.0.0.0:${PORT} -t public
fi