FROM wordpress:php8.3-fpm-alpine

RUN apk add --no-cache bash curl

COPY docker/wordpress/php.ini /usr/local/etc/php/conf.d/custom.ini

COPY src/wp-content/plugins /var/www/html/wp-content/plugins
COPY src/wp-content/themes /var/www/html/wp-content/themes
COPY src/wp-content/mu-plugins /var/www/html/wp-content/mu-plugins

RUN chown -R www-data:www-data /var/www/html
