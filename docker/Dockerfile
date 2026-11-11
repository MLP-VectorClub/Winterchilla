# Winterchilla (legacy PHP, read-only mode): one build, two runtime images (targets: fpm, web).
#   docker buildx bake -f docker/docker-bake.hcl     (context is the repository root)
# No secrets are baked in; all configuration is runtime environment (see docs/KUBERNETES.md).
ARG PHP_IMAGE=php:8.5-fpm-trixie
ARG NODE_IMAGE=node:24-trixie-slim

# The build stage runs on the builder's own architecture (output is architecture independent: pure PHP vendor tree, built JS/CSS).
FROM --platform=$BUILDPLATFORM ${PHP_IMAGE} AS php-base-build
COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd pdo_pgsql redis opcache \
 && rm -rf /var/lib/apt/lists/* /tmp/*

FROM ${PHP_IMAGE} AS php-base
COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd pdo_pgsql redis opcache \
 && rm -rf /var/lib/apt/lists/* /tmp/*

FROM --platform=$BUILDPLATFORM ${NODE_IMAGE} AS node-bin
FROM --platform=$BUILDPLATFORM composer:2 AS composer-bin

# ---------------------------------------------------------------------------------------------------------
FROM php-base-build AS build
ARG GIT_COMMIT_HASH=unknown
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH} COMPOSER_ALLOW_SUPERUSER=1 CI=true
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY --from=node-bin /usr/local/bin/node /usr/local/bin/node
COPY --from=node-bin /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && npm install -g pnpm@11 \
 && apt-get update && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && pnpm run build

# runtime tree for the fpm image: everything the app needs at runtime, minus node tooling and tests
RUN mkdir /runtime && cp -a /app/. /runtime/ \
 && rm -rf /runtime/node_modules /runtime/tests /runtime/.git /runtime/.env /runtime/docs /runtime/graphics
RUN chmod -R a+rX /runtime/public

# ---------------------------------------------------------------------------------------------------------
FROM php-base AS fpm
RUN { echo "memory_limit = 256M"; echo "expose_php = Off"; echo "upload_max_filesize = 2M"; echo "post_max_size = 4M"; \
      echo "display_errors = Off"; echo "log_errors = On"; echo "error_log = /dev/stderr"; echo "error_reporting = E_ALL & ~E_DEPRECATED"; echo "zend.exception_ignore_args = On"; echo "session.upload_progress.enabled = 0"; \
      echo "opcache.enable=1"; echo "opcache.validate_timestamps=0"; echo "opcache.memory_consumption=96"; \
      echo "opcache.max_accelerated_files=20000"; } > /usr/local/etc/php/conf.d/99-app.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-app.conf
WORKDIR /var/www/html
COPY --from=build --chown=www-data:www-data /runtime/ ./
# Runtime-written/stateful paths: fs/ is the app's data directory (sprites, renders, caches; mount a volume over it),
# /data/export holds the colour guide JSON export that the CronJob writes and nginx serves as /dist/mlpvc-colorguide.json.
RUN mkdir -p fs/appearance_previews fs/cg_render fs/cm_source fs/cm_tokenized fs/contribs fs/sprites fs/sprites_pcg fs/tmp logs /data/export \
 && ln -sfn /data/export/mlpvc-colorguide.json public/dist/mlpvc-colorguide.json \
 && chown -R www-data:www-data fs logs /data/export
ENV LOG_PATH=php://stderr PRODUCTION=true NPM_BUILD_LOCK_FILE_PATH=fs/gulp.lock
USER www-data
EXPOSE 9000

# ---------------------------------------------------------------------------------------------------------
FROM nginxinc/nginx-unprivileged:1-alpine-slim AS web
# static assets only; PHP itself runs in the fpm container of the same pod (same absolute paths)
COPY --from=build /runtime/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
USER root
RUN mkdir -p /data/export && ln -sfn /data/export/mlpvc-colorguide.json /var/www/html/public/dist/mlpvc-colorguide.json
USER 101
EXPOSE 8080
