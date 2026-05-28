-- Adiciona anotacoes internas para PDFs em Documentos.
-- Execute no phpMyAdmin ou no cliente MySQL do banco do site.
-- Nao apaga dados existentes.

CREATE TABLE IF NOT EXISTS team_document_annotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    page_number INT UNSIGNED NOT NULL DEFAULT 1,
    type VARCHAR(20) NOT NULL DEFAULT 'highlight',
    x DECIMAL(8,6) NOT NULL DEFAULT 0,
    y DECIMAL(8,6) NOT NULL DEFAULT 0,
    width DECIMAL(8,6) NOT NULL DEFAULT 0,
    height DECIMAL(8,6) NOT NULL DEFAULT 0,
    color VARCHAR(20) NOT NULL DEFAULT '#facc15',
    note TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_team_document_annotations_document (document_id, page_number, active),
    CONSTRAINT fk_team_document_annotations_document FOREIGN KEY (document_id) REFERENCES team_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_document_annotations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
