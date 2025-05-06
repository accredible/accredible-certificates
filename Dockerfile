FROM wordpress:latest

# Configuring PHP directives
COPY custom.ini $PHP_INI_DIR/conf.d/

WORKDIR /var/www/html
