FROM php:8.2-apache

# Install PDO PostgreSQL driver
RUN docker-php-ext-install pdo pdo_pgsql

# Copy your website files
COPY . /var/www/html

# Set proper permissions for web files
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80 for web server
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
