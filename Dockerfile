FROM php:5.6-apache

RUN apt-get update
RUN apt-get -y install mysql-client vim
RUN docker-php-ext-install pdo pdo_mysql mysql mysqli
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
ADD util/resources/xdebug.ini /usr/local/etc/php/conf.d/20-xdebug.ini
RUN sed -e "s/%XDEBUG_REMOTE_HOST%/`/sbin/ip route|awk '/default/ { print $3 }'`/" \
        -i /usr/local/etc/php/conf.d/20-xdebug.ini