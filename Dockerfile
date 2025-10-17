FROM php:8.2-apache

# Force-configure and install using mysqlnd for better Docker compatibility
RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-install pdo_mysql

# Copy custom PHP configuration to enable pdo_mysql
COPY 99-pdo-mysql.ini /usr/local/etc/php/conf.d/

# Enable Apache mod_rewrite for URL routing (if needed)
RUN a2enmod rewrite

# Copy your website files
COPY . /var/www/html

# Set proper permissions for web files
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80 for web server
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
