FROM php:8-apache
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN a2enmod rewrite
COPY . /var/www
WORKDIR /var/www
RUN mkdir /tmp/php-sessions && chown www-data:www-data /tmp/php-sessions
RUN echo "session.save_path=/tmp/php-sessions\nsession.cookie_samesite=None\nsession.cookie_secure=1\nsession.cookie_samesite=None\n" > /usr/local/etc/php/conf.d/docker-php-ext-sessions.ini
RUN rm -rf html
RUN composer install
RUN ln -s /var/www/public /var/www/html
