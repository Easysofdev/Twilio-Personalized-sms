web: vendor/bin/heroku-php-apache2 public/
worker: php artisan queue:restart && php artisan queue:work database --tries=5 --queue=high,default
supervisor: supervisord -c supervisord.conf -n
