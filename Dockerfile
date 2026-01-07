FROM php:8.0-cli-alpine

# Install dependencies
RUN apk add --no-cache \
    rabbitmq-c-dev \
    autoconf \
    g++ \
    make \
    git \
    unzip \
    linux-headers

# Install AMQP extension
RUN pecl install amqp && \
    docker-php-ext-enable amqp

# Install pcntl for signal handling
RUN docker-php-ext-install pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy all application code
COPY . .

# Install PHP dependencies (with dev for testing)
RUN composer install --optimize-autoloader

CMD ["php", "tests/Integration/run_test.php"]
