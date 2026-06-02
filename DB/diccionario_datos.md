# Diccionario de Datos — Spotify Audio Interleave

**Base de datos:** `spotify_audio_interleave`  
**Motor:** InnoDB  
**Charset:** utf8mb4 / utf8mb4_unicode_ci  
**Versión:** 1.0.0

---

## 1. `usuarios`

Usuarios autenticados mediante OAuth con Spotify.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único del usuario |
| 2 | `nombre` | VARCHAR(255) | NO | — | — | Nombre público del usuario en Spotify |
| 3 | `email` | VARCHAR(255) | SÍ | NULL | — | Correo electrónico (opcional, depende de alcances de Spotify) |
| 4 | `password_hash` | VARCHAR(255) | SÍ | NULL | — | Hash bcrypt para futura autenticación local (actualmente no usado) |
| 5 | `foto` | VARCHAR(500) | SÍ | NULL | — | URL de la imagen de perfil de Spotify |
| 6 | `spotify_id` | VARCHAR(255) | NO | — | UQ | Identificador único del usuario en la plataforma de Spotify |
| 7 | `access_token` | TEXT | SÍ | NULL | — | Token de acceso vigente a la API de Spotify |
| 8 | `refresh_token` | TEXT | SÍ | NULL | — | Token para renovar el access_token cuando expira |
| 9 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Fecha y hora de registro del usuario |
| 10 | `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | — | Fecha y hora de la última actualización |

**Índices:**
- `PRIMARY KEY` (`id`)
- `UNIQUE INDEX` `uq_usuarios_spotify_id` (`spotify_id`)
- `INDEX` `idx_usuarios_email` (`email`)

**Relaciones:**
- 1:N → `spotify_tokens` (`usuario_id`)
- 1:N → `audios` (`usuario_id`)
- 1:N → `playlists_favoritas` (`usuario_id`)
- 1:N → `configuraciones` (`usuario_id`)
- 1:N → `historial_reproduccion` (`usuario_id`)
- 1:N → `logs_reproduccion` (`usuario_id`)
- 1:N → `sesiones` (`usuario_id`)

---

## 2. `spotify_tokens`

Historial de tokens OAuth emitidos por Spotify, incluyendo renovaciones.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único del registro de token |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario propietario del token |
| 3 | `access_token` | TEXT | NO | — | — | Token de acceso a la API de Spotify |
| 4 | `refresh_token` | TEXT | SÍ | NULL | — | Token de renovación (puede ser nulo en primera emisión) |
| 5 | `expires_at` | DATETIME | NO | — | — | Fecha y hora de expiración del access_token |
| 6 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Momento en que se emitió o renovó el token |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_spotify_tokens_usuario` (`usuario_id`)
- `INDEX` `idx_spotify_tokens_expiracion` (`expires_at`)

**Llave foránea:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

---

## 3. `audios`

Archivos MP3 subidos por el usuario para intercalar durante la reproducción.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único del audio |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario que subió el audio |
| 3 | `nombre` | VARCHAR(255) | NO | — | — | Nombre descriptivo asignado por el usuario |
| 4 | `archivo` | VARCHAR(500) | NO | — | — | Ruta relativa al archivo MP3 en el servidor |
| 5 | `duracion` | INT UNSIGNED | NO | 0 | — | Duración en segundos (metadata extraída con getID3) |
| 6 | `tamano` | INT UNSIGNED | NO | 0 | — | Tamaño del archivo en bytes |
| 7 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Fecha y hora de subida |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_audios_usuario` (`usuario_id`)
- `UNIQUE INDEX` `uq_audios_usuario_nombre` (`usuario_id`, `nombre`)

**Llave foránea:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

**Trigger asociado:**
- `trg_after_insert_audio`: Al insertar un audio, crea automáticamente su registro en `audio_estadisticas` con valores en cero.

**Relaciones:**
- N:1 → `usuarios`
- 1:N → `configuraciones` (`audio_id`)
- 1:1 → `audio_estadisticas` (`audio_id`)
- 1:N → `historial_reproduccion` (`audio_id`)

---

## 4. `playlists_favoritas`

Playlists de Spotify guardadas por el usuario para usarlas en configuraciones.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único del registro |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario que guardó la playlist |
| 3 | `spotify_playlist_id` | VARCHAR(255) | NO | — | — | Identificador de la playlist en la API de Spotify |
| 4 | `nombre_playlist` | VARCHAR(255) | NO | — | — | Nombre de la playlist (texto original de Spotify) |
| 5 | `imagen` | VARCHAR(500) | SÍ | NULL | — | URL de la imagen de portada de la playlist |
| 6 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Fecha y hora en que se guardó la playlist |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_playlists_fav_usuario` (`usuario_id`)
- `UNIQUE INDEX` `uq_playlist_usuario_spotify` (`usuario_id`, `spotify_playlist_id`)

**Llave foránea:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

**Relaciones:**
- N:1 → `usuarios`
- 1:N → `configuraciones` (`playlist_id`)
- 1:N → `historial_reproduccion` (`playlist_id`)

---

## 5. `configuraciones`

Configuración de reproducción que vincula una playlist favorita, un audio personalizado y un intervalo de intercalado.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único de la configuración |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario que creó la configuración |
| 3 | `playlist_id` | INT UNSIGNED | NO | — | FK | Referencia a la playlist favorita |
| 4 | `audio_id` | INT UNSIGNED | NO | — | FK | Referencia al audio personalizado |
| 5 | `canciones_intervalo` | TINYINT UNSIGNED | NO | 3 | — | Cada cuántas canciones se intercala el audio. Valores permitidos: 1, 2, 3, 5, 10 |
| 6 | `activo` | TINYINT(1) | NO | 1 | — | Estado de la configuración: 1=activa, 0=desactivada |
| 7 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Fecha y hora de creación |
| 8 | `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | — | Fecha y hora de última modificación |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_config_usuario` (`usuario_id`)
- `INDEX` `idx_config_playlist` (`playlist_id`)
- `INDEX` `idx_config_audio` (`audio_id`)
- `INDEX` `idx_config_activo` (`activo`)

**Llaves foráneas:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`
- `playlist_id` → `playlists_favoritas(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`
- `audio_id` → `audios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

---

## 6. `historial_reproduccion`

Registro histórico de cada sesión de reproducción ejecutada por el usuario.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único del registro histórico |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario que reprodujo |
| 3 | `playlist_id` | INT UNSIGNED | SÍ | NULL | FK | Referencia a la playlist utilizada (SET NULL si se elimina) |
| 4 | `audio_id` | INT UNSIGNED | SÍ | NULL | FK | Referencia al audio intercalado (SET NULL si se elimina) |
| 5 | `fecha_reproduccion` | DATETIME | NO | — | — | Fecha y hora de inicio de la reproducción |
| 6 | `canciones_escuchadas` | INT UNSIGNED | NO | 0 | — | Cantidad total de canciones reproducidas en la sesión |
| 7 | `duracion_total` | INT UNSIGNED | NO | 0 | — | Duración total de la sesión en segundos |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_historial_usuario` (`usuario_id`)
- `INDEX` `idx_historial_fecha` (`fecha_reproduccion`)
- `INDEX` `idx_historial_playlist` (`playlist_id`)
- `INDEX` `idx_historial_audio` (`audio_id`)

**Llaves foráneas:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`
- `playlist_id` → `playlists_favoritas(id)` — `ON DELETE SET NULL ON UPDATE CASCADE`
- `audio_id` → `audios(id)` — `ON DELETE SET NULL ON UPDATE CASCADE`

> **Nota:** `playlist_id` y `audio_id` usan `ON DELETE SET NULL` para preservar el historial incluso si los recursos referenciados son eliminados posteriormente.

---

## 7. `logs_reproduccion`

Registro detallado de eventos ocurridos durante la reproducción. Útil para depuración, auditoría y análisis de uso.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único del log |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario asociado al evento |
| 3 | `tipo_evento` | VARCHAR(50) | NO | — | — | Tipo de evento. Valores típicos: `inicio`, `pausa`, `reanudacion`, `fin`, `error`, `audio_intercalado`, `cambio_cancion` |
| 4 | `descripcion` | TEXT | SÍ | NULL | — | Descripción detallada del evento (mensaje de error, nombre de canción, etc.) |
| 5 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Momento exacto en que ocurrió el evento |

**Índices:**
- `PRIMARY KEY` (`id`)
- `INDEX` `idx_logs_usuario` (`usuario_id`)
- `INDEX` `idx_logs_tipo` (`tipo_evento`)
- `INDEX` `idx_logs_fecha` (`created_at`)

**Llave foránea:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

---

## 8. `sesiones`

Control de sesiones activas de los usuarios en la aplicación web.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único de la sesión |
| 2 | `usuario_id` | INT UNSIGNED | NO | — | FK | Referencia al usuario dueño de la sesión |
| 3 | `token` | VARCHAR(255) | NO | — | UQ | Token de sesión único (generado con `random_bytes(32)` y convertido a hexadecimal) |
| 4 | `ip` | VARCHAR(45) | SÍ | NULL | — | Dirección IP desde donde se conectó (longitud 45 para soportar IPv6) |
| 5 | `user_agent` | VARCHAR(500) | SÍ | NULL | — | Cadena User-Agent del navegador o dispositivo |
| 6 | `ultimo_acceso` | DATETIME | SÍ | NULL | — | Fecha y hora del último request asociado a esta sesión |
| 7 | `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | — | Fecha y hora de creación de la sesión |

**Índices:**
- `PRIMARY KEY` (`id`)
- `UNIQUE INDEX` `uq_sesiones_token` (`token`)
- `INDEX` `idx_sesiones_usuario` (`usuario_id`)
- `INDEX` `idx_sesiones_ultimo_acceso` (`ultimo_acceso`)

**Llave foránea:**
- `usuario_id` → `usuarios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

---

## 9. `audio_estadisticas`

Estadísticas de uso acumuladas de cada audio personalizado. Relación 1:1 con la tabla `audios`.

| # | Columna | Tipo | Nulo | Default | Llave | Descripción |
|---|---|---|---|---|---|---|
| 1 | `id` | INT UNSIGNED | NO | AUTO_INCREMENT | PK | Identificador único de la estadística |
| 2 | `audio_id` | INT UNSIGNED | NO | — | FK/UQ | Referencia al audio (índice único garantiza relación 1:1) |
| 3 | `reproducciones` | INT UNSIGNED | NO | 0 | — | Contador de veces que se ha reproducido este audio |
| 4 | `tiempo_total_reproducido` | INT UNSIGNED | NO | 0 | — | Tiempo total acumulado en segundos |
| 5 | `ultima_reproduccion` | DATETIME | SÍ | NULL | — | Fecha y hora de la última vez que se reprodujo |

**Índices:**
- `PRIMARY KEY` (`id`)
- `UNIQUE INDEX` `uq_audio_estadisticas` (`audio_id`)

**Llave foránea:**
- `audio_id` → `audios(id)` — `ON DELETE CASCADE ON UPDATE CASCADE`

> **Creación automática:** Un trigger `AFTER INSERT` en la tabla `audios` inserta automáticamente el registro correspondiente en `audio_estadisticas` con `reproducciones = 0` y `tiempo_total_reproducido = 0`.

---

## Resumen de tablas

| # | Tabla | Descripción | Filas iniciales | Relaciones |
|---|---|---|---|---|
| 1 | `usuarios` | Usuarios autenticados con Spotify | 0 | 7 salientes |
| 2 | `spotify_tokens` | Historial de tokens OAuth | 0 | 1 entrante |
| 3 | `audios` | Archivos MP3 subidos | 0 | 3 salientes, 1 entrante |
| 4 | `playlists_favoritas` | Playlists de Spotify guardadas | 0 | 2 salientes, 1 entrante |
| 5 | `configuraciones` | Configuración playlist-audio-intervalo | 0 | 3 entrantes |
| 6 | `historial_reproduccion` | Historial de sesiones | 0 | 3 entrantes |
| 7 | `logs_reproduccion` | Logs de eventos | 0 | 1 entrante |
| 8 | `sesiones` | Sesiones activas | 0 | 1 entrante |
| 9 | `audio_estadisticas` | Estadísticas de audios | 0 | 1 entrante (creada vía trigger) |

## Convenciones generales

| Concepto | Convención | Ejemplo |
|---|---|---|
| Nombres de tablas | Plural, snake_case, español | `playlists_favoritas` |
| Nombres de columnas | Singular, snake_case, español | `canciones_intervalo` |
| Llave primaria | `id` INT UNSIGNED AUTO_INCREMENT | `id` |
| Llave foránea | tabla_referencia + `_id` | `usuario_id`, `audio_id` |
| Timestamp creación | `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP | `created_at` |
| Timestamp actualización | `updated_at` TIMESTAMP ... ON UPDATE | `updated_at` |
| Motor | InnoDB | — |
| Charset | utf8mb4 | — |
| Collation | utf8mb4_unicode_ci | — |
| Borrado en cascada | `ON DELETE CASCADE` (excepto historial) | — |
| Nulos permitidos | Solo donde semanticamente tenga sentido | `email`, `foto` |
