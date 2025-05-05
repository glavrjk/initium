FROM php:8.4-apache

RUN a2enmod rewrite
RUN a2enmod ssl
RUN a2enmod headers

RUN apt-get update && apt-get install -y git unzip zip

RUN mkdir /etc/apache2/sites-available/ssl

WORKDIR /var/www

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions gd pdo_mysql bcmath zip intl opcache sodium curl xdebug imagick apfd

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ADD "https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh" /tmp
RUN bash /tmp/setup.deb.sh
RUN apt-get install -ym symfony-cli