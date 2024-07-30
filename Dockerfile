FROM php:8-apache
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN a2enmod rewrite
COPY . /var/www
WORKDIR /var/www
RUN rm -rf html
RUN composer install
RUN ln -s /var/www/public /var/www/html
