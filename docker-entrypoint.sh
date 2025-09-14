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

# Wait for DB ping using PHP Mongo client (works with Atlas MONGODB_URI or local mongodb host)
wait_for_mongo() {
  local timeout=${DB_WAIT_TIMEOUT:-60}
  local start=$(date +%s)
  echo "Waiting for MongoDB to be reachable (timeout ${timeout}s)..."
  while true; do
    php -r '
      require __DIR__ . "/vendor/autoload.php";
      $uri = getenv("MONGODB_URI");
      $db = getenv("DB_DATABASE") ?: "admin";
      if (!$uri) { exit(1); }
      try {
          $c = new MongoDB\Client($uri, ["serverSelectionTimeoutMS" => 3000]);
          $c->selectDatabase($db)->command(["ping" => 1]);
          exit(0);
      } catch (Exception $e) {
          // non-zero
          exit(1);
      }
    '
    if [ $? -eq 0 ]; then
      echo "MongoDB reachable"
      return 0
    fi
    now=$(date +%s)
    elapsed=$((now - start))
    if [ "$elapsed" -ge "$timeout" ]; then
      echo "Timed out waiting for MongoDB after ${timeout}s"
      return 1
    fi
    sleep 1
  done
}

# Run setup
echo ">>> container entrypoint starting"

# Ensure correct apache port
update_apache_port

# Try to reach MongoDB (best-effort). If MONGODB_URI missing, skip.
if [ -n "$MONGODB_URI" ]; then
  wait_for_mongo || echo "Warning: MongoDB not reachable (timed out). Continuing startup..."
else
  echo "MONGODB_URI not set — skipping DB ping (ensure you have a DB or MONGODB_URI set)"
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
