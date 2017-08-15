FROM php:5.6-apache

RUN apt-get update
RUN apt-get -y install mysql-client
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install mysql mysqli
RUN apt-get -y install vim
