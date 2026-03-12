FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN apt-get update && apt-get install -y git wget iptables ufw

COPY ./html /var/www/html

RUN chown -R 1000:1000 /var/www/html && \
    chmod -R 775 /var/www/html && \
    chmod -R 777 /var/www/html/uploads

COPY custom.ini /usr/local/etc/php/conf.d/custom.ini

RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]