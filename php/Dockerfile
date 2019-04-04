FROM php:7.2-fpm-alpine

RUN apk add --no-cache --virtual=.build-deps zlib-dev autoconf gcc g++ make \
  && pecl install grpc protobuf opencensus-alpha apcu igbinary xdebug \
  && docker-php-ext-install sysvshm opcache \
  && docker-php-ext-enable grpc protobuf opencensus apcu igbinary xdebug \
  && apk del .build-deps \
  && apk del *-dev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json /tmp/composer.json
COPY composer.lock /tmp/composer.lock
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-scripts --no-autoloader -d /tmp

COPY . /src
WORKDIR /src

RUN mv -n /tmp/vendor ./ \
  && composer dump-autoload
