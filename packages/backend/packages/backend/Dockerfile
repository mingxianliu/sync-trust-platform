FROM php:8.1-apache

# 安裝系統依賴
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 複製 composer 檔案
COPY composer.json composer.lock ./

# 安裝 PHP 依賴
RUN composer install --no-dev --optimize-autoloader

# 複製應用程式檔案
COPY . .

# 設定 Apache 配置
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# 設定權限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/writable

# 設定環境變數
ENV CI_ENVIRONMENT=production

# 暴露端口
EXPOSE 80

# 啟動 Apache
CMD ["apache2-foreground"] 