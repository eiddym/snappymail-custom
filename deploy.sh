#!/usr/bin/env bash
set -e

CONTAINER_NAME="snappymail-marabunta"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SNAPPY_VERSION="2.38.2"
VOLUME_CONFIG_DIR="/var/lib/docker/volumes/cbaa2cfded4f0656e92e20275f67575ce03906dfe73422dc4c1597fa4d612fbd/_data/_data_/_default_"

echo "=== Desplegando Tema MarabuntaSky en SnappyMail ==="

# 1. Copiar CSS y recursos de imagen
docker cp "$THEME_DIR/styles.css" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/themes/Gmail/styles.css"
docker cp "$THEME_DIR/styles.css" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/themes/Default/styles.css"
docker cp "$THEME_DIR/marabunta-seal.png" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/static/marabunta-seal.png"

# 2. Copiar plantillas HTML personalizadas
docker cp "$THEME_DIR/Login.html" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/app/templates/Views/User/Login.html"
docker cp "$THEME_DIR/Index.html" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/app/templates/Index.html"

# 3. Ajustar propiedad de archivos para el servidor web
docker exec "$CONTAINER_NAME" chown -R www-data:www-data "/snappymail/snappymail/v/$SNAPPY_VERSION/"

# 4. Purgar caché de plantillas en volumen
if [ -d "$VOLUME_CONFIG_DIR/cache" ]; then
    rm -rf "$VOLUME_CONFIG_DIR/cache/"*
fi

# 5. Incrementar versión de caché en application.ini
if [ -f "$VOLUME_CONFIG_DIR/configs/application.ini" ]; then
    sed -i -E 's/index = "v[0-9]+"/index = "v30"/g' "$VOLUME_CONFIG_DIR/configs/application.ini"
    sed -i -E 's/fast_cache_index = "v[0-9]+"/fast_cache_index = "v30"/g' "$VOLUME_CONFIG_DIR/configs/application.ini"
fi

# 6. Reiniciar contenedor
docker restart "$CONTAINER_NAME"

echo "=== ¡Despliegue completado con éxito! ==="
