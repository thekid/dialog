FROM php:8.6-cli-alpine

RUN docker-php-ext-install -j$(nproc) bcmath

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN sed -ri -e 's!memory_limit = .+!memory_limit = -1!g' "$PHP_INI_DIR/php.ini"

RUN curl -sSL https://github.com/xp-runners/evolution/releases/download/v10.0.0/xp-linux-amd64-10.0.0.tar.gz | tar xvz -C /usr/bin

RUN mkdir /app

COPY class.pth /app/

COPY src/ /app/src/

COPY vendor/ /app/vendor/

WORKDIR /app

VOLUME /space

EXPOSE 8080

CMD ["/usr/bin/xp", "xp.web.Runner", "-a", "0.0.0.0:8080", "-p", "prod", "de.thekid.dialog.App", "/space"]