FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libssl-dev \
        zlib1g-dev \
        libpng-dev \
        git \
        unzip \
        curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

RUN a2enmod rewrite headers

RUN echo "sendmail_path = /usr/sbin/sendmail -t -i" >> /usr/local/etc/php/php.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
