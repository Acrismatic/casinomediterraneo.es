#!/usr/bin/env bash

set -e

PROJECT="casinomediterraneo"

echo "Creando estructura del proyecto..."

# mkdir -p $PROJECT
# cd $PROJECT

# ------------------------
# Carpetas principales
# ------------------------

mkdir -p docker/nginx
mkdir -p docker/wordpress/entrypoint

mkdir -p scripts

mkdir -p src/wp-content/mu-plugins
mkdir -p src/wp-content/plugins/cm-core
mkdir -p src/wp-content/themes/cm-theme
mkdir -p src/wp-content/uploads

mkdir -p docs

# ------------------------
# .gitignore
# ------------------------

cat <<EOF > .gitignore
.env
.env.local
.env.staging

*.sql
*.zip
*.tar.gz

logs/
tmp/

src/wp-content/uploads/*
!src/wp-content/uploads/.gitkeep

node_modules
vendor

.DS_Store
.idea
.vscode
EOF

# ------------------------
# .env.example
# ------------------------

cat <<EOF > .env.example
MYSQL_ROOT_PASSWORD=root
WORDPRESS_DB_NAME=wordpress
WORDPRESS_DB_USER=wordpress
WORDPRESS_DB_PASSWORD=wordpress

HTTP_PORT=8080
PMA_PORT=8081

WP_HOME=http://localhost:8080
WP_SITEURL=http://localhost:8080
EOF

# ------------------------
# docker-compose base
# ------------------------

cat <<EOF > compose.yml
services:

  db:
    image: mysql:8
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: \${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: \${WORDPRESS_DB_NAME}
      MYSQL_USER: \${WORDPRESS_DB_USER}
      MYSQL_PASSWORD: \${WORDPRESS_DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql

  wordpress:
    build:
      context: .
      dockerfile: docker/wordpress/Dockerfile
    restart: unless-stopped
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_NAME: \${WORDPRESS_DB_NAME}
      WORDPRESS_DB_USER: \${WORDPRESS_DB_USER}
      WORDPRESS_DB_PASSWORD: \${WORDPRESS_DB_PASSWORD}
    volumes:
      - wp_core:/var/www/html
      - uploads:/var/www/html/wp-content/uploads

  nginx:
    image: nginx:alpine
    depends_on:
      - wordpress
    ports:
      - "\${HTTP_PORT}:80"
    volumes:
      - wp_core:/var/www/html:ro
      - uploads:/var/www/html/wp-content/uploads:ro
      - ./docker/nginx/staging.conf:/etc/nginx/conf.d/default.conf:ro

volumes:
  db_data:
  wp_core:
  uploads:
EOF

# ------------------------
# compose.dev.yml
# ------------------------

cat <<EOF > compose.dev.yml
services:

  wordpress:
    volumes:
      - ./src/wp-content/plugins:/var/www/html/wp-content/plugins
      - ./src/wp-content/themes:/var/www/html/wp-content/themes
      - ./src/wp-content/mu-plugins:/var/www/html/wp-content/mu-plugins
      - ./src/wp-content/uploads:/var/www/html/wp-content/uploads

  nginx:
    volumes:
      - ./src/wp-content/plugins:/var/www/html/wp-content/plugins:ro
      - ./src/wp-content/themes:/var/www/html/wp-content/themes:ro
      - ./src/wp-content/mu-plugins:/var/www/html/wp-content/mu-plugins:ro
      - ./src/wp-content/uploads:/var/www/html/wp-content/uploads:ro
      - ./docker/nginx/dev.conf:/etc/nginx/conf.d/default.conf:ro

  phpmyadmin:
    image: phpmyadmin
    ports:
      - "\${PMA_PORT}:80"
    environment:
      PMA_HOST: db
EOF

# ------------------------
# compose.staging.yml
# ------------------------

cat <<EOF > compose.staging.yml
services:

  wordpress:
    environment:
      WP_DEBUG: "false"

  nginx:
    volumes:
      - ./docker/nginx/staging.conf:/etc/nginx/conf.d/default.conf:ro
EOF

# ------------------------
# Dockerfile WordPress
# ------------------------

cat <<EOF > docker/wordpress/Dockerfile
FROM wordpress:php8.3-fpm-alpine

RUN apk add --no-cache bash curl

COPY docker/wordpress/php.ini /usr/local/etc/php/conf.d/custom.ini

COPY src/wp-content/plugins /var/www/html/wp-content/plugins
COPY src/wp-content/themes /var/www/html/wp-content/themes
COPY src/wp-content/mu-plugins /var/www/html/wp-content/mu-plugins

RUN chown -R www-data:www-data /var/www/html
EOF

# ------------------------
# PHP config
# ------------------------

cat <<EOF > docker/wordpress/php.ini
memory_limit=512M
upload_max_filesize=128M
post_max_size=128M
max_execution_time=120
EOF

# ------------------------
# nginx dev config
# ------------------------

cat <<EOF > docker/nginx/dev.conf
server {
    listen 80;
    root /var/www/html;

    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass wordpress:9000;
        fastcgi_param SCRIPT_FILENAME /var/www/html\$fastcgi_script_name;
    }
}
EOF

# ------------------------
# nginx staging config
# ------------------------

cat <<EOF > docker/nginx/staging.conf
server {
    listen 80;
    root /var/www/html;

    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass wordpress:9000;
        fastcgi_param SCRIPT_FILENAME /var/www/html\$fastcgi_script_name;
    }

    location ~* \.(css|js|jpg|jpeg|png|gif|svg|webp|woff|woff2)\$ {
        expires 30d;
        access_log off;
    }
}
EOF

# ------------------------
# Makefile
# ------------------------

cat <<EOF > Makefile
COMPOSE_DEV=docker compose -f compose.yml -f compose.dev.yml

setup:
	cp -n .env.example .env || true
	mkdir -p src/wp-content/uploads

up:
	\$(COMPOSE_DEV) up -d --build

down:
	\$(COMPOSE_DEV) down

logs:
	\$(COMPOSE_DEV) logs -f

wp:
	\$(COMPOSE_DEV) run --rm wordpress wp \$(filter-out \$@,\$(MAKECMDGOALS))

shell:
	\$(COMPOSE_DEV) exec wordpress sh

%:
	@:
EOF

# ------------------------
# scripts
# ------------------------

cat <<EOF > scripts/setup.sh
#!/usr/bin/env bash

cp -n .env.example .env

mkdir -p src/wp-content/uploads
mkdir -p src/wp-content/plugins
mkdir -p src/wp-content/themes
mkdir -p src/wp-content/mu-plugins

echo "Entorno preparado"
EOF

chmod +x scripts/setup.sh

# ------------------------
# gitkeep uploads
# ------------------------

touch src/wp-content/uploads/.gitkeep

# ------------------------
# README
# ------------------------

cat <<EOF > README.md
# WordPress Development Environment

## Setup inicial

\`\`\`
cp .env.example .env
make setup
make up
\`\`\`

Abrir:

http://localhost:8080

## WP CLI

\`\`\`
make wp plugin list
\`\`\`

## Logs

\`\`\`
make logs
\`\`\`
EOF

echo ""
echo "Proyecto creado correctamente."
echo ""
echo "Siguiente paso:"
# echo "cd $PROJECT"
echo "cp .env.example .env"
echo "make setup"
echo "make up"
echo "" 