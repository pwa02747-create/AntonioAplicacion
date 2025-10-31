FROM php:8.2-apache

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Instalar dependencias PHP y extensiones necesarias
RUN apt-get update && apt-get install -y \
    unzip git libzip-dev libonig-dev libcurl4-openssl-dev libgmp-dev zlib1g-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gmp \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar solo composer.json (y composer.lock si lo tienes)
COPY composer.json ./
# COPY composer.lock ./   # descomenta si agregas composer.lock

# Instalar dependencias PHP y generar autoloader optimizado
RUN composer install --no-dev --optimize-autoloader

# Copiar el resto de la aplicación
COPY . .

# Dar permisos al directorio
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer puerto 80 para Apache
EXPOSE 80

# Iniciar Apache al levantar el contenedor
CMD ["apache2-foreground"]
