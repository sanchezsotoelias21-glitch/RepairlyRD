-- Si tu tabla Usuario tiene `rol` como ENUM y aparece "Data truncated for column 'rol'",
-- ejecuta este script en la misma base que usa la aplicación.
-- Ajusta el nombre de la tabla si difiere (Usuario vs usuario).

ALTER TABLE Usuario
    MODIFY COLUMN rol VARCHAR(40) NOT NULL DEFAULT 'cliente';
