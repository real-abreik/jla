FROM php:8.3.9-alpine

ENV BUILD_DEPS autoconf file gcc libc-dev automake make g++ pkgconf

RUN apk add --update --no-cache --virtual .build-deps $BUILD_DEPS
RUN pecl install redis && docker-php-ext-enable redis.so

RUN addgroup -S script && adduser -S script -G script
USER script

WORKDIR /home/script
