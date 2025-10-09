CREATE DATABASE LookEmploy;
USE LookEmploy;

	CREATE TABLE Cliente (
		
		ID INT AUTO_INCREMENT PRIMARY KEY,
		
		nome VARCHAR(400) NOT NULL,
		sobrenome VARCHAR(400) NOT NULL,
		email VARCHAR(400) NOT NULL UNIQUE,
		senha VARCHAR(400) NOT NULL,
		telefone VARCHAR(11),
		bairro VARCHAR(200) NOT NULL,
		logradouro VARCHAR(200) NOT NULL,
		numero VARCHAR(10) NOT NULL,
		complemento VARCHAR(150),
		sexo VARCHAR(9) NOT NULL,
		genero VARCHAR(40) NOT NULL,
		dataNascimento DATE,

		descricao TEXT,
		caminhoImagemPerfil VARCHAR(255),
		caminhoImagemFundo VARCHAR(255)		
		)ENGINE = InnoDB;

	CREATE TABLE Prestador (

		ID INT AUTO_INCREMENT PRIMARY KEY,

		nome VARCHAR(400) NOT NULL,
		sobrenome VARCHAR(400) NOT NULL,
		email VARCHAR(400) NOT NULL UNIQUE,
		senha VARCHAR(400) NOT NULL,
		telefone VARCHAR(11),
		bairro VARCHAR(200) NOT NULL,
		logradouro VARCHAR(200) NOT NULL,
		numero VARCHAR(10) NOT NULL,
		complemento VARCHAR(150),
		sexo VARCHAR(9) NOT NULL,
		genero VARCHAR(40) NOT NULL,
		dataNascimento DATE,

		descricao TEXT,
		caminhoImagemPerfil VARCHAR(255),
		caminhoImagemFundo VARCHAR(255),

		tipoServico VARCHAR(11),
		estrelaAvaliativa VARCHAR(5)		
		)ENGINE = InnoDB;

	CREATE TABLE Servico (

		codigoServico INT PRIMARY KEY AUTO_INCREMENT,

		bairro VARCHAR(200) NOT NULL,
		logradouro VARCHAR(200) NOT NULL,
		numero VARCHAR(10) NOT NULL,
		complemento VARCHAR(150),
		dataServico DATETIME NOT NULL,
		tipoPagamento VARCHAR(150) NOT NULL,
		descricao TEXT,
		contrato TEXT,

		prestador INT NOT NULL,
		cliente INT NOT NULL,

		FOREIGN KEY (prestador) REFERENCES Prestador(ID),
		FOREIGN KEY (cliente) REFERENCES Cliente(ID) 
		)ENGINE = InnoDB;

	 CREATE TABLE AnuncioServico (

		codigoAnuncio INT PRIMARY KEY AUTO_INCREMENT,

		bairro VARCHAR(200) NOT NULL,
		logradouro VARCHAR(200) NOT NULL,
		data DATETIME NOT NULL,
		descricao TEXT,
		tipoServico VARCHAR(11),
		cliente INT NOT NULL,

		FOREIGN KEY (cliente) REFERENCES Cliente(ID) 
		)ENGINE = InnoDB;
