FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache-studyflix.conf /etc/apache2/conf-available/studyflix.conf
RUN a2enconf studyflix

WORKDIR /var/www/html
COPY . /var/www/html/

# Arquivos de infraestrutura ficam fora do DocumentRoot.
RUN cp /var/www/html/docker/entrypoint.sh /usr/local/bin/studyflix-entrypoint \
    && chmod +x /usr/local/bin/studyflix-entrypoint \
    && mkdir -p /opt/studyflix \
    && cp -r /var/www/html/database /opt/studyflix/database \
    && cp -r /var/www/html/scripts /opt/studyflix/scripts \
    && rm -rf /var/www/html/docker /var/www/html/database /var/www/html/scripts \
    && rm -f /var/www/html/Dockerfile /var/www/html/README_RAILWAY.md \
              /var/www/html/.env.example /var/www/html/.dockerignore /var/www/html/composer.json \
    && chown -R www-data:www-data /var/www/html

ENV PORT=8080
EXPOSE 8080

CMD ["/usr/local/bin/studyflix-entrypoint"]
