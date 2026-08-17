FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libldap2-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure ldap \
    && docker-php-ext-install pdo_mysql ldap zip

RUN { \
    echo "upload_max_filesize=512M"; \
    echo "post_max_size=512M"; \
    echo "max_file_uploads=20"; \
    echo "memory_limit=768M"; \
} > /usr/local/etc/php/conf.d/uploads.ini

RUN { \
    echo "display_errors=Off"; \
    echo "display_startup_errors=Off"; \
    echo "log_errors=On"; \
    echo "html_errors=Off"; \
    echo "zend.exception_ignore_args=On"; \
} > /usr/local/etc/php/conf.d/security.ini

RUN a2enmod rewrite headers
RUN { \
    echo '<Directory /var/www/html/tools>'; \
    echo '    Require all denied'; \
    echo '</Directory>'; \
    echo '<Directory /var/www/html/public/uploads>'; \
    echo '    Options -Indexes'; \
    echo '    AllowOverride None'; \
    echo '    php_admin_flag engine off'; \
    echo '    <FilesMatch "\.(php|phtml|php3|php4|php5|php7|phar)$">'; \
    echo '        Require all denied'; \
    echo '    </FilesMatch>'; \
    echo '</Directory>'; \
} > /etc/apache2/conf-available/requisiti-security.conf \
    && a2enconf requisiti-security
RUN echo "ServerName localhost" > /etc/apache2/conf-available/server-name.conf \
    && a2enconf server-name

WORKDIR /var/www/html
