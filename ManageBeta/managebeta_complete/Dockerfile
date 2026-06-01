FROM php:8.2-apache

# Enable mod_rewrite for .htaccess routing
RUN a2enmod rewrite

# Install SQLite support
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copy all project files into Apache's web root
COPY . /var/www/html/

# Create database folder if it doesn't exist, then set permissions
RUN mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/database \
    && chmod -R 775 /var/www/html/database

# Set document root to the project subfolder
ENV APACHE_DOCUMENT_ROOT /var/www/html/ManageBeta/managebeta_complete
RUN sed -i 's|/var/www/html|/var/www/html/ManageBeta/managebeta_complete|g' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess to work
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80