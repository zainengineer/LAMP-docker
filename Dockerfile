FROM php:5.6-apache
#An example https://github.com/docker-library/php/issues/75#issuecomment-235773906
RUN apt-get update
RUN apt-get install -y libpng-dev
RUN docker-php-ext-install gd
RUN apt-get -y install mysql-client vim libmcrypt-dev libpng-dev libxml2-dev aria2 zlib1g-dev \
     libfreetype6-dev libjpeg62-turbo-dev
RUN docker-php-ext-install mcrypt soap pdo pdo_mysql mysql mysqli opcache  mbstring
#RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
#RUN docker-php-ext-install -j$(nproc) gd
RUN a2enmod rewrite ssl headers deflate expires mime
RUN apt-get -y install git zip unzip
RUN pecl install xdebug-2.5.5
RUN docker-php-ext-enable xdebug
ADD util/resources/xdebug.ini /usr/local/etc/php/conf.d/20-xdebug.ini
RUN sed -e "s/%XDEBUG_REMOTE_HOST%/`/sbin/ip route|awk '/default/ { print $3 }'`/" \
        -i /usr/local/etc/php/conf.d/20-xdebug.ini
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
