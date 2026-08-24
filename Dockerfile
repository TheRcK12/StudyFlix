FROM php:8.3-fpm-bookworm

# Runtime web server + dependências para compilar a extensão oficial do MongoDB.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        ca-certificates \
        libssl-dev \
        libonig-dev \
        pkg-config \
        $PHPIZE_DEPS \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install mbstring \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# PHP-FPM deve enxergar as variáveis do Railway (MONGO_URL, PORT etc.).
RUN { \
      echo '[www]'; \
      echo 'clear_env = no'; \
      echo 'catch_workers_output = yes'; \
    } > /usr/local/etc/php-fpm.d/zz-studyflix.conf \
    && { \
      echo 'expose_php = Off'; \
      echo 'display_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'session.save_path = "/tmp/studyflix-sessions"'; \
    } > /usr/local/etc/php/conf.d/zz-studyflix.ini \
    && mkdir -p /tmp/studyflix-sessions \
    && chown -R www-data:www-data /tmp/studyflix-sessions

# Remove o site padrão do nginx. O arquivo final é gerado no boot com a PORT do Railway.
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf \
    && mkdir -p /etc/nginx/templates

COPY docker/nginx-studyflix.conf.template /etc/nginx/templates/studyflix.conf.template
COPY docker/entrypoint.sh /usr/local/bin/studyflix-entrypoint
RUN chmod +x /usr/local/bin/studyflix-entrypoint

WORKDIR /var/www/html
COPY . /var/www/html/

# Scripts de infraestrutura não ficam expostos pelo DocumentRoot.
RUN mkdir -p /opt/studyflix/scripts \
    && cp -r /var/www/html/scripts/. /opt/studyflix/scripts/ \
    && rm -rf /var/www/html/docker /var/www/html/scripts \
    && rm -f /var/www/html/Dockerfile /var/www/html/README_RAILWAY.md \
              /var/www/html/VALIDACAO.md /var/www/html/.env.example \
              /var/www/html/.dockerignore /var/www/html/composer.json \
    && chown -R www-data:www-data /var/www/html

ENV PORT=8080
EXPOSE 8080

CMD ["/usr/local/bin/studyflix-entrypoint"]
