FROM php:7.1.8-apache

COPY . /srv/app
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /srv/app

ENV PATH="/srv/app:${PATH}"

RUN chown -R www-data:www-data /srv/app && a2enmod rewrite

# Install git
RUN apt-get update && apt-get install --assume-yes --no-install-recommends git && rm -rf /var/lib/apt/lists/*
	
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

COPY composer.json /srv/app/composer.json
RUN php composer.phar install

RUN apt-get purge --assume-yes git
RUN apt-get --assume-yes autoclean

RUN php frankly-amp.php
