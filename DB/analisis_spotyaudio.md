# Análisis de Base de Datos — spotyaudio

**Fecha:** 31/05/2026  
**Motor:** MySQL 8.4.3 (MariaDB compatible)  
**Charset:** utf8mb4 / utf8mb4_unicode_ci  
**Collation:** utf8mb3_spanish_ci (global) / utf8mb4_unicode_ci (tablas)

---

## Resumen general

| Métrica | Valor |
|---|---|
| Tablas | 9 |
| Llaves primarias | 9 |
| Llaves foráneas | 12 |
| Índices únicos | 5 |
| Índices secundarios | 17 |
| Triggers | 1 (`trg_after_insert_audio`) |
| Filas totales | 0 |

---

## Tablas encontradas

| # | Tabla | Engine | Filas | Tamaño estimado | Descripción |
|---|---|---|---|---|---|
| 1 | `usuarios` | InnoDB | 0 | ~1KB | Usuarios autenticados con Spotify |
| 2 | `spotify_tokens` | InnoDB | 0 | ~1KB | Historial de tokens OAuth |
| 3 | `audios` | InnoDB | 0 | ~1KB | Archivos MP3 subidos por el usuario |
| 4 | `playlists_favoritas` | InnoDB | 0 | ~1KB | Playlists de Spotify guardadas |
| 5 | `configuraciones` | InnoDB | 0 | ~1KB | Configuración playlist-audio-intervalo |
| 6 | `historial_reproduccion` | InnoDB | 0 | ~1KB | Historial de sesiones de reproducción |
| 7 | `logs_reproduccion` | InnoDB | 0 | ~1KB | Logs detallados de eventos |
| 8 | `sesiones` | InnoDB | 0 | ~1KB | Sesiones activas de usuarios |
| 9 | `audio_estadisticas` | InnoDB | 0 | ~1KB | Estadísticas de uso de audios |

---

## Estructura detallada

### 1. `usuarios`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| nombre | VARCHAR(255) | NO | — | — |
| email | VARCHAR(255) | SÍ | NULL | — |
| password_hash | VARCHAR(255) | SÍ | NULL | — |
| foto | VARCHAR(500) | SÍ | NULL | — |
| spotify_id | VARCHAR(255) | NO | — | UQ |
| access_token | TEXT | SÍ | NULL | — |
| refresh_token | TEXT | SÍ | NULL | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | — |

**Índices:** 1 PK, 1 UQ (`spotify_id`), 1 INDEX (`email`)  
**Relaciones salientes:** spotify_tokens, audios, playlists_favoritas, configuraciones, historial_reproduccion, logs_reproduccion, sesiones

### 2. `spotify_tokens`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| access_token | TEXT | NO | — | — |
| refresh_token | TEXT | SÍ | NULL | — |
| expires_at | DATETIME | NO | — | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |

**Índices:** 1 PK, 2 INDEX (`usuario_id`, `expires_at`)  
**Relaciones entrantes:** usuarios

### 3. `audios`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| nombre | VARCHAR(255) | NO | — | — |
| archivo | VARCHAR(500) | NO | — | — |
| duracion | INT UNSIGNED | NO | 0 | — |
| tamano | INT UNSIGNED | NO | 0 | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |

**Índices:** 1 PK, 1 UQ (`usuario_id`, `nombre`), 1 INDEX (`usuario_id`)  
**Relaciones:** entrante desde usuarios; saliente hacia configuraciones, historial_reproduccion, audio_estadisticas

### 4. `playlists_favoritas`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| spotify_playlist_id | VARCHAR(255) | NO | — | — |
| nombre_playlist | VARCHAR(255) | NO | — | — |
| imagen | VARCHAR(500) | SÍ | NULL | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |

**Índices:** 1 PK, 1 UQ (`usuario_id`, `spotify_playlist_id`), 1 INDEX (`usuario_id`)  
**Relaciones:** entrante desde usuarios; saliente hacia configuraciones, historial_reproduccion

### 5. `configuraciones`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| playlist_id | INT UNSIGNED | NO | — | FK → playlists_favoritas(id) CASCADE |
| audio_id | INT UNSIGNED | NO | — | FK → audios(id) CASCADE |
| canciones_intervalo | TINYINT UNSIGNED | NO | 3 | — |
| activo | TINYINT(1) | NO | 1 | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | — |

**Índices:** 1 PK, 4 INDEX (`usuario_id`, `playlist_id`, `audio_id`, `activo`)  
**Relaciones entrantes:** usuarios, playlists_favoritas, audios

### 6. `historial_reproduccion`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| playlist_id | INT UNSIGNED | SÍ | NULL | FK → playlists_favoritas(id) SET NULL |
| audio_id | INT UNSIGNED | SÍ | NULL | FK → audios(id) SET NULL |
| fecha_reproduccion | DATETIME | NO | — | — |
| canciones_escuchadas | INT UNSIGNED | NO | 0 | — |
| duracion_total | INT UNSIGNED | NO | 0 | — |

**Índices:** 1 PK, 4 INDEX (`usuario_id`, `fecha_reproduccion`, `playlist_id`, `audio_id`)  
**Relaciones entrantes:** usuarios, playlists_favoritas, audios

### 7. `logs_reproduccion`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| tipo_evento | VARCHAR(50) | NO | — | — |
| descripcion | TEXT | SÍ | NULL | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |

**Índices:** 1 PK, 3 INDEX (`usuario_id`, `tipo_evento`, `created_at`)  
**Relaciones entrantes:** usuarios

### 8. `sesiones`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| usuario_id | INT UNSIGNED | NO | — | FK → usuarios(id) CASCADE |
| token | VARCHAR(255) | NO | — | UQ |
| ip | VARCHAR(45) | SÍ | NULL | — |
| user_agent | VARCHAR(500) | SÍ | NULL | — |
| ultimo_acceso | DATETIME | SÍ | NULL | — |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | — |

**Índices:** 1 PK, 1 UQ (`token`), 2 INDEX (`usuario_id`, `ultimo_acceso`)  
**Relaciones entrantes:** usuarios

### 9. `audio_estadisticas`
| Columna | Tipo | Nulo | Default | PK/UQ/FK |
|---|---|---|---|---|
| id | INT UNSIGNED | NO | AUTO_INCREMENT | PK |
| audio_id | INT UNSIGNED | NO | — | FK/UQ → audios(id) CASCADE |
| reproducciones | INT UNSIGNED | NO | 0 | — |
| tiempo_total_reproducido | INT UNSIGNED | NO | 0 | — |
| ultima_reproduccion | DATETIME | SÍ | NULL | — |

**Índices:** 1 PK, 1 UQ (`audio_id`)  
**Relación entrante:** audios (1:1 forzada por UNIQUE)

---

## Relaciones entre entidades

| # | Origen | Destino | Tipo | FK | Comportamiento DELETE |
|---|---|---|---|---|---|
| 1 | spotify_tokens | usuarios | N:1 | usuario_id | CASCADE |
| 2 | audios | usuarios | N:1 | usuario_id | CASCADE |
| 3 | playlists_favoritas | usuarios | N:1 | usuario_id | CASCADE |
| 4 | configuraciones | usuarios | N:1 | usuario_id | CASCADE |
| 5 | configuraciones | playlists_favoritas | N:1 | playlist_id | CASCADE |
| 6 | configuraciones | audios | N:1 | audio_id | CASCADE |
| 7 | historial_reproduccion | usuarios | N:1 | usuario_id | CASCADE |
| 8 | historial_reproduccion | playlists_favoritas | N:1 | playlist_id | SET NULL |
| 9 | historial_reproduccion | audios | N:1 | audio_id | SET NULL |
| 10 | logs_reproduccion | usuarios | N:1 | usuario_id | CASCADE |
| 11 | sesiones | usuarios | N:1 | usuario_id | CASCADE |
| 12 | audio_estadisticas | audios | 1:1 | audio_id | CASCADE |

---

## Llaves foráneas

| FK | Delete | Update | Tabla hija | Tabla padre |
|---|---|---|---|---|
| fk_spotify_tokens_usuario | CASCADE | CASCADE | spotify_tokens | usuarios |
| fk_audios_usuario | CASCADE | CASCADE | audios | usuarios |
| fk_playlists_fav_usuario | CASCADE | CASCADE | playlists_favoritas | usuarios |
| fk_config_usuario | CASCADE | CASCADE | configuraciones | usuarios |
| fk_config_playlist | CASCADE | CASCADE | configuraciones | playlists_favoritas |
| fk_config_audio | CASCADE | CASCADE | configuraciones | audios |
| fk_historial_usuario | CASCADE | CASCADE | historial_reproduccion | usuarios |
| fk_historial_playlist | SET NULL | CASCADE | historial_reproduccion | playlists_favoritas |
| fk_historial_audio | SET NULL | CASCADE | historial_reproduccion | audios |
| fk_logs_usuario | CASCADE | CASCADE | logs_reproduccion | usuarios |
| fk_sesiones_usuario | CASCADE | CASCADE | sesiones | usuarios |
| fk_audio_estadisticas_audio | CASCADE | CASCADE | audio_estadisticas | audios |

---

## Índices

| Tabla | Índice | Tipo | Columnas | Propósito |
|---|---|---|---|---|
| usuarios | PRIMARY | BTREE | id | PK |
| usuarios | uq_usuarios_spotify_id | UNIQUE | spotify_id | Evitar duplicados por ID de Spotify |
| usuarios | idx_usuarios_email | BTREE | email | Búsqueda por email |
| spotify_tokens | PRIMARY | BTREE | id | PK |
| spotify_tokens | idx_spotify_tokens_usuario | BTREE | usuario_id | JOIN con usuarios |
| spotify_tokens | idx_spotify_tokens_expiracion | BTREE | expires_at | Limpieza de tokens vencidos |
| audios | PRIMARY | BTREE | id | PK |
| audios | uq_audios_usuario_nombre | UNIQUE | usuario_id, nombre | Nombre único por usuario |
| audios | idx_audios_usuario | BTREE | usuario_id | JOIN con usuarios |
| playlists_favoritas | PRIMARY | BTREE | id | PK |
| playlists_favoritas | uq_playlist_usuario_spotify | UNIQUE | usuario_id, spotify_playlist_id | Evitar duplicados |
| playlists_favoritas | idx_playlists_fav_usuario | BTREE | usuario_id | JOIN con usuarios |
| configuraciones | PRIMARY | BTREE | id | PK |
| configuraciones | idx_config_usuario | BTREE | usuario_id | JOIN con usuarios |
| configuraciones | idx_config_playlist | BTREE | playlist_id | JOIN con playlists_favoritas |
| configuraciones | idx_config_audio | BTREE | audio_id | JOIN con audios |
| configuraciones | idx_config_activo | BTREE | activo | Filtro de configuraciones activas |
| historial_reproduccion | PRIMARY | BTREE | id | PK |
| historial_reproduccion | idx_historial_usuario | BTREE | usuario_id | JOIN con usuarios |
| historial_reproduccion | idx_historial_fecha | BTREE | fecha_reproduccion | Orden cronológico |
| historial_reproduccion | idx_historial_playlist | BTREE | playlist_id | JOIN con playlists |
| historial_reproduccion | idx_historial_audio | BTREE | audio_id | JOIN con audios |
| logs_reproduccion | PRIMARY | BTREE | id | PK |
| logs_reproduccion | idx_logs_usuario | BTREE | usuario_id | JOIN con usuarios |
| logs_reproduccion | idx_logs_tipo | BTREE | tipo_evento | Filtro por tipo |
| logs_reproduccion | idx_logs_fecha | BTREE | created_at | Orden cronológico |
| sesiones | PRIMARY | BTREE | id | PK |
| sesiones | uq_sesiones_token | UNIQUE | token | Token único por sesión |
| sesiones | idx_sesiones_usuario | BTREE | usuario_id | JOIN con usuarios |
| sesiones | idx_sesiones_ultimo_acceso | BTREE | ultimo_acceso | Limpieza de sesiones |
| audio_estadisticas | PRIMARY | BTREE | id | PK |
| audio_estadisticas | uq_audio_estadisticas | UNIQUE | audio_id | Relación 1:1 con audios |

---

## Trigger existente

**Nombre:** `trg_after_insert_audio`  
**Evento:** AFTER INSERT ON audios  
**Lógica:**
```sql
INSERT INTO audio_estadisticas (audio_id, reproducciones, tiempo_total_reproducido)
VALUES (NEW.id, 0, 0);
```

---

## Optimizaciones posibles

| # | Sugerencia | Justificación |
|---|---|---|
| 1 | Agregar `idx_usuarios_spotify_id` como índice separado además del UNIQUE | Ya existe como UNIQUE, suficiente |
| 2 | Verificar collation global utf8mb3_spanish_ci vs utf8mb4_unicode_ci de tablas | La collation global es diferente a la de las tablas. No genera error porque las tablas definen su propia collation, pero puede causar confusión en migraciones |
| 3 | Agregar `ON DELETE SET NULL` para `configuraciones.playlist_id` | Actualmente es CASCADE; si se elimina una playlist favorita se pierden las configuraciones. Depende del negocio |
| 4 | Crear vista `v_historial_detallado` para evitar JOINs repetitivos | Simplificaría consultas frecuentes de reportes |
| 5 | Evaluar partición de `historial_reproduccion` por mes si el volumen crece | Tabla propensa a alto volumen de inserciones |
| 6 | Agregar CHECK `canciones_intervalo IN (1,2,3,5,10)` en configuraciones | MySQL 8+ soporta CHECK constraints; actualmente la validación es solo en la aplicación |

---

## Diagrama de relaciones (textual)

```
                    ┌──────────────────────────────────────────────────────────────┐
                    │                          usuarios                              │
                    └──┬──┬──┬──┬──┬──┬──┬──┘                                    │
         ┌─────────────┘  │  │  │  │  │  │  └──────────────────────┐            │
         ▼                ▼  ▼  ▼  ▼  ▼  ▼                         ▼            │
  ┌──────────┐  ┌────────┐  ┌──────────┐  ┌───────────────────┐  ┌───────────┐  │
  │spotify_  │  │ audios │  │playlists_│  │logs_reproduccion  │  │ sesiones  │  │
  │tokens    │  └───┬────┘  │favoritas │  └───────────────────┘  └───────────┘  │
  └──────────┘      │       └───┬──┬───┘                                       │
                    │           │  │                                            │
                    ▼           ▼  ▼                                            │
          ┌──────────────────────────────┐                                      │
          │      configuraciones         │                                      │
          └──────────────────────────────┘                                      │
                    │           │                                              │
                    ▼           ▼                                              │
          ┌──────────────────────────────┐                                      │
          │  historial_reproduccion      │                                      │
          └──────────────────────────────┘                                      │
                    │                                                          │
                    ▼                                                          │
          ┌──────────────────────────────┐                                      │
          │  audio_estadisticas (1:1)   │                                      │
          └──────────────────────────────┘                                      │
                                                                               │
  Líneas continuas = CASCADE                                                    │
  Líneas punteadas = SET NULL                                                   │
  ──────────────────────────────────────────────────────────────────────────────┘
```
