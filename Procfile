web: vendor/bin/heroku-php-apache2 public/ node --max_old_space_size=2560
worker: php artisan queue:restart && php artisan queue:work database --tries=5 --queue=high,default
