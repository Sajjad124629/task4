FROM php:8.4-apache

# Enable Apache mod_rewrite for Symfony routing
RUN a2enmod rewrite

# Update Apache DocumentRoot to point to Symfony's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl zip opcache mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy the entire project to the container
COPY . .

# Set environment to production
ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Install PHP dependencies (production mode)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Create var directory if it doesn't exist and set permissions
RUN mkdir -p /var/www/html/var && \
    chown -R www-data:www-data /var/www/html/var && \
    chmod -R 775 /var/www/html/var

# Clear and warmup cache for production
RUN php bin/console cache:clear --env=prod && \
    php bin/console cache:warmup --env=prod

# Make the entrypoint script executable
RUN chmod +x docker-entrypoint.sh

# Render will look for the EXPOSE port
EXPOSE 80

# Use our custom entrypoint script
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
