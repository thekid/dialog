FROM php:8.1-cli-alpine

RUN docker-php-ext-install -j$(nproc) bcmath

RUN curl -sSL https://baltocdn.com/xp-framework/xp-runners/distribution/downloads/e/entrypoint/xp-run-8.6.2.sh > /usr/bin/xp-run

RUN mkdir /app

COPY class.pth /app/

COPY src/ /app/src/

COPY vendor/ /app/vendor/

WORKDIR /app

VOLUME /space

EXPOSE 8080

CMD ["/bin/sh", "/usr/bin/xp-run", "xp.web.Runner", "-a", "0.0.0.0:8080", "-p", "prod", "de.thekid.dialog.App", "/space"]