# Use the official PHP Apache image
FROM php:8.2-apache

# Install PDO PostgreSQL driver
RUN docker-php-ext-install pdo_pgsql pgsql

# Copy custom PHP configuration to ensure extensions are loaded
COPY 99-pdo-mysql.ini /usr/local/etc/php/conf.d/

# Enable Apache mod_rewrite (for PHP frameworks like Laravel or custom routes)
RUN a2enmod rewrite

# Copy project files to container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
