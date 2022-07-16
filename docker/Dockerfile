FROM php:8.1.2-cli-alpine3.14

WORKDIR /mnt/app

ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'

RUN apk add libzip-dev libzip zip libxml2-dev
RUN docker-php-ext-install zip xml
RUN apk add --no-cache chromium chromium-chromedriver
RUN apk add --no-cache firefox libzip-dev; \
    docker-php-ext-install zip
ARG GECKODRIVER_VERSION=0.30.0

RUN wget -q https://github.com/mozilla/geckodriver/releases/download/v$GECKODRIVER_VERSION/geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz; \
    tar -zxf geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz -C /usr/bin; \
    rm geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz

ADD /composer.json composer.json
ADD /composer.lock composer.lock

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"\
   && php composer-setup.php --install-dir=$(pwd) --filename=composer\
   && php -r "unlink('composer-setup.php');"

RUN php composer install

COPY /crontab /etc/cron.d/crawler-job

# give the new cronfile execution rights
RUN chmod 0644 /etc/cron.d/crawler-job \
    && crontab /etc/cron.d/crawler-job

# run cron daemon in foreground
CMD crond -f