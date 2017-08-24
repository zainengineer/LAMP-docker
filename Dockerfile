FROM php:5.6-apache
#An example https://github.com/docker-library/php/issues/75#issuecomment-235773906
RUN apt-get update
RUN apt-get -y install mysql-client vim
RUN apt-get -y install libmcrypt-dev
RUN docker-php-ext-install mcrypt
RUN apt-get -y install libpng-dev
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
#RUN apt-get -y install libjpeg-turbo-dev freetype-dev
RUN apt-get -y install zlib1g-dev
RUN apt-get -y install php5-gd
RUN apt-get -y install php-pear
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng12-dev
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd
#RUN docker-php-ext-enable gd