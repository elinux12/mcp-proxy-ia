# Usa una imagen base de PHP con Apache
FROM php:8.1-apache

# 1. Instalar dependencias de desarrollo necesarias
# El comando 'apt-get update && apt-get install' asegura que tengamos las librerías necesarias
RUN apt-get update && \
    apt-get install -y \
    libcurl4-openssl-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Habilita módulos necesarios para cURL y JSON
# Ahora sí se encuentran las dependencias para compilar cURL
RUN docker-php-ext-install curl json

# 3. Copia tus archivos al directorio público de Apache
COPY . /var/www/html/

# 4. Exponer el puerto (Render lo mapea automáticamente)
EXPOSE 80 

# Mantiene Apache en primer plano
CMD ["apache2-foreground"]