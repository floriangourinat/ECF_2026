FROM php:8.2-apache
RUN apt-get update && apt-get install -y libssl-dev zlib1g-dev libpng-dev git unzip
RUN docker-php-ext-install pdo pdo_mysql
RUN echo "sendmail_path = /usr/sbin/sendmail -t -i" >> /usr/local/etc/php/php.ini
RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN a2enmod rewrite headers
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
