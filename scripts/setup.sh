#!/usr/bin/env bash

cp -n .env.example .env

mkdir -p src/wp-content/uploads
mkdir -p src/wp-content/plugins
mkdir -p src/wp-content/themes
mkdir -p src/wp-content/mu-plugins

echo "Entorno preparado"
