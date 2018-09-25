FROM php:7.1.8-apache

COPY . /srv/app
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /srv/app

ENV PATH="/srv/app:${PATH}"

RUN chown -R www-data:www-data /srv/app && a2enmod rewrite

# Install git
RUN apt-get update && apt-get install --assume-yes --no-install-recommends git && rm -rf /var/lib/apt/lists/*

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN curl -o composer-setup.sig https://composer.github.io/installer.sig
RUN php -r "if (hash('SHA384', file_get_contents('composer-setup.php')) !== trim(file_get_contents('composer-setup.sig'))) { unlink('composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

COPY composer.json /srv/app/composer.json
RUN php composer.phar install

RUN apt-get purge --assume-yes git
RUN apt-get --assume-yes autoclean

RUN php frankly-amp.php
