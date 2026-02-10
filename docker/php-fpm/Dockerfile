FROM php:8.2-fpm

WORKDIR "/application"

# composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && composer --version

# необходимые системные зависимости
RUN apt-get update && apt-get install -y \
    curl \
    nano \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxml2-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        zip \
        intl \
        gd \
        pdo_pgsql \
        pgsql \
        bcmath \
        soap \
        sockets \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# копирование php.ini
COPY php.ini /usr/local/etc/php/conf.d/php.ini
