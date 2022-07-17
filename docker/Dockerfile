FROM php:8.1.2-cli-alpine3.14

WORKDIR /mnt/app

ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'

##
# needed php stuff
RUN apk add libzip-dev libzip zip libxml2-dev \
    && docker-php-ext-install zip xml


ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions sockets gd xdebug
#

# headless browser stuff
RUN apk add --no-cache chromium chromium-chromedriver
ARG GECKODRIVER_VERSION=0.30.0
RUN wget -q https://github.com/mozilla/geckodriver/releases/download/v$GECKODRIVER_VERSION/geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz; \
    tar -zxf geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz -C /usr/bin; \
    rm geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz

##
# application stuff
# this is the simplest way to use composer in your containers.
# the trick is, that since docker 1.17 you can copy files from different images.
# in this case, we simply copy the composer binary from the official composer image to our image ;)
#COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"\
   && php composer-setup.php --install-dir=$(pwd) --filename=composer\
   && php -r "unlink('composer-setup.php');"

# copy all files to the image
ADD /src src
ADD /index.php index.php
ADD /composer.json composer.json
ADD /composer.lock composer.lock

RUN php composer install

##
# cron stuff

# copy a crontab file to the image
COPY /crontab /etc/cron.d/crawler-job

# give the new cronfile execution rights
RUN chmod 0644 /etc/cron.d/crawler-job \
    && crontab /etc/cron.d/crawler-job

# run cron daemon in foreground
CMD crond -f
