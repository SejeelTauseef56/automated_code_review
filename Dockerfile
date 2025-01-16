FROM php:8.2-fpm

# Install required dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    zip \
    unzip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dotenv
RUN composer require vlucas/phpdotenv

WORKDIR /app

# Copy project files into the container
COPY . /app

# Install PHP dependencies
RUN composer install

EXPOSE 9000
CMD ["php-fpm"]
