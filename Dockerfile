FROM php:8.4-cli-alpine

RUN docker-php-ext-install -j$(nproc) bcmath

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN sed -ri -e 's!memory_limit = .+!memory_limit = -1!g' "$PHP_INI_DIR/php.ini"

RUN curl -sSL https://github.com/xp-runners/reference/releases/download/v9.2.0/xp-run-9.2.0.sh > /usr/bin/xp-run

RUN mkdir /app

COPY class.pth /app/

COPY src/ /app/src/

COPY vendor/ /app/vendor/

WORKDIR /app

VOLUME /space

EXPOSE 8080

CMD ["/bin/sh", "/usr/bin/xp-run", "xp.web.Runner", "-a", "0.0.0.0:8080", "-p", "prod", "de.thekid.dialog.App", "/space"]