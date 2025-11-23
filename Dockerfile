FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql zip bcmath pcntl\
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache's mod_rewrite for friendly URLs
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www

# Copy composer files
COPY composer.json ./
COPY composer.lock* ./

# Copy application files
COPY . .

# Configure Apache DocumentRoot to point to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create startup script
RUN echo '#!/bin/bash\n\
composer install --no-dev --optimize-autoloader\n\
\n\
# Wait for database to be ready\n\
echo "[$(date +"%Y-%m-%d %H:%M:%S")] Waiting for database..."\n\
echo "[$(date +"%Y-%m-%d %H:%M:%S")] Connection details: MYSQL_HOST=$MYSQL_HOST, MYSQL_PORT=$MYSQL_PORT, MYSQL_USER=$MYSQL_USER"\n\
attempt=0\n\
max_attempts=10\n\
\n\
while [ $attempt -lt $max_attempts ]; do\n\
  attempt=$((attempt + 1))\n\
  echo "[$(date +"%Y-%m-%d %H:%M:%S")] Attempt $attempt/$max_attempts..."\n\
  \n\
  error_output=$(mysqladmin ping -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --skip-ssl 2>&1)\n\
  if [ $? -eq 0 ]; then\n\
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Database is ready!"\n\
    break\n\
  else\n\
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Connection failed: $error_output"\n\
  fi\n\
  \n\
  if [ $attempt -lt $max_attempts ]; then\n\
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] Database not ready, waiting 5 seconds..."\n\
    sleep 5\n\
  fi\n\
done\n\
\n\
if [ $attempt -eq $max_attempts ]; then\n\
  if ! mysqladmin ping -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --skip-ssl --silent; then\n\
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] ERROR: Database not responding after $max_attempts attempts"\n\
    exit 1\n\
  fi\n\
fi\n\
\n\
# Run migrations\n\
echo "[$(date +"%Y-%m-%d %H:%M:%S")] Running migrations..."\n\
php /var/www/bin/migrate.php\n\
\n\
# Start Apache\n\
echo "[$(date +"%Y-%m-%d %H:%M:%S")] Starting Apache..."\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

# The default command will be to start Apache
CMD ["/start.sh"]
