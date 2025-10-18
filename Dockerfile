# Use the official PHP Apache image
FROM php:8.2-apache

# Install system dependencies and MySQL libraries
RUN apt-get update && apt-get install -y libmysqlclient-dev && docker-php-ext-install pdo pdo_mysql mysqli

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
