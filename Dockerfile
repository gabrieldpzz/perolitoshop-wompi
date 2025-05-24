FROM php:8.2-apache

# Copiar código
COPY . /var/www/html/

# Habilitar extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

# Configurar zona horaria
RUN ln -fs /usr/share/zoneinfo/America/El_Salvador /etc/localtime && \
    dpkg-reconfigure -f noninteractive tzdata

# Habilitar mod_rewrite
RUN a2enmod rewrite

EXPOSE 80
