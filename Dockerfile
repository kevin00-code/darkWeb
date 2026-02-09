# Use the official PHP image with Apache
FROM php:8.1-apache

# Copy your project files into the web directory
COPY . /var/www/html/

# Ensure the server can write to your JSON vault
RUN chmod -R 777 /var/www/html/

# Open port 80 for web traffic
EXPOSE 80
