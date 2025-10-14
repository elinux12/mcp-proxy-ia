FROM php:8.1-apache

# Habilita módulos necesarios para cURL y JSON
RUN docker-php-ext-install curl json

# Copia tus archivos al directorio de Apache
COPY . /var/www/html/

# Exponer el puerto 80, que es el que usa Apache.
EXPOSE 80