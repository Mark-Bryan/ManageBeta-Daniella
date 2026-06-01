FROM php:8.2-apache

# Enable mod_rewrite for .htaccess routing
RUN a2enmod rewrite

# Install SQLite support
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copy all project files into Apache's web root
COPY . /var/www/html/

# Give Apache permission to write the SQLite database file
RUN chown -R www-data:www-data /var/www/html/database \
    && chmod -R 775 /var/www/html/database

# Allow .htaccess to work
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80