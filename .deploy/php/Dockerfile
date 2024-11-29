# from https://www.drupal.org/docs/system-requirements/php-requirements
FROM php:8.2.16-fpm-bullseye

# install the PHP extensions we need
RUN set -eux; \
	\
	apt-get update; \
	\
	apt-get install -y wget \
		zlib1g-dev \
		libzip-dev \
		libfreetype6-dev \
		libpng-dev \
		libwebp-dev \
		libjpeg-dev \
		libpq-dev ; \
	\
	docker-php-ext-configure gd \
		--with-freetype \
		--with-jpeg=/usr/include \
		--with-webp \
	; \
	\
	docker-php-ext-install -j "$(nproc)" \
		gd \
		opcache \
		pdo_mysql \
		pdo_pgsql \
		zip

# Install and enable the redis extension.
RUN wget https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar -P /usr/bin \
&& chmod +x /usr/bin/pickle.phar \
&& pickle.phar install redis \
&& docker-php-ext-enable redis

# set recommended PHP.ini settings
RUN { \
		echo 'memory_limit = 512M'; \
		echo 'post_max_size = 24M'; \
		echo 'upload_max_filesize = 24M'; \
		echo 'max_input_vars = 6000'; \
		echo 'max_execution_time = 120'; \
	} > /usr/local/etc/php/conf.d/docker-fpm.ini
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
		echo 'opcache.fast_shutdown=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Installing git.
RUN apt-get install -y git

# Adding Composer.
COPY --from=composer:2.0 /usr/bin/composer /usr/local/bin/

# Installing Robo.
RUN wget https://github.com/consolidation/robo/releases/download/4.0.4/robo.phar
RUN chmod +x robo.phar
RUN mv robo.phar /usr/bin/robo

# Installing NPM.
RUN apt-get install -y npm
RUN npm install gulp-cli gulp -g

# Installing Drush Launcher.
RUN wget -O drush.phar https://github.com/drush-ops/drush-launcher/releases/latest/download/drush.phar
RUN chmod +x drush.phar
RUN mv drush.phar /usr/local/bin/drush

# Installing Mysql Client needed for Drush.
RUN apt-get install -y default-mysql-client

# Installing Patch for Composer.
RUN apt-get install -y patch

# https://www.drupal.org/node/3060/release
ENV DRUPAL_VERSION 10.1.5

WORKDIR /var/www/html
