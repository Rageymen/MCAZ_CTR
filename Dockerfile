FROM php:7.0-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

RUN a2enmod rewrite
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y libicu-dev \
    libzip-dev \
    libmcrypt-dev \   
    libxrender1 libfontconfig1 libx11-dev libjpeg-dev \
    && docker-php-ext-install zip \ 
    && docker-php-ext-install intl \
    && docker-php-ext-install mcrypt mysqli \
    && docker-php-ext-install pdo_mysql 
  
RUN mkdir -p /var/www/html 
COPY . /var/www/html/ 
 