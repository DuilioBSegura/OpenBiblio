#!/bin/sh
set -e

mkdir -p /var/www/html/uploads/capas
chown -R www-data:www-data /var/www/html/uploads
chmod -R 755 /var/www/html/uploads

exec apache2-foreground
