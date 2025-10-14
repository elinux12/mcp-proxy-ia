# Usa una imagen base de PHP con Apache
FROM php:8.1-apache

# 1. Instalar dependencias de desarrollo y las extensiones binarias de PHP.
# Instalamos 'git' y 'libcurl4-openssl-dev' (por si acaso), pero la clave son las extensiones 'php8.1-curl' y 'php8.1-json'.
RUN apt-get update && \
    apt-get install -y \
    libcurl4-openssl-dev \
    git \
    php8.1-curl \
    php8.1-json \
    && rm -rf /var/lib/apt/lists/*

# 2. Habilita las extensiones instaladas.
# Nota: Ya no usamos 'docker-php-ext-install'. Solo las habilitamos.
RUN docker-php-ext-enable curl json

# 3. Copia tus archivos al directorio público de Apache
COPY . /var/www/html/

# 4. Exponer el puerto (Apache)
EXPOSE 80 

# Mantiene Apache en primer plano
CMD ["apache2-foreground"]