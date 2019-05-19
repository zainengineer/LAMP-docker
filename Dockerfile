FROM php:5.6-apache
#FROM php:5.6.3-apache
#FROM php:5.5-apache
#RUN printf "deb http://archive.debian.org/debian/ jessie main\ndeb-src http://archive.debian.org/debian/ jessie main\ndeb http://security.debian.org jessie/updates main\ndeb-src http://security.debian.org jessie/updates main" > /etc/apt/sources.list
#An example https://github.com/docker-library/php/issues/75#issuecomment-235773906
RUN apt-get update
RUN apt-get install -y libpng-dev
RUN docker-php-ext-install gd
RUN apt-get -y install mysql-client vim libmcrypt-dev libpng-dev libxml2-dev aria2 zlib1g-dev \
     libfreetype6-dev libjpeg62-turbo-dev git zip unzip pv

RUN docker-php-ext-install mcrypt soap pdo pdo_mysql mysql mysqli opcache  mbstring bcmath

#RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
#RUN docker-php-ext-install -j$(nproc) gd
RUN a2enmod rewrite ssl headers deflate expires mime
#RUN pecl install xdebug-2.5.5
#RUN pecl install xdebug-2.3.3

#due to this issue https://github.com/docker-library/php/issues/133

# Compile and install xdebug with the static property fix
RUN BEFORE_PWD=$(pwd) \
    && mkdir -p /opt/xdebug \
    && cd /opt/xdebug \
    && curl -k -L https://github.com/xdebug/xdebug/archive/XDEBUG_2_5_5.tar.gz | tar zx \
    && cd xdebug-XDEBUG_2_5_5 \
    && phpize \
    && ./configure --enable-xdebug \
    && make clean \
    && sed -i 's/-O2/-O0/g' Makefile \
    && make \
    # && make test \
    && make install \
    && cd "${BEFORE_PWD}" \
    && rm -r /opt/xdebug
RUN docker-php-ext-enable xdebug
ADD util/resources/xdebug.ini /usr/local/etc/php/conf.d/20-xdebug.ini
RUN apt-get install -y iproute
RUN sed -e "s/%XDEBUG_REMOTE_HOST%/`/sbin/ip route|awk '/default/ { print $3 }'`/" \
        -i /usr/local/etc/php/conf.d/20-xdebug.ini
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
