#!/bin/bash
set -e

PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "Waiting for MySQL..."
for i in $(seq 1 40); do
  if php /var/www/html/bin/migrate.php; then
    echo "Database ready."
    break
  fi
  echo "Retry $i/40..."
  sleep 3
done

exec apache2-foreground
