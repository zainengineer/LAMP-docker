FROM php:5.6-apache

RUN apt-get update
RUN apt-get -y install mysql-client vim
RUN apt-get -y install libmcrypt-dev
RUN docker-php-ext-install mcrypt
RUN apt-get -y install libpng-dev
RUN docker-php-ext-install gd
RUN apt-get -y install libxml2-dev
RUN docker-php-ext-install soap
RUN docker-php-ext-install pdo pdo_mysql mysql mysqli
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install opcache
RUN docker-php-ext-install mbstring
RUN a2enmod rewrite
RUN a2enmod ssl headers deflate expires mime
RUN apt-get -y install aria2
ADD util/resources/xdebug.ini /usr/local/etc/php/conf.d/20-xdebug.ini
RUN sed -e "s/%XDEBUG_REMOTE_HOST%/`/sbin/ip route|awk '/default/ { print $3 }'`/" \
        -i /usr/local/etc/php/conf.d/20-xdebug.ini
