FROM wordpress:latest

# Install required packages and development tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    default-mysql-client \
    subversion \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV PATH="/root/.composer/vendor/bin:${PATH}"

# Install PHP_CodeSniffer and WordPress Coding Standards
RUN composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true \
    && composer global require \
    squizlabs/php_codesniffer \
    wp-coding-standards/wpcs \
    && phpcs --config-set installed_paths /root/.composer/vendor/wp-coding-standards/wpcs \
    && phpcs --config-set default_standard WordPress

# Configuring PHP directives
COPY custom.ini $PHP_INI_DIR/conf.d/

WORKDIR /var/www/html
