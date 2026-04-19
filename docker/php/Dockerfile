FROM php:8.4-fpm-bookworm as php_base
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV XDEBUG_VERSION="3.4.0"

RUN apt update && apt install -y \
    curl \
    git \
    gosu \
    libzip-dev \
    unzip \
    # Install some software that is necessary for debugging and incident investigation, that's less than 1% of the image
    inetutils-ping \
    iproute2 \
    procps \
    vim-tiny \
    libpcre3 \
    gnupg2 \
    librabbitmq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libpng-dev \
    libicu-dev \
    && apt clean \
    && rm -fr /var/lib/apt/lists/ \
    && ln -s /usr/bin/vim.tiny /usr/bin/vim

RUN docker-php-ext-install zip \
    && docker-php-ext-install -j$(nproc) mysqli \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && pecl install xdebug-$XDEBUG_VERSION \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && pecl install amqp && docker-php-ext-enable amqp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl


COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/xdebug3.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY ./docker/php/fpm-custom.conf /usr/local/etc/php-fpm.d/zzz-custom.conf
COPY ./docker/php/fpm-access-ecs.conf /usr/local/etc/php-fpm.d/zzz-fpm-access-ecs.inactive
ENV LOG_STREAM="php://stdout"

# set log limit 16384 which docker has
# php has a limitation of 8192 by default
RUN sed -i -e "s/log_limit\s*=\s*[0-9]*/log_limit = 16384/g" /usr/local/etc/php-fpm.d/docker.conf

# We need it to enable/disable extensions and write configs in entrypoint for other users
RUN chmod -R a+w /usr/local/etc/php/conf.d/ /usr/local/etc/php-fpm.d/

CMD ["/usr/local/sbin/php-fpm"]

# Composer cache and config will be mounted here
RUN mkdir -p /home/php/composer /composer/cache /composer/config /app \
    && chmod a+rwx -R /home/php /composer/cache /composer/config /app


ENV HOME=/home/php
ENV COMPOSER_HOME=/home/php/composer/config
ENV COMPOSER_CACHE_DIR=/home/php/composer/cache

ENV ECS_JSON_LOGS_ENABLED=0

WORKDIR /app
