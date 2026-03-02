FROM php:8.2-apache
RUN a2enmod rewrite
RUN docker-php-ext-install mysqli
RUN chown -R www-data:www-data /var/www/html
COPY . /var/www/html/

EXPOSE 80




