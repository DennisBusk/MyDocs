# set pwd to the root of the project
#cd /home/dennisb/mydocs.dennisbusk.dk/httpdocs
/home/dennisb/mydocs.dennisbusk.dk/php artisan down
git pull origin master
/home/dennisb/mydocs.dennisbusk.dk/composer install --no-interaction --prefer-dist --optimize-autoloader
/home/dennisb/mydocs.dennisbusk.dk/php artisan config:cache
/home/dennisb/mydocs.dennisbusk.dk/php artisan route:cache
/home/dennisb/mydocs.dennisbusk.dk/php artisan view:cache
/home/dennisb/mydocs.dennisbusk.dk/php artisan event:cache
/home/dennisb/mydocs.dennisbusk.dk/php artisan migrate --force
/home/dennisb/mydocs.dennisbusk.dk/php artisan queue:restart
/home/dennisb/mydocs.dennisbusk.dk/php artisan up
