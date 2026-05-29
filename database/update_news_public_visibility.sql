-- Adiciona modo de visualizacao para noticias publicadas.
-- Execute no phpMyAdmin ou no cliente MySQL do banco do site.
-- Nao apaga dados existentes.

ALTER TABLE news
    ADD COLUMN public_visibility VARCHAR(20) NOT NULL DEFAULT 'listed' AFTER status;

UPDATE news
SET public_visibility = 'listed'
WHERE public_visibility IS NULL OR public_visibility = '';
