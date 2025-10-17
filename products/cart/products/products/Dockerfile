# Use official PHP CLI image
FROM php:8.2-cli

# Set working directory inside container
WORKDIR /var/www/html

# Copy all project files into container
COPY . .

# Expose port 10000 for Render
EXPOSE 10000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
FROM php:8.2-apache

# Install PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Copy your website files
COPY . /var/www/html/
