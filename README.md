# Tema "MarabuntaSky" para SnappyMail Webmail

Repositorio del tema visual personalizado **Glassmorphism en Modo Oscuro** diseñado para **Cooperativa Minera Marabunta R.L.**.

## 🌟 Características Principales
- **Sello Institucional HD**: Sello oficial transparente de la Cooperativa Minera Marabunta R.L. centrado arriba del título principal.
- **Modo Oscuro Glassmorphic**: Tarjeta de acceso con desenfoque de fondo (`backdrop-filter: blur(25px)`), bordes sutiles y malla de fondo con degradados oscuros.
- **Ojito de Contraseña Activo**: Alternador interactivo entre visibilidad de contraseña (`type="password"` / `type="text"`) con iluminación activa en blanco (`#ffffff`).
- **Pie de Página de Seguridad**: Leyenda de seguridad institucional `🛡 Seguro • Privado • Institucional`.
- **Botonera e Interfaz Interna**: Adaptación de la barra de herramientas, selector de carpetas y cuadros de diálogo a la paleta corporativa.

## 📦 Estructura del Repositorio
- `styles.css`: Hoja de estilos CSS principal con variables de SnappyMail y sello en Base64.
- `marabunta-seal.png`: Sello circular oficial transparente de alta resolución.
- `Login.html`: Plantilla de inicio de sesión con íconos de campo y pie de página.
- `Index.html`: Plantilla base con el event listener global para el alternador de contraseña.
- `deploy.sh`: Script ejecutable de automatización para desplegar los cambios en el contenedor Docker.

## 🔧 Instalación y Despliegue

```bash
chmod +x deploy.sh
./deploy.sh
```

© 2026 Cooperativa Minera Marabunta R.L. - Todos los derechos reservados.
