param(
  [string]$Target = "root@202.10.37.19"
)
$remote = @"
cd /var/www/backend &&
git pull &&
composer install --no-dev --optimize-autoloader &&
php artisan optimize:clear &&
php artisan config:clear &&
php artisan route:clear &&
php artisan view:clear &&
php artisan migrate --force &&
chown -R www-data:www-data storage bootstrap/cache &&
chmod -R 775 storage bootstrap/cache &&
php -v || true &&
systemctl reload php-fpm || true
"@
ssh $Target $remote
