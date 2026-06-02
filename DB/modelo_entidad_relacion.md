# Modelo Entidad-Relación — Spotify Audio Interleave

## Diagrama de relaciones

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              spotify_audio_interleave                        │
└─────────────────────────────────────────────────────────────────────────────┘

  ┌───────────────────┐        ┌──────────────────────────┐
  │     sesiones      │        │    spotify_tokens         │
  │───────────────────│        │──────────────────────────│
  │ id               │──┐     │ id                       │──┐
  │ usuario_id (FK)  │──┼─────│ usuario_id (FK)         │──┼───┐
  │ token            │  │     │ access_token             │  │   │
  │ ip               │  │     │ refresh_token            │  │   │
  │ user_agent       │  │     │ expires_at               │  │   │
  │ ultimo_acceso    │  │     │ created_at               │  │   │
  │ created_at       │  │     └──────────────────────────┘  │   │
  └───────────────────┘  │                                  │   │
                          │                                  │   │
  ┌───────────────────┐  │   ┌──────────────────────────┐   │   │
  │  logs_reproduccion │  │   │       usuarios              │   │   │
  │───────────────────│  │   │──────────────────────────│   │   │   │
  │ id               │  │   │ id                       │◄──┘   │   │
  │ usuario_id (FK)  │──┘   │ nombre                   │       │   │
  │ tipo_evento      │      │ email                    │       │   │
  │ descripcion      │      │ password_hash            │       │   │
  │ created_at       │      │ foto                     │       │   │
  └───────────────────┘      │ spotify_id              │       │   │
                              │ access_token            │       │   │
  ┌───────────────────┐      │ refresh_token           │       │   │
  │     audios        │      │ created_at              │       │   │
  │───────────────────│      │ updated_at              │       │   │
  │ id               │──┐   └───────────┬──────────────┘       │   │
  │ usuario_id (FK)  │──┼───────────────┘                      │   │
  │ nombre           │  │                                      │   │
  │ archivo          │  │   ┌──────────────────────────┐       │   │
  │ duracion         │  │   │   playlists_favoritas    │       │   │
  │ tamano           │  │   │──────────────────────────│       │   │
  │ created_at       │  │   │ id                       │──┐    │   │
  └───────┬───────────┘  │   │ usuario_id (FK)         │──┼────┘   │
          │              │   │ spotify_playlist_id     │  │        │
          │ 1:1          │   │ nombre_playlist         │  │        │
          ▼              │   │ imagen                  │  │        │
  ┌───────────────────┐  │   │ created_at              │  │        │
  │ audio_estadisticas│  │   └──────────────────────────┘  │        │
  │───────────────────│  │                                │        │
  │ id               │  │   ┌──────────────────────────┐   │        │
  │ audio_id (FK)   │──┘   │   configuraciones         │   │        │
  │ reproducciones   │      │──────────────────────────│   │        │
  │ tiempo_total...  │      │ id                       │   │        │
  │ ultima_repro...  │      │ usuario_id (FK)          │───┘        │
  └───────────────────┘      │ playlist_id (FK)        │◄───────────┘
                              │ audio_id (FK)           │◄──────────┘
  ┌───────────────────┐      │ canciones_intervalo     │
  │ historial_repro... │      │ activo                  │
  │───────────────────│      │ created_at              │
  │ id               │      │ updated_at              │
  │ usuario_id (FK)  │──┐   └──────────────────────────┘
  │ playlist_id (FK)  │──┼──┐
  │ audio_id (FK)     │──┼──┼──┐
  │ fecha_repro...    │  │  │  │
  │ canciones_esc...  │  │  │  │
  │ duracion_total    │  │  │  │
  └───────────────────┘  │  │  │
                          │  │  │
  ┌──────────────────────┘  │  │
  │  ┌──────────────────────┘  │
  │  │  ┌──────────────────────┘
  ▼  ▼  ▼
  (referencias a playlists_favoritas, audios, usuarios)
```

## Leyenda de relaciones

| Símbolo | Significado |
|---------|-------------|
| `1` | Uno |
| `N` | Muchos |
| `1:1` | Uno a uno |
| `1:N` | Uno a muchos |
| `FK` | Foreign Key (Llave foránea) |

## Catálogo de relaciones

### 1. usuarios → spotify_tokens (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `spotify_tokens` | `usuarios` | N:1 | `spotify_tokens.usuario_id` → `usuarios.id` |

Un usuario puede tener **muchos** registros de tokens (uno por cada renovación).  
Cada token pertenece a **un solo** usuario.

**Borrado en cascada:** Si se elimina un usuario, se eliminan todos sus tokens.

---

### 2. usuarios → audios (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `audios` | `usuarios` | N:1 | `audios.usuario_id` → `usuarios.id` |

Un usuario puede tener **muchos** audios subidos.  
Cada audio pertenece a **un solo** usuario.

**Borrado en cascada:** Si se elimina un usuario, se eliminan todos sus audios (y sus archivos MP3 deben limpiarse en la lógica de aplicación).

---

### 3. usuarios → playlists_favoritas (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `playlists_favoritas` | `usuarios` | N:1 | `playlists_favoritas.usuario_id` → `usuarios.id` |

Un usuario puede guardar **muchas** playlists favoritas.  
Cada playlist guardada pertenece a **un solo** usuario.

**Restricción única compuesta:** `(usuario_id, spotify_playlist_id)` — un usuario no puede guardar la misma playlist de Spotify dos veces.

---

### 4. usuarios → configuraciones (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `configuraciones` | `usuarios` | N:1 | `configuraciones.usuario_id` → `usuarios.id` |

Un usuario puede crear **muchas** configuraciones de reproducción.  
Cada configuración pertenece a **un solo** usuario.

---

### 5. usuarios → historial_reproduccion (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `historial_reproduccion` | `usuarios` | N:1 | `historial_reproduccion.usuario_id` → `usuarios.id` |

Un usuario puede tener **muchos** registros en el historial.  
Cada registro histórico pertenece a **un solo** usuario.

---

### 6. usuarios → logs_reproduccion (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `logs_reproduccion` | `usuarios` | N:1 | `logs_reproduccion.usuario_id` → `usuarios.id` |

Un usuario puede tener **muchos** logs de eventos.  
Cada log pertenece a **un solo** usuario.

---

### 7. usuarios → sesiones (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `sesiones` | `usuarios` | N:1 | `sesiones.usuario_id` → `usuarios.id` |

Un usuario puede tener **muchas** sesiones activas (diferentes dispositivos/navegadores).  
Cada sesión pertenece a **un solo** usuario.

---

### 8. audios → configuraciones (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `configuraciones` | `audios` | N:1 | `configuraciones.audio_id` → `audios.id` |

Un audio puede estar asociado a **muchas** configuraciones (diferentes playlists).  
Cada configuración utiliza **un solo** audio.

**Borrado en cascada:** Si se elimina un audio, se eliminan todas las configuraciones que lo usan.

---

### 9. audios → audio_estadisticas (1:1)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `audio_estadisticas` | `audios` | 1:1 | `audio_estadisticas.audio_id` → `audios.id` |

Cada audio tiene **exactamente un** registro de estadísticas.  
Cada registro de estadísticas pertenece a **exactamente un** audio.

**Restricción única:** `audio_estadisticas.audio_id` tiene índice `UNIQUE`, garantizando la relación 1:1.

**Automático:** Se crea mediante un `TRIGGER AFTER INSERT` en la tabla `audios`.

---

### 10. audios → historial_reproduccion (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `historial_reproduccion` | `audios` | N:1 | `historial_reproduccion.audio_id` → `audios.id` |

Un audio puede aparecer en **muchos** registros del historial.  
Cada registro del historial referencia **un solo** audio.

**Borrado con SET NULL:** Si se elimina un audio, el historial conserva el registro pero con `audio_id = NULL`.

---

### 11. playlists_favoritas → configuraciones (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `configuraciones` | `playlists_favoritas` | N:1 | `configuraciones.playlist_id` → `playlists_favoritas.id` |

Una playlist guardada puede estar en **muchas** configuraciones.  
Cada configuración referencia **una sola** playlist guardada.

---

### 12. playlists_favoritas → historial_reproduccion (1:N)

| Tabla origen | Tabla destino | Tipo | Llave foránea |
|---|---|---|---|
| `historial_reproduccion` | `playlists_favoritas` | N:1 | `historial_reproduccion.playlist_id` → `playlists_favoritas.id` |

Una playlist guardada puede aparecer en **muchos** registros del historial.  
Cada registro del historial referencia **una sola** playlist guardada.

**Borrado con SET NULL:** Si se elimina la playlist guardada, el historial conserva el registro pero con `playlist_id = NULL`.

---

## Resumen de cardinalidades

```
usuarios (1) ──<N> spotify_tokens
usuarios (1) ──<N> audios
usuarios (1) ──<N> playlists_favoritas
usuarios (1) ──<N> configuraciones
usuarios (1) ──<N> historial_reproduccion
usuarios (1) ──<N> logs_reproduccion
usuarios (1) ──<N> sesiones
audios    (1) ──<N> configuraciones
audios    (1) ──<1> audio_estadisticas
audios    (1) ──<N> historial_reproduccion
playlists_favoritas (1) ──<N> configuraciones
playlists_favoritas (1) ──<N> historial_reproduccion
```

## Convenciones de nomenclatura

- **Nombres de tablas:** Plural en español, snake_case (`usuarios`, `playlists_favoritas`)
- **Nombres de columnas:** Singular en español, snake_case (`spotify_id`, `canciones_intervalo`)
- **Llaves primarias:** Siempre `id` (entero sin signo, auto-increment)
- **Llaves foráneas:** Nombre de la tabla referenciada + `_id` (`usuario_id`, `audio_id`)
- **Timestamps:** `created_at`, `updated_at` donde aplique
- **Motor:** InnoDB para integridad referencial
- **Charset:** `utf8mb4` con collation `utf8mb4_unicode_ci`
