-- Agregar columna codigo_seguimiento a la tabla de órdenes de reparación
-- Esta migración es segura para ejecutar múltiples veces

-- Intentar agregar la columna a orden_reparacion
ALTER TABLE `orden_reparacion` 
ADD COLUMN IF NOT EXISTS `codigo_seguimiento` VARCHAR(20) UNIQUE AFTER `id_orden`;

-- Intentar agregar la columna a Orden_Reparacion (mayúscula)
ALTER TABLE `Orden_Reparacion` 
ADD COLUMN IF NOT EXISTS `codigo_seguimiento` VARCHAR(20) UNIQUE AFTER `id_orden`;

-- Intentar agregar la columna a reparacion
ALTER TABLE `reparacion` 
ADD COLUMN IF NOT EXISTS `codigo_seguimiento` VARCHAR(20) UNIQUE AFTER `id_orden`;

-- Intentar agregar la columna a Reparacion (mayúscula)
ALTER TABLE `Reparacion` 
ADD COLUMN IF NOT EXISTS `codigo_seguimiento` VARCHAR(20) UNIQUE AFTER `id_orden`;

-- Intentar agregar la columna a orden
ALTER TABLE `orden` 
ADD COLUMN IF NOT EXISTS `codigo_seguimiento` VARCHAR(20) UNIQUE AFTER `id_orden`;
