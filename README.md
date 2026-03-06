# WordPress Development Environment

## Setup inicial

```
cp .env.example .env
make setup
make up
```

`make setup` tambien escribe `HOST_UID` y `HOST_GID` en `.env` para evitar problemas de permisos en `src/wp-content/*` cuando Docker escribe archivos.

Abrir:

http://localhost:8080

## WP CLI

```
make wp plugin list
```

## Logs

```
make logs
```

## Recuperar permisos en el host

Si ya tienes archivos creados con otro propietario, desde la raiz del proyecto ejecuta:

```bash
sudo chown -R "$(id -u):$(id -g)" src/wp-content
```
