DROP DATABASE IF EXISTS LookEmploy;

-- 1) Criar DB com charset padronizado
CREATE DATABASE LookEmploy
    CHARACTER SET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
USE LookEmploy;

-- 2) TABELA usuarios (origem única de identidade/autenticação)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(400) NOT NULL,
    email VARCHAR(400) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL, -- armazenar hash (bcrypt/argon2) - 255 suficiente
    tipo ENUM('cliente','prestador','admin') NOT NULL DEFAULT 'cliente',
    online TINYINT(1) DEFAULT 0,
    last_login DATETIME NULL,
    failed_attempts INT DEFAULT 0,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires DATETIME NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_online (online),
    INDEX idx_tipo (tipo),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) TABELA Cliente 
CREATE TABLE Cliente (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE, -- vincula a tabela usuarios
    nome VARCHAR(400) NOT NULL,
    sobrenome VARCHAR(400) NOT NULL,
    email VARCHAR(400) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    bairro VARCHAR(200) NOT NULL,
    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(150),
    sexo VARCHAR(9) NOT NULL,
    dataNascimento DATE,
    descricao TEXT,
    caminhoImagemPerfil VARCHAR(255),
    caminhoImagemFundo VARCHAR(255),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cliente_email (email),
    INDEX idx_cliente_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) TABELA Prestador 
CREATE TABLE Prestador (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE,
    nome VARCHAR(400) NOT NULL,
    sobrenome VARCHAR(400) NOT NULL,
    email VARCHAR(400) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    bairro VARCHAR(200) NOT NULL,
    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(150),
    sexo VARCHAR(9) NOT NULL,
    dataNascimento DATE,
    descricao TEXT,
    caminhoImagemPerfil VARCHAR(255),
    caminhoImagemFundo VARCHAR(255),
    tipoServico VARCHAR(100),
    avaliacao DECIMAL(3,2) DEFAULT NULL, -- permite notas como 4.75
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prestador_email (email),
    INDEX idx_prestador_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) TABELA Servico
CREATE TABLE Servico (
    codigoServico INT PRIMARY KEY AUTO_INCREMENT,
    bairro VARCHAR(200) NOT NULL,
    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(150),
    dataServico DATETIME NOT NULL,
    tipoPagamento VARCHAR(150) NOT NULL,
    descricao TEXT,
    contrato TEXT,
    prestador INT NULL,
    cliente INT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_servico_prestador (prestador),
    INDEX idx_servico_cliente (cliente),
    CONSTRAINT fk_servico_prestador FOREIGN KEY (prestador) REFERENCES Prestador(ID) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_servico_cliente FOREIGN KEY (cliente) REFERENCES Cliente(ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) TABELA AnuncioServico
CREATE TABLE AnuncioServico (
    codigoAnuncio INT PRIMARY KEY AUTO_INCREMENT,
    bairro VARCHAR(200) NOT NULL,
    logradouro VARCHAR(200) NOT NULL,
    data DATETIME NOT NULL,
    descricao TEXT,
    tipoServico VARCHAR(100),
    cliente INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_anuncio_cliente FOREIGN KEY (cliente) REFERENCES Cliente(ID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) TABELA conversas (melhora organização de chats)
CREATE TABLE conversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_a INT NOT NULL,
    user_b INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_conversa_pair (user_a, user_b),
    CONSTRAINT fk_conversa_user_a FOREIGN KEY (user_a) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conversa_user_b FOREIGN KEY (user_b) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- 8) TABELA mensagens (ligada a usuarios e conversas)
CREATE TABLE mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    conversa_id INT NULL,
    mensagem TEXT NOT NULL COMMENT 'Mensagem criptografada (armazenar ciphertext pela app)',
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    entregue TINYINT(1) DEFAULT 0,
    lido TINYINT(1) DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remetente_destinatario (remetente_id, destinatario_id),
    INDEX idx_conversa (conversa_id),
    INDEX idx_data_envio (data_envio),
    INDEX idx_lido (lido),
    CONSTRAINT fk_msg_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_msg_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_msg_conversa FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) TABELA security_logs
CREATE TABLE security_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    event_type VARCHAR(100) NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON NULL,
    severity ENUM('INFO','WARNING','ERROR','CRITICAL') DEFAULT 'INFO',
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_severity (severity),
    INDEX idx_event_type (event_type),
    CONSTRAINT fk_security_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) TABELA usuarios_bloqueados
CREATE TABLE usuarios_bloqueados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    bloqueado_id INT NOT NULL,
    data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT,
    UNIQUE KEY unique_bloqueio (usuario_id, bloqueado_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_bloqueado (bloqueado_id),
    CONSTRAINT fk_ub_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ub_bloqueado FOREIGN KEY (bloqueado_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11) VIEW para estatísticas de segurança
CREATE OR REPLACE VIEW v_security_stats AS
SELECT 
    DATE(timestamp) as data,
    event_type,
    severity,
    COUNT(*) as total_eventos
FROM security_logs
GROUP BY DATE(timestamp), event_type, severity
ORDER BY data DESC, total_eventos DESC;

-- 12) PROCEDURES
DROP PROCEDURE IF EXISTS sp_log_security_event;
DELIMITER $$
CREATE PROCEDURE sp_log_security_event(
    IN p_event_type VARCHAR(100),
    IN p_user_id INT,
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT,
    IN p_details JSON,
    IN p_severity VARCHAR(10)
)
BEGIN
    INSERT INTO security_logs (timestamp, event_type, user_id, ip_address, user_agent, details, severity)
    VALUES (NOW(), p_event_type, p_user_id, p_ip_address, p_user_agent, p_details, p_severity);
END $$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_limpar_logs_antigos;
DELIMITER $$
CREATE PROCEDURE sp_limpar_logs_antigos(IN dias_manter INT)
BEGIN
    DELETE FROM security_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL dias_manter DAY);
    SELECT ROW_COUNT() as logs_removidos;
END $$
DELIMITER ;

-- 13) TRIGGERS (audit) 
DROP TRIGGER IF EXISTS tr_audit_mensagem_delete;
DELIMITER $$
CREATE TRIGGER tr_audit_mensagem_delete
BEFORE DELETE ON mensagens
FOR EACH ROW
BEGIN
    INSERT INTO security_logs (timestamp, event_type, user_id, details, severity)
    VALUES (
        NOW(),
        'MESSAGE_DELETED',
        OLD.remetente_id,
        JSON_OBJECT('mensagem_id', OLD.id, 'destinatario_id', OLD.destinatario_id, 'data_envio', OLD.data_envio),
        'INFO'
    );
END $$
DELIMITER ;

DROP TRIGGER IF EXISTS tr_audit_usuario_status;
DELIMITER $$
CREATE TRIGGER tr_audit_usuario_status
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF OLD.online != NEW.online THEN
        INSERT INTO security_logs (timestamp, event_type, user_id, details, severity)
        VALUES (
            NOW(),
            IF(NEW.online = 1, 'USER_ONLINE', 'USER_OFFLINE'),
            NEW.id,
            JSON_OBJECT('previous_status', OLD.online, 'new_status', NEW.online),
            'INFO'
        );
    END IF;
END $$
DELIMITER ;

-- 14) Trigger para normalizar ordem de pares em conversas (garante user_a < user_b)
DROP TRIGGER IF EXISTS tr_conversa_normaliza_ordem;
DELIMITER $$
CREATE TRIGGER tr_conversa_normaliza_ordem
BEFORE INSERT ON conversas
FOR EACH ROW
BEGIN
	DECLARE tmp INT;
    IF NEW.user_a > NEW.user_b THEN
        SET tmp = NEW.user_a;
        SET NEW.user_a = NEW.user_b;
        SET NEW.user_b = tmp;
    END IF;
END $$
DELIMITER ;

-- 15) Índices adicionais para performance
-- Não é 100% portátil, mas funciona na maioria dos motores.
CREATE INDEX idx_mensagens_nao_lidas ON mensagens(destinatario_id, lido, data_envio);
CREATE INDEX idx_conversa_pair ON conversas(user_a, user_b);

-- 16) Comentários/metadata
ALTER TABLE security_logs COMMENT = 'Registros de eventos de segurança do sistema de chat';
ALTER TABLE usuarios_bloqueados COMMENT = 'Relacionamento de usuários bloqueados';

-- 17) Exemplo de consulta útil 
-- SELECT DISTINCT u.id, u.nome, u.online
-- FROM usuarios u
-- JOIN mensagens m ON (u.id = m.remetente_id OR u.id = m.destinatario_id)
-- WHERE u.id != :meu_id
--   AND (:meu_id IN (m.remetente_id, m.destinatario_id));

-- 18) Observações finais:
--  - Senhas na coluna usuarios.senha devem conter hash seguro (bcrypt/argon2) gerado na aplicação.
--  - Mensagens devem ser cifradas na aplicação antes de inserir em 'mensagem' (armazenar ciphertext).
--  - Migrar dados das tabelas antigas (se houver) requer scripts ETL; posso gerar se quiser.
--  - Teste triggers/procedures em staging antes de mover para produção.

-- 19) Mensagem de conclusão
SELECT 'LookEmploy com segurança integrada (teste em staging recomendado)' AS status;
