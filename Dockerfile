FROM php:8.2-apache

# Install PostgreSQL and MySQL PDO drivers
RUN apt-get update && apt-get install -y libpq-dev zip unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy all app files into the web root
COPY . /var/www/html

# Ensure Apache can access everything
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
