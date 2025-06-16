-- Ajouter la colonne voice_path à la table messages
ALTER TABLE messages ADD COLUMN voice_path VARCHAR(255) NULL AFTER image_path;

-- Ajouter une colonne read_at à la table message_reads si elle n'existe pas
ALTER TABLE message_reads ADD COLUMN read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Créer un index pour optimiser les requêtes de lecture
CREATE INDEX idx_message_reads_message_user ON message_reads(message_id, user_id);
CREATE INDEX idx_messages_voice ON messages(voice_path);

-- Ajouter une table pour les appels
CREATE TABLE IF NOT EXISTS calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    caller_id INT NOT NULL,
    call_type ENUM('voice', 'video') NOT NULL,
    status ENUM('calling', 'answered', 'ended', 'missed') DEFAULT 'calling',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    duration INT DEFAULT 0,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_calls_conversation ON calls(conversation_id);
CREATE INDEX idx_calls_caller ON calls(caller_id);
