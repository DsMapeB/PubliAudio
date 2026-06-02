-- ============================================================================
-- Migración: Agregar token_expires_at a la tabla usuarios
-- Versión: 1.0.0
-- Fecha: 01/06/2026
-- 
-- Motivo: El código PHP (Usuario.php, SpotifyService.php) espera la columna
-- token_expires_at en la tabla usuarios para almacenar la fecha de expiración
-- del access token de Spotify. Esta columna faltaba en el schema original.
-- 
-- Diseño: Se almacena en usuarios (denormalizado) porque:
--   1. El código actual ya usa este diseño
--   2. La tabla spotify_tokens existe para histórico, no para el flujo activo
--   3. Cambiar a spotify_tokens requeriría refactor mayor
--   4. La columna permite consultas rápidas de expiración sin JOINs
-- ============================================================================

ALTER TABLE `usuarios`
    ADD COLUMN `token_expires_at` DATETIME DEFAULT NULL
    COMMENT 'Fecha de expiración del access token de Spotify'
    AFTER `refresh_token`;
