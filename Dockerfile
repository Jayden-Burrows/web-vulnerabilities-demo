# From the official PHP 8.3 Apache image
FROM php:8.3-apache

# Install dependencies required for pdo_sqlite
RUN apt-get update && apt-get install -y \
libsqlite3-dev \
libjpeg-dev \
libpng-dev \
libwebp-dev

# Fixing the Apache Image from issuing a message that it 
# doesn't know its fully qualified domain name
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install pdo_sqlite
RUN docker-php-ext-install pdo_sqlite \
&& docker-php-ext-configure gd --with-jpeg --with-webpg

COPY . /var/www/html/

# Set up the data folders
RUN mkdir -p /var/www/html/vulnerable/data /var/www/html/safe/data \
&& chown -R www-data:www-data /var/www/html

EXPOSE 80