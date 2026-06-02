# Spotify Audi

Aplicación web que permite conectar tu cuenta de Spotify, seleccionar playlists y reproducirlas intercalando audios personalizados que hayas subido previamente.

## Requisitos

- PHP 8.2+
- MySQL 5.7+
- Extensión PHP: `curl`, `pdo_mysql`
- Composer (opcional, para getID3)
- Cuenta de Spotify (gratuita o premium)

## Instalación

### 1. Clonar el repositorio

```bash
git clone <repo-url>
cd spotify-audi
```

### 2. Configurar base de datos

Ejecutar el script SQL:

```bash
mysql -u root -p < DB/spotify_audi.sql
```

### 3. Configurar variables de entorno

Copiar `.env.example` a `.env` y editar:

```bash
cp .env.example .env
```

Editar el archivo `.env` con tus credenciales:

```env
DB_HOST=localhost
DB_NAME=spotify_audi
DB_USER=root
DB_PASSWORD=

SPOTIFY_CLIENT_ID=tu_client_id
SPOTIFY_CLIENT_SECRET=tu_client_secret
SPOTIFY_REDIRECT_URI=https://codigos.test/publi/index.php?accion=callback

APP_NAME=Spotify Audi
DEBUG=true
MAX_AUDIO_SIZE=10
UPLOAD_PATH=uploads/audios
```

### 4. Configurar Spotify Developer

1. Ve a [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
2. Crea una nueva aplicación
3. Agrega la URL de redirección: `https://codigos.test/publi/index.php?accion=callback`
4. Copia el **Client ID** y **Client Secret** al archivo `.env`

### 5. Iniciar la aplicación

```bash
php -S localhost:8000
```

O usa Laragon/XAMPP/WAMP apuntando a la carpeta del proyecto.

## Estructura MVC

```
├── index.php                 # Front Controller (entrada única)
├── .env                      # Variables de entorno
├── DB/
│   └── spotify_audi.sql      # Esquema de base de datos
├── Controlador/              # Controladores
│   ├── AuthController.php    # Autenticación Spotify OAuth
│   ├── DashboardController.php
│   ├── AudioController.php   # CRUD de audios
│   ├── PlaylistController.php
│   └── PlayerController.php  # Lógica del reproductor
├── Modelo/                   # Modelos y acceso a datos
│   ├── Conexion.php          # Conexión PDO (singleton)
│   ├── Usuario.php
│   ├── Audio.php
│   └── Configuracion.php
├── Servicio/                 # Servicios externos
│   └── SpotifyService.php    # API de Spotify OAuth + Web API
├── Funciones/                # Helpers
│   └── helpers.php           # Funciones utilitarias
├── Vista/                    # Vistas (PHP + HTML + Tailwind)
│   ├── layouts/              # header.php, footer.php
│   ├── partials/             # navbar.php, sidebar.php
│   ├── auth/
│   ├── dashboard/
│   ├── audios/
│   ├── playlists/
│   └── player/
└── public/                   # Assets estáticos
    ├── css/app.css
    └── js/
        ├── app.js
        ├── player.js
        └── sweetalert.js
```

## Flujo de autenticación

1. El usuario hace clic en "Continuar con Spotify"
2. Se redirige a Spotify Authorization con PKCE
3. Usuario autoriza la aplicación
4. Spotify redirige al callback con código de autorización
5. El backend intercambia el código por tokens (access + refresh)
6. Se guarda/actualiza el usuario en la base de datos
7. Se inicia sesión y redirige al dashboard
8. El refresh token se usa automáticamente cuando expira el access token

## Flujo de reproducción

1. Usuario selecciona playlist y configura un audio con intervalo (ej: cada 3 canciones)
2. Usuario presiona "Iniciar reproducción"
3. El sistema inicia la playlist en el dispositivo activo de Spotify
4. Por cada canción terminada, el contador aumenta
5. Cuando el contador alcanza el intervalo:
   - Pausa Spotify
   - Muestra overlay con el audio personalizado
   - Reproduce el audio MP3 subido
   - Al terminar, reanuda Spotify
6. El ciclo se repite hasta terminar la playlist

## Seguridad

- **CSRF**: Tokens en todos los formularios POST
- **XSS**: Output escaping con `htmlspecialchars()` mediante helper `e()`
- **SQL Injection**: Prepared Statements PDO con `ATTR_EMULATE_PREPARES = false`
- **Autenticación**: OAuth 2.0 con PKCE, refresh automático de tokens
- **Sesiones**: Control de acceso por sesión, regeneración de ID
- **Archivos**: Validación de tipo MP3, tamaño máximo configurable

## Tecnologías

- **Backend**: PHP 8.2+ nativo (sin framework)
- **Frontend**: HTML5, TailwindCSS (CDN), JavaScript ES6+
- **Base de datos**: MySQL con PDO
- **API externa**: Spotify Web API
- **Librerías**: SweetAlert2, Font Awesome
- **Arquitectura**: MVC, Código limpio, Componentes reutilizables
