# Dockerfile
FROM php:8.3-fpm

# Install system dependencies + Redis extension
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Create non-root user matching your host UID/GID
ARG UID=1000
ARG GID=1000
ARG USER=mohamedali
RUN groupadd -g ${GID} ${USER} || true \
    && useradd -u ${UID} -g ${GID} -m -s /bin/bash ${USER} \
    && usermod -aG www-data ${USER}

WORKDIR /var/www/html
USER ${USER}

COPY --chown=${USER}:${USER} . .

EXPOSE 9000
CMD ["php-fpm"]
