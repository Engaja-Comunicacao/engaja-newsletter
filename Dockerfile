FROM php:8.2-apache

# Dependências do sistema + extensões do PHP necessárias
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo pdo_mysql zip gd \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

ENV TZ=America/Fortaleza

# Instala Composer (pra PHPMailer)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o DocumentRoot do Apache para /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/000-default.conf \
  /etc/apache2/apache2.conf \
  /etc/apache2/conf-available/*.conf

# Copia o projeto
WORKDIR /var/www/html
COPY . .

# Permissões para uploads
RUN mkdir -p public/uploads/headers public/uploads/pdfs \
 && chown -R www-data:www-data public/uploads \
 && chmod -R 775 public/uploads

# Instala dependências PHP (se existir composer.json)
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist; fi

RUN mkdir -p /var/www/html/storage \
 && chown -R www-data:www-data /var/www/html/storage \
 && chmod -R 775 /var/www/html/storage

EXPOSE 80
