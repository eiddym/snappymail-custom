# SnappyMail Customization & Authentik Bridge

Repositorio oficial de personalización para el cliente Webmail SnappyMail de Cooperativa Minera Marabunta R.L.

## 🚀 Características Principales

1. **Tema Visual MarabuntaSky**:
   - Diseño moderno en tono celeste institucional y tarjetas con efecto *glassmorphic*.
   - Fondo uniforme de grilla continua sin cortes verticales.
   - Sello institucional Marabunta R.L. estilizado.
   - Alto contraste optimizado en menús laterales de administración y desplegables (`<select> / <option>`).

2. **Plugin `toggle-password`**:
   - Muestra u oculta la contraseña en el formulario de login mediante un botón con delegación de eventos (`document.addEventListener`).
   - Mantiene la compatibilidad si KnockoutJS vuelve a renderizar los campos.

3. **Plugin `authentik-bridge`**:
   - **Autenticación Unificada (SSO LDAP + IMAP)**:
     - **Hook `login.credentials.step-2`**: Valida las credenciales introducidas por el usuario contra **Authentik LDAP Outpost** (`ldap://authentik-ldap-outpost:3389`).
     - **Hook `login.credentials`**: Si el bind LDAP en Authentik es exitoso, descifra e inyecta la contraseña real del servidor IMAP de Hostinger desde un almacén local cifrado.
   - Cifrado simétrico de alta seguridad mediante `libsodium` (`sodium_crypto_secretbox`).

---

## 🛠️ Estructura del Proyecto

```
MarabuntaSky/
├── plugins/
│   ├── authentik-bridge/
│   │   └── index.php         # Plugin PHP para validación LDAP + sustitución de clave IMAP
│   └── toggle-password/
│       ├── index.php         # Registro del plugin de visibilidad de contraseña
│       └── toggle.js         # Lógica JS del ojo para mostrar/ocultar clave
├── Login.html                # Plantilla personalizada de Login
├── Index.html                # Plantilla base
├── styles.css                # Estilos del tema MarabuntaSky
├── load-credentials.php      # Helper CLI para guardar credenciales cifradas de Hostinger
├── deploy.sh                 # Script de despliegue automatizado en Docker
└── README.md                 # Documentación del proyecto
```

---

## 🔐 Administración de Credenciales Hostinger

Las contraseñas reales de Hostinger no se almacenan en texto plano. Se cifran utilizando `libsodium` con una clave maestra (`CREDENTIAL_MASTER_KEY`).

### Cargar o actualizar una credencial

Ejecutar el script helper mediante CLI dentro del contenedor:

```bash
docker exec -e CREDENTIAL_MASTER_KEY="aOFx40L6HDvy4GfRdAjkVvtOa6Ph7W1s776oMRm0DA8=" snappymail-marabunta \
  php /snappymail/snappymail/v/2.38.2/app/load-credentials.php usuario@marabuntarl.com 'ContraseñaHostinger'
```

### Ubicación del Almacén Cifrado
El archivo cifrado se guarda en:
`/var/lib/snappymail/_data_/_default_/hostinger-credentials.json` con permisos de lectura restringidos (`0600`).

---

## ⚙️ Variables de Entorno

| Variable | Descripción | Valor por Defecto |
|---|---|---|
| `AUTHENTIK_LDAP_HOST` | Dirección del LDAP Outpost de Authentik | `ldap://authentik-ldap-outpost:3389` |
| `AUTHENTIK_LDAP_BASE_DN` | Base DN para buscar usuarios en Authentik | `ou=users,dc=ldap,dc=goauthentik,dc=io` |
| `CREDENTIAL_MASTER_KEY` | Clave Maestra de 32 bytes (Base64) para descifrado de contraseñas | Clave de respaldo local |
| `SNAPPYMAIL_CREDENTIAL_STORE` | Ruta al archivo JSON con credenciales cifradas | `/var/lib/snappymail/_data_/_default_/hostinger-credentials.json` |

---

## 📦 Despliegue Automatizado

Para desplegar actualizaciones en el contenedor SnappyMail en producción:

```bash
./deploy.sh
```

El script realiza automáticamente:
1. Copia de estilos CSS y assets al contenedor.
2. Copia de plantillas HTML y plugins PHP.
3. Activación de plugins en `application.ini`.
4. Limpieza de caché e incremento del identificador de versión (`v60`).
5. Conexión a la red Docker `plantillas_red-plantillas`.
6. Reinicio del contenedor `snappymail-marabunta`.
