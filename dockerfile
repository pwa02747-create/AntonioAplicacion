FROM php:8.2-apache

# Habilitar mod_rewrite (útil para routing si usas .htaccess)
RUN a2enmod rewrite

# Instalar dependencias necesarias para tus librerías PHP
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libgmp-dev \
    zlib1g-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        gmp

# Instalar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar tu aplicación al directorio web de Apache
COPY . /var/www/html

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias de PHP (ya están en composer.json)
RUN composer install --no-dev --optimize-autoloader

# Apache escucha en el puerto 80 por defecto (Render detecta esto)
EXPOSE 80
