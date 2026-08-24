# Usa imagem base oficial do PHP com Apache
FROM php:8.2-apache

# Atualiza os pacotes e instala dependências necessárias do PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copia o projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Dá permissão para o Apache acessar os arquivos
RUN chown -R www-data:www-data /var/www/html

# Habilita o mod_rewrite do Apache (útil para rotas amigáveis)
RUN a2enmod rewrite

# Expõe a porta 80 (onde o servidor vai rodar)
EXPOSE 80

# Inicia o Apache quando o container subir
CMD ["apache2-foreground"]
