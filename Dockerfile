FROM php:7.3-cli-alpine

RUN docker-php-ext-install -j$(nproc) bcmath

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing gnu-libiconv

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

RUN curl -sSL https://dl.bintray.com/xp-runners/generic/xp-run-master.sh > /usr/bin/xp-run

RUN mkdir /app

COPY class.pth /app/

COPY src/ /app/src/

COPY vendor/ /app/vendor/

WORKDIR /app

EXPOSE 3000

CMD ["/bin/sh", "/usr/bin/xp-run", "xp.web.Runner", "-a", "0.0.0.0:3000", "de.thekid.dialog.App"]