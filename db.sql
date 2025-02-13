CREATE DATABASE IF NOT EXISTS medicina;

USE medicina;

-- Tabela de Usuários (pacientes, profissionais, familiares, etc.)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('Admin', 'Medico', 'Enfermeiro', 'ACS', 'Paciente') NOT NULL,
    numero_familia VARCHAR(10),
    telefone VARCHAR(20),
    cep VARCHAR(10) NOT NULL,
    rua VARCHAR(100) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    complemento VARCHAR(50),
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL,
    estado CHAR(2) NOT NULL,
    data_nascimento VARCHAR(12) NOT NULL,
    sexo VARCHAR(20) NOT NULL,
    micro_area VARCHAR(50),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
alter table usuarios add column micro_area VARCHAR(50) after sexo;
alter table usuarios modify sexo VARCHAR(20) NOT NULL;
alter table usuarios modify data_nascimento VARCHAR(12) NOT NULL;
ALTER TABLE usuarios ADD COLUMN micro_area_id INT;
ALTER TABLE usuarios ADD FOREIGN KEY (micro_area_id) REFERENCES micro_areas(id);

CREATE TABLE IF NOT EXISTS micro_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Tabela de Pacientes (dados específicos de pacientes com DCNTs)
CREATE TABLE IF NOT EXISTS pacientes (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 tipo_doenca VARCHAR(25) NOT NULL,
 historico_familiar TEXT,
 estado_civil VARCHAR(20),
 profissao VARCHAR(50),
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);


-- Tabela de Profissionais de Saúde (associados aos pacientes)
CREATE TABLE IF NOT EXISTS profissionais (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 especialidade VARCHAR(100),
 registro_profissional VARCHAR(50),
 unidade_saude VARCHAR(100),
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS paciente_profissional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    profissional_id INT NOT NULL,
    tipo_profissional ENUM('Medico', 'Enfermeiro', 'ACS') NOT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE CASCADE
);


-- Tabela de Exames (resultados de exames dos pacientes)
CREATE TABLE IF NOT EXISTS exames (
 id INT AUTO_INCREMENT PRIMARY KEY,
 paciente_id INT NOT NULL,
 tipo_exame VARCHAR(100),
 resultado TEXT,
 data_exame DATE NOT NULL,
 arquivo_exame VARCHAR(255), -- Link para arquivo na nuvem, caso necessário
 FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS acompanhamento_em_casa (
 id INT AUTO_INCREMENT PRIMARY KEY,
 paciente_id INT NOT NULL,
 data_acompanhamento DATE NOT NULL,
 glicemia VARCHAR(10),
 hipertensao VARCHAR(10),
 observacoes TEXT,
 FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

-- Tabela de Consultas e Acompanhamento
CREATE TABLE IF NOT EXISTS consultas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 paciente_id INT NOT NULL,
 profissional_id INT,
 data_consulta DATE NOT NULL,
 observacoes TEXT,
 pressao_arterial VARCHAR(10),
 glicemia VARCHAR(10),
 peso DECIMAL(5,2),
 altura VARCHAR(10),
 imc DECIMAL(4,1),
 classificacao_imc VARCHAR(20),
 estado_emocional VARCHAR(50),
 habitos_vida TEXT,
 FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
 FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE SET NULL
);

-- Tabela de Medicamentos e Controle de Uso
CREATE TABLE IF NOT EXISTS medicamentos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 paciente_id INT NOT NULL,
 nome_medicamento VARCHAR(100),
 dosagem VARCHAR(50),
 frequencia VARCHAR(50),
 observacoes TEXT,
 data_inicio DATE,
 data_fim DATE,
 FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);


-- Tabela para Análises e Estatísticas
CREATE TABLE IF NOT EXISTS analises_estatisticas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 paciente_id INT NOT NULL,
 data_analise DATE,
 comparativo_pa VARCHAR(20),
 comparativo_glicemia VARCHAR(20),
 comparativo_risco_cardio VARCHAR(20),
 FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);


-- Tabela de Logs de Acesso (para questões de segurança e rastreamento)
CREATE TABLE IF NOT EXISTS logs_acesso (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 acao VARCHAR(100),
 data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 endereco_ip VARCHAR(50),
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS riscos_saude (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    data_calculo DATE NOT NULL,
    sexo ENUM('Homem', 'Mulher') NOT NULL,
    idade VARCHAR(10) NOT NULL,
    colesterol_total INT NOT NULL,
    colesterol_hdl INT NOT NULL,
    pressao_sistolica INT NOT NULL,
    fumante ENUM('Sim', 'Não') NOT NULL,
    remedios_hipertensao ENUM('Sim', 'Não') NOT NULL,
    probabilidade DECIMAL(5,2) NOT NULL,
    pontuacao INT NOT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);