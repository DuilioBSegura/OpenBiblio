FROM php:8.2-apache

# Dependências de sistema e extensões PHP necessárias (PDO MySQL e mbstring)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

# Módulos do Apache necessários para .htaccess, segurança, cache, compressão e proxy reverso
RUN a2enmod rewrite headers expires deflate remoteip

# Configurações PHP e Apache customizadas
COPY docker/php/openbiblio.ini /usr/local/etc/php/conf.d/openbiblio.ini
COPY docker/apache/openbiblio.conf /etc/apache2/sites-available/000-default.conf

# Script de inicialização (normaliza CRLF caso editado no Windows)
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

# Copiar código da aplicação
COPY . /var/www/html

# Garantir diretório de uploads e permissões corretas
RUN mkdir -p /var/www/html/uploads/capas \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1/auth/login.php') !== false ? 0 : 1);"

ENTRYPOINT ["docker-entrypoint.sh"]
