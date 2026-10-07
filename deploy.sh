#!/usr/bin/env bash
set -e

CONTAINER_NAME="snappymail-marabunta"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SNAPPY_VERSION="2.38.2"
VOLUME_CONFIG_DIR="/var/lib/docker/volumes/cbaa2cfded4f0656e92e20275f67575ce03906dfe73422dc4c1597fa4d612fbd/_data/_data_/_default_"

echo "=== Desplegando Tema MarabuntaSky, Plugins y Authentik Bridge ==="

# 1. Copiar CSS y recursos de imagen
docker cp "$THEME_DIR/styles.css" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/themes/Gmail/styles.css"
docker cp "$THEME_DIR/styles.css" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/themes/Default/styles.css"
docker cp "$THEME_DIR/marabunta-seal.png" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/static/marabunta-seal.png"

# 2. Copiar plantillas HTML personalizadas
docker cp "$THEME_DIR/Login.html" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/app/templates/Views/User/Login.html"
docker cp "$THEME_DIR/Index.html" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/app/templates/Index.html"

# 3. Copiar script de carga de credenciales
docker cp "$THEME_DIR/load-credentials.php" "$CONTAINER_NAME:/snappymail/snappymail/v/$SNAPPY_VERSION/app/load-credentials.php"

# 4. Copiar plugins
if [ -d "$THEME_DIR/plugins" ]; then
    mkdir -p "$VOLUME_CONFIG_DIR/plugins"
    cp -r "$THEME_DIR/plugins/"* "$VOLUME_CONFIG_DIR/plugins/"
fi

# 5. Ajustar propiedad de archivos para el servidor web
docker exec "$CONTAINER_NAME" chown -R www-data:www-data "/snappymail/snappymail/v/$SNAPPY_VERSION/"
chown -R 82:82 "$VOLUME_CONFIG_DIR/plugins/" 2>/dev/null || true

# 6. Activar plugins en application.ini si no están incluidos
INI_FILE="$VOLUME_CONFIG_DIR/configs/application.ini"
if [ -f "$INI_FILE" ]; then
    if ! grep -q "authentik-bridge" "$INI_FILE"; then
        sed -i -E 's/enabled_list = "([^"]+)"/enabled_list = "\1,authentik-bridge"/g' "$INI_FILE"
    fi
fi

# 7. Purgar caché de plantillas en volumen
if [ -d "$VOLUME_CONFIG_DIR/cache" ]; then
    rm -rf "$VOLUME_CONFIG_DIR/cache/"*
fi

# 8. Incrementar versión de caché en application.ini
if [ -f "$INI_FILE" ]; then
    sed -i -E 's/index = "v[0-9]+"/index = "v60"/g' "$INI_FILE"
    sed -i -E 's/fast_cache_index = "v[0-9]+"/fast_cache_index = "v60"/g' "$INI_FILE"
fi

# 9. Conectar contenedor a la red de Authentik si no lo está
docker network connect plantillas_red-plantillas "$CONTAINER_NAME" 2>/dev/null || true

# 10. Reiniciar contenedor
docker restart "$CONTAINER_NAME"

echo "=== ¡Despliegue completado con éxito! ==="
