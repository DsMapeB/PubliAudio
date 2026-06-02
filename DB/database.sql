-- ============================================================================
-- Spotify Audio Interleave - Base de Datos
-- Versión: 1.0.0
-- Motor: MySQL 8+ / MariaDB (Laragon)
-- Collation: utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `spotify_audio_interleave`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `spotify_audio_interleave`;

-- ============================================================================
-- 1. usuarios
-- Tabla principal de usuarios autenticados vía Spotify OAuth.
-- Almacena datos del perfil público y credenciales de la API de Spotify.
-- ============================================================================
CREATE TABLE `usuarios` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único del usuario',
    `nombre`        VARCHAR(255)    NOT NULL                 COMMENT 'Nombre público del usuario en Spotify',
    `email`         VARCHAR(255)    DEFAULT NULL             COMMENT 'Correo electrónico (opcional, depende de permisos Spotify)',
    `password_hash` VARCHAR(255)    DEFAULT NULL             COMMENT 'Hash bcrypt de contraseña local (reserva para auth local futura)',
    `foto`          VARCHAR(500)    DEFAULT NULL             COMMENT 'URL de la foto de perfil de Spotify',
    `spotify_id`    VARCHAR(255)    NOT NULL                 COMMENT 'ID único del usuario en Spotify',
    `access_token`  TEXT            DEFAULT NULL             COMMENT 'Access token vigente de Spotify',
    `refresh_token` TEXT            DEFAULT NULL             COMMENT 'Refresh token para renovar access token',
    `token_expires_at` DATETIME     DEFAULT NULL             COMMENT 'Fecha de expiración del access token',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Fecha de registro',
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP  COMMENT 'Última actualización',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uq_usuarios_spotify_id` (`spotify_id`)  COMMENT 'Garantiza un solo registro por usuario de Spotify',
    INDEX `idx_usuarios_email` (`email`)  COMMENT 'Índice para búsquedas por correo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios autenticados con Spotify';


-- ============================================================================
-- 2. spotify_tokens
-- Historial de tokens OAuth de Spotify.
-- Se mantiene un registro por cada renovación para trazabilidad.
-- ============================================================================
CREATE TABLE `spotify_tokens` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único del registro de token',
    `usuario_id`    INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario propietario del token',
    `access_token`  TEXT            NOT NULL                 COMMENT 'Token de acceso vigente',
    `refresh_token` TEXT            DEFAULT NULL             COMMENT 'Token de renovación (puede ser nulo en la primera emisión)',
    `expires_at`    DATETIME        NOT NULL                 COMMENT 'Fecha y hora de expiración del access_token',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Momento en que se emitió/renovó el token',
    PRIMARY KEY (`id`),
    INDEX `idx_spotify_tokens_usuario` (`usuario_id`)  COMMENT 'Búsqueda rápida por usuario',
    INDEX `idx_spotify_tokens_expiracion` (`expires_at`)  COMMENT 'Índice para limpieza de tokens expirados',
    CONSTRAINT `fk_spotify_tokens_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de tokens OAuth de Spotify';


-- ============================================================================
-- 3. audios
-- Archivos MP3 subidos por el usuario para intercalar en reproducción.
-- ============================================================================
CREATE TABLE `audios` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único del audio',
    `usuario_id`    INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario propietario',
    `nombre`        VARCHAR(255)    NOT NULL                 COMMENT 'Nombre descriptivo asignado por el usuario',
    `archivo`       VARCHAR(500)    NOT NULL                 COMMENT 'Ruta relativa al archivo MP3 en el servidor',
    `duracion`      INT UNSIGNED    NOT NULL DEFAULT 0       COMMENT 'Duración en segundos (obtenida con getID3 o similar)',
    `tamano`        INT UNSIGNED    NOT NULL DEFAULT 0       COMMENT 'Tamaño del archivo en bytes',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Fecha de subida',
    PRIMARY KEY (`id`),
    INDEX `idx_audios_usuario` (`usuario_id`)  COMMENT 'Búsqueda por usuario propietario',
    UNIQUE INDEX `uq_audios_usuario_nombre` (`usuario_id`, `nombre`)  COMMENT 'Nombre único por usuario',
    CONSTRAINT `fk_audios_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archivos MP3 subidos por el usuario';


-- ============================================================================
-- 4. playlists_favoritas
-- Playlists de Spotify que el usuario ha guardado para configurar.
-- ============================================================================
CREATE TABLE `playlists_favoritas` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único del registro',
    `usuario_id`           INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario que guardó la playlist',
    `spotify_playlist_id`  VARCHAR(255)    NOT NULL                 COMMENT 'ID de la playlist en Spotify',
    `nombre_playlist`      VARCHAR(255)    NOT NULL                 COMMENT 'Nombre de la playlist según Spotify',
    `imagen`               VARCHAR(500)    DEFAULT NULL             COMMENT 'URL de la imagen de portada (extraída de Spotify)',
    `created_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Fecha en que se guardó',
    PRIMARY KEY (`id`),
    INDEX `idx_playlists_fav_usuario` (`usuario_id`)  COMMENT 'Búsqueda por usuario',
    UNIQUE INDEX `uq_playlist_usuario_spotify` (`usuario_id`, `spotify_playlist_id`)  COMMENT 'Evita duplicados de la misma playlist por usuario',
    CONSTRAINT `fk_playlists_fav_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Playlists de Spotify guardadas por el usuario';


-- ============================================================================
-- 5. configuraciones
-- Configuración de reproducción: vincula playlist, audio e intervalo.
-- ============================================================================
CREATE TABLE `configuraciones` (
    `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único de la configuración',
    `usuario_id`         INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario que creó la configuración',
    `playlist_id`        INT UNSIGNED    NOT NULL                 COMMENT 'FK a playlists_favoritas',
    `audio_id`           INT UNSIGNED    NOT NULL                 COMMENT 'FK al audio personalizado a intercalar',
    `canciones_intervalo` TINYINT UNSIGNED NOT NULL DEFAULT 3     COMMENT 'Cada cuántas canciones se intercala el audio (1,2,3,5,10)',
    `activo`             TINYINT(1)      NOT NULL DEFAULT 1       COMMENT '1=activa, 0=desactivada por el usuario',
    `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Fecha de creación',
    `updated_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP  COMMENT 'Última modificación',
    PRIMARY KEY (`id`),
    INDEX `idx_config_usuario` (`usuario_id`)  COMMENT 'Búsqueda por usuario',
    INDEX `idx_config_playlist` (`playlist_id`)  COMMENT 'Búsqueda por playlist',
    INDEX `idx_config_audio` (`audio_id`)  COMMENT 'Búsqueda por audio',
    INDEX `idx_config_activo` (`activo`)  COMMENT 'Filtro de configuraciones activas',
    CONSTRAINT `fk_config_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_config_playlist`
        FOREIGN KEY (`playlist_id`) REFERENCES `playlists_favoritas` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_config_audio`
        FOREIGN KEY (`audio_id`) REFERENCES `audios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuraciones de reproducción con intercalado de audios';


-- ============================================================================
-- 6. historial_reproduccion
-- Registro histórico de cada sesión de reproducción ejecutada.
-- ============================================================================
CREATE TABLE `historial_reproduccion` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único del registro histórico',
    `usuario_id`          INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario que reprodujo',
    `playlist_id`         INT UNSIGNED    DEFAULT NULL             COMMENT 'FK a playlists_favoritas (puede quedar nulo si se elimina)',
    `audio_id`            INT UNSIGNED    DEFAULT NULL             COMMENT 'FK al audio intercalado (puede quedar nulo si se elimina)',
    `fecha_reproduccion`  DATETIME        NOT NULL                 COMMENT 'Fecha y hora de inicio de la reproducción',
    `canciones_escuchadas` INT UNSIGNED   NOT NULL DEFAULT 0       COMMENT 'Cantidad de canciones reproducidas en la sesión',
    `duracion_total`      INT UNSIGNED    NOT NULL DEFAULT 0       COMMENT 'Duración total de la sesión en segundos',
    PRIMARY KEY (`id`),
    INDEX `idx_historial_usuario` (`usuario_id`)  COMMENT 'Búsqueda por usuario',
    INDEX `idx_historial_fecha` (`fecha_reproduccion`)  COMMENT 'Ordenamiento cronológico',
    INDEX `idx_historial_playlist` (`playlist_id`)  COMMENT 'Búsqueda por playlist',
    INDEX `idx_historial_audio` (`audio_id`)  COMMENT 'Búsqueda por audio intercalado',
    CONSTRAINT `fk_historial_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_historial_playlist`
        FOREIGN KEY (`playlist_id`) REFERENCES `playlists_favoritas` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_historial_audio`
        FOREIGN KEY (`audio_id`) REFERENCES `audios` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de sesiones de reproducción';


-- ============================================================================
-- 7. logs_reproduccion
-- Registro de eventos detallados durante la reproducción.
-- Útil para depuración, auditoría y estadísticas.
-- ============================================================================
CREATE TABLE `logs_reproduccion` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único del log',
    `usuario_id`    INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario asociado al evento',
    `tipo_evento`   VARCHAR(50)     NOT NULL                 COMMENT 'Tipo de evento (inicio, pausa, reanudacion, fin, error, audio_intercalado)',
    `descripcion`   TEXT            DEFAULT NULL             COMMENT 'Descripción detallada del evento',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Momento del evento',
    PRIMARY KEY (`id`),
    INDEX `idx_logs_usuario` (`usuario_id`)  COMMENT 'Búsqueda por usuario',
    INDEX `idx_logs_tipo` (`tipo_evento`)  COMMENT 'Filtro por tipo de evento',
    INDEX `idx_logs_fecha` (`created_at`)  COMMENT 'Ordenamiento cronológico',
    CONSTRAINT `fk_logs_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro detallado de eventos de reproducción';


-- ============================================================================
-- 8. sesiones
-- Control de sesiones activas de los usuarios en la aplicación web.
-- ============================================================================
CREATE TABLE `sesiones` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único de la sesión',
    `usuario_id`    INT UNSIGNED    NOT NULL                 COMMENT 'FK al usuario dueño de la sesión',
    `token`         VARCHAR(255)    NOT NULL                 COMMENT 'Token de sesión único (generado con random_bytes)',
    `ip`            VARCHAR(45)     DEFAULT NULL             COMMENT 'Dirección IP desde donde se conectó (soporta IPv6)',
    `user_agent`    VARCHAR(500)    DEFAULT NULL             COMMENT 'User-Agent del navegador/dispositivo',
    `ultimo_acceso` DATETIME        DEFAULT NULL             COMMENT 'Fecha y hora del último request con esta sesión',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'Fecha de creación de la sesión',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uq_sesiones_token` (`token`)  COMMENT 'Token único para identificar la sesión',
    INDEX `idx_sesiones_usuario` (`usuario_id`)  COMMENT 'Búsqueda de sesiones por usuario',
    INDEX `idx_sesiones_ultimo_acceso` (`ultimo_acceso`)  COMMENT 'Índice para limpieza de sesiones expiradas',
    CONSTRAINT `fk_sesiones_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones activas de usuarios en la aplicación';


-- ============================================================================
-- 9. audio_estadisticas
-- Estadísticas de uso de cada audio: reproducciones, tiempo acumulado.
-- Relación 1:1 con la tabla audios.
-- ============================================================================
CREATE TABLE `audio_estadisticas` (
    `id`                       INT UNSIGNED    NOT NULL AUTO_INCREMENT  COMMENT 'Identificador único de la estadística',
    `audio_id`                 INT UNSIGNED    NOT NULL                 COMMENT 'FK al audio (relación 1:1)',
    `reproducciones`           INT UNSIGNED    NOT NULL DEFAULT 0       COMMENT 'Contador de veces que se ha reproducido este audio',
    `tiempo_total_reproducido` INT UNSIGNED    NOT NULL DEFAULT 0       COMMENT 'Tiempo total acumulado en segundos',
    `ultima_reproduccion`      DATETIME        DEFAULT NULL             COMMENT 'Fecha y hora de la última reproducción',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uq_audio_estadisticas` (`audio_id`)  COMMENT 'Garantiza relación 1:1 con audios',
    CONSTRAINT `fk_audio_estadisticas_audio`
        FOREIGN KEY (`audio_id`) REFERENCES `audios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estadísticas de uso de cada audio personalizado';


-- ============================================================================
-- DATOS INICIALES
-- ============================================================================

-- No se insertan usuarios iniciales porque el registro es exclusivamente
-- mediante autenticación OAuth con Spotify.
-- La tabla audio_estadisticas se poblará automáticamente al crear un audio
-- mediante un TRIGGER o desde la lógica de la aplicación.

-- ============================================================================
-- TRIGGERS
-- ============================================================================

-- Al crear un nuevo audio, se inserta automáticamente su registro
-- de estadísticas con valores en cero.
DELIMITER //
CREATE TRIGGER `trg_after_insert_audio`
    AFTER INSERT ON `audios`
    FOR EACH ROW
BEGIN
    INSERT INTO `audio_estadisticas` (`audio_id`, `reproducciones`, `tiempo_total_reproducido`)
    VALUES (NEW.`id`, 0, 0);
END//
DELIMITER ;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================
-- Para importar desde phpMyAdmin:
-- 1. Crear una base de datos vacía llamada 'spotify_audio_interleave'
-- 2. Seleccionar la pestaña SQL
-- 3. Pegar o seleccionar este archivo
-- 4. Ejecutar
-- O directamente: SOURCE /ruta/completa/database.sql;
-- ============================================================================
