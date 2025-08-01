CREATE DATABASE LookEmploy;
USE LookEmploy;

	CREATE TABLE Cliente (
		
		ID INT AUTO_INCREMENT PRIMARY KEY,
		RG VARCHAR(9) UNIQUE NOT NULL,

		nome VARCHAR(255) NOT NULL,
		email VARCHAR(255) NOT NULL UNIQUE,
		senha VARCHAR(255) NOT NULL,
		telefone VARCHAR(11),
		bairro VARCHAR(60) NOT NULL,
		logradouro VARCHAR(60) NOT NULL,
		numero VARCHAR(10) NOT NULL,
		complemento VARCHAR(80),
		sexo VARCHAR(9) NOT NULL,
		genero VARCHAR(40) NOT NULL,

		descricao TEXT,
		caminhoImagemPerfil VARCHAR(255),
		caminhoImagemFundo VARCHAR(255)		
		)ENGINE = InnoDB;

	CREATE TABLE Prestador (
		ID INT AUTO_INCREMENT PRIMARY KEY,
		CPF VARCHAR(11) UNIQUE NOT NULL,

		nome VARCHAR(100) NOT NULL,
		email VARCHAR(100) NOT NULL UNIQUE,
		senha VARCHAR(40) NOT NULL,
		telefone VARCHAR(11),
		bairro VARCHAR(60) NOT NULL,
		logradouro VARCHAR(60) NOT NULL,
		numero VARCHAR(10) NOT NULL,
		complemento VARCHAR(80),
		sexo VARCHAR(9) NOT NULL,
		genero VARCHAR(40) NOT NULL,

		descricao TEXT,
		caminhoImagemPerfil VARCHAR(255),
		caminhoImagemFundo VARCHAR(255),

		tipoServico VARCHAR(45),
		estrelaAvaliativa VARCHAR(5)		
		)ENGINE = InnoDB;

	CREATE TABLE Servico (
		codigoServico INT PRIMARY KEY AUTO_INCREMENT,

		bairro VARCHAR(60) NOT NULL,
		logradouro VARCHAR(60) NOT NULL,
		numero VARCHAR(10) NOT NULL,
		complemento VARCHAR(80),
		dataServico DATETIME NOT NULL,
		tipoPagamento VARCHAR(150) NOT NULL,
		contrato TEXT,

		prestador INT NOT NULL,
		cliente INT NOT NULL,

		FOREIGN KEY (prestador) REFERENCES Prestador(ID),
		FOREIGN KEY (cliente) REFERENCES Cliente(ID) 
		)ENGINE = InnoDB; 	