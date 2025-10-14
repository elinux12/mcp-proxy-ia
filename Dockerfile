# Usa una imagen base de PHP con Apache
FROM php:8.1-apache

# 1. Instalar dependencias de desarrollo y librerías base en una sola capa.
# Esto incluye la actualización, la instalación de librerías para cURL, y limpieza de cache.
RUN apt-get update && \
    apt-get install -y \
    libcurl4-openssl-dev \
    libzip-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

# 2. Habilita módulos necesarios para cURL y JSON.
# El error 'modules/*' es común sin la limpieza anterior.
RUN docker-php-ext-install curl json

# 3. Copia tus archivos al directorio público de Apache
COPY . /var/www/html/

# 4. Exponer el puerto (opcional, pero limpio)
EXPOSE 80 

# Mantiene Apache en primer plano
CMD ["apache2-foreground"]