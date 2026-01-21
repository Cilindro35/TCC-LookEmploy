# 🏗️ LookEmploy - Plataforma de Conexão entre Clientes e Prestadores

[Banner do Projeto](https://ibb.co/XxtTSdYx)

> **Sistema web completo para conectar quem precisa de serviços com quem oferece, focando em construção civil e reformas.**

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com/)
[![WebSocket](https://img.shields.io/badge/WebSocket-Ratchet-010101?style=for-the-badge&logo=websocket&logoColor=white)](https://socketo.me/)
[![License](https://img.shields.io/badge/License-Academic-blue?style=for-the-badge)](LICENSE)

---

## 📋 Índice
- [✨ Visão Geral](#-visão-geral)
- [🚀 Funcionalidades](#-funcionalidades)
- [🛠️ Tecnologias](#️-tecnologias)
- [📁 Estrutura do Projeto](#-estrutura-do-projeto)
- [⚡ Instalação Rápida](#-instalação-rápida)
- [🔧 Configuração Detalhada](#-configuração-detalhada)
- [📖 Como Usar](#-como-usar)
- [🧪 Testando o Sistema](#-testando-o-sistema)
- [🤝 Contribuindo](#-contribuindo)
- [📄 Licença](#-licença)
- [👥 Autores](#-autores)
- [🙏 Agradecimentos](#-agradecimentos)

---

## ✨ Visão Geral

**LookEmploy** é uma plataforma web desenvolvida como Trabalho de Conclusão de Curso (TCC) que visa modernizar a contratação de serviços na área de construção civil. O sistema conecta **clientes** que necessitam de serviços especializados com **prestadores** qualificados, oferecendo um ambiente seguro, intuitivo e completo para gerenciamento de toda a jornada do serviço.

### 🎯 Objetivos
- Facilitar a conexão entre demanda e oferta de serviços
- Proporcionar segurança e transparência nas transações
- Digitalizar processos tradicionais do setor
- Oferecer ferramentas modernas de comunicação e gestão

---

## 🚀 Funcionalidades

### 👤 **Gestão de Usuários**
- Cadastro com validação em tempo real
- Perfis completos (Cliente/Prestador)
- Sistema de avaliação por estrelas
- Bloqueio de usuários indesejados

### 💬 **Comunicação**
- Chat em tempo real com criptografia AES-256
- Histórico de conversas
- Indicador de "digitando..."
- Upload de anexos (imagens, PDFs)

### 📋 **Gestão de Serviços**
- Solicitação de serviços com data/horário
- Acompanhamento do status (Pendente → Andamento → Concluído)
- Métodos de pagamento (PIX, Cartão, Dinheiro)
- Geração automática de contrato em PDF

### 🔐 **Segurança**
- Autenticação via JWT (JSON Web Tokens)
- Criptografia ponta-a-ponta
- Rate limiting contra ataques
- Logs de atividades suspeitas

---

## 🛠️ Tecnologias

### **Backend**
- **PHP 7.4+** - Lógica principal da aplicação
- **MySQL 5.7+** - Banco de dados relacional
- **Ratchet** - Servidor WebSocket para chat em tempo real
- **Composer** - Gerenciador de dependências
- **DomPDF** - Geração de PDFs

### **Frontend**
- **HTML5** - Estrutura semântica
- **CSS3** - Estilização moderna e responsiva
- **JavaScript (ES6+)** - Interatividade e validações
- **Font Awesome** - Biblioteca de ícones
- **Google Fonts** - Tipografia customizada

### **Segurança**
- **JWT** - Tokens de autenticação
- **AES-256-GCM** - Criptografia de mensagens
- **Prepared Statements** - Prevenção SQL Injection
- **Input Sanitization** - Prevenção XSS

---
### 📁 Estrutura Do Projeto
lookemploy/

├── 📂 api_chat/

│ ├── 📂 src/Security/ # Autenticação, criptografia, logs

│ ├── 📄 servidor_chat_seguro.php

│└── 📂 vendor/

│

├── 🎨 css/

│ ├── design_cadastro.css

│ ├── design_login.css

│ ├── design_perfil.css

│ ├── design_contatos.css

│ ├── design_pedidos.css

│ └── design_telaInicial.css

│

├── ⚡ js/

│ ├── validacaoCadastro.js

│ ├── validacaoLogin.js

│ ├── contatos_seguro.js # Chat em tempo real

│ └── menuLateral.js

│

├── 🖥️ php/

│ ├── realizarCadastro.php

│ ├── realizarLogin.php

│ ├── realizarLogout.php

│ └── excluirConta.php

│

├── 🖼️ img/

│ ├── logo.png

│ ├── logo_icon.png

│ ├── img_perfil/ # Fotos de usuários

│ └── telaInicial/ # Banner da home

│

├── 📄 index.html # Landing page

├── 📄 cadastro.html # Cadastro

├── 📄 login.html # Login

├── 📄 telaInicial.php # Dashboard

├── 📄 perfil.php # Perfil

├── 📄 contatos.php # Chat

└── 📄 pedidos.php # Serviços contratados


---

## ⚡ Instalação Rápida

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 5.7+
- Servidor web (Apache/Nginx)
- Composer

### Passos básicos
# 1. Clone o repositório
git clone https://github.com/seu-usuario/lookemploy.git
cd lookemploy

# 2. Configure o banco de dados
mysql -u root -p < database/lookemploy.sql

# 3. Instale dependências
cd api_chat
composer install

# 4. Inicie o servidor de chat
# Windows:
iniciar_chat_seguro.bat

# Linux/Mac:
php servidor_chat_seguro.php

# 5. Acesse no navegador
http://localhost/lookemploy

### 🔧 Configuração Detalhada

1. Configuração do Banco de Dados

  Edite o arquivo api_chat/conectar.php:

  $host = 'localhost';

  $dbname = 'lookemploy';

  $username = 'root';

  $password = 'sua_senha';

2. Configuração do Servidor Web
Apache: Configure o VirtualHost apontando para a pasta lookemploy

  Nginx: Configure o root para o diretório do projeto

3. Variáveis de Ambiente (Opcional)
Crie um arquivo .env na raiz do projeto:

  DB_HOST=localhost

  DB_NAME=lookemploy

  DB_USER=root

  DB_PASS=sua_senha

  JWT_SECRET=sua_chave_secreta_aqui

5. Configuração do WebSocket
Porta padrão: 8080

Certifique-se de que a porta está liberada no firewall

Para produção, considere usar um proxy reverso (Nginx)

### 📖 Como Usar
Para Clientes
Cadastre-se como "Cliente"

Explore prestadores pela página inicial

Contrate um serviço clicando em "Contratar"

Comunique-se via chat para combinar detalhes

Avalie o serviço após a conclusão

Para Prestadores
Cadastre-se como "Prestador" informando sua especialidade

1. Complete seu perfil com foto e descrição

2. Aguarde solicitações de serviços

3. Aceite/Recuse pedidos na página "Pedidos"

4. Comunique-se com clientes via chat

5. Fluxo de um Serviço
Cliente contrata → Prestador aceita → Chat de detalhes → 
Serviço realizado → Cliente avalia → Histórico salvo

### 🧪 Testando o Sistema
Teste de Segurança Integrado
Acesse: http://localhost/lookemploy/teste_seguranca.html

Este painel testa automaticamente:

✅ Conexão com banco de dados

✅ Geração de tokens JWT

✅ Servidor WebSocket

✅ Criptografia de mensagens

✅ Estrutura de arquivos

Testes Manuais Recomendados
Cadastro de ambos os tipos de conta

Login/Logout em diferentes navegadores

Upload de foto de perfil

Chat entre dois usuários diferentes

Contratação completa de um serviço

Geração de contrato PDF

Avaliação de prestador

Casos de Teste Críticos
Bloqueio e desbloqueio de usuários

Cancelamento de serviço com histórico

Validação de datas passadas

Upload de arquivos maliciosos (deve ser bloqueado)

### 🤝 Contribuindo
Este é um projeto acadêmico, mas contribuições são bem-vindas para:

Reportar bugs
Abra uma issue descrevendo o problema e os passos para reproduzir.

Sugerir melhorias
Proponha novas funcionalidades ou otimizações.

Corrigir problemas
Faça um fork e envie um pull request com:

Descrição clara da alteração

Testes realizados

Screenshots (se aplicável)

Guia de Estilo de Código
PHP: PSR-12

JavaScript: ESLint com regras padrão

HTML/CSS: Semântico e acessível

Commits: Conventional Commits

Copyright (c) 2025 [Seu Nome]

Este projeto foi desenvolvido como Trabalho de Conclusão de Curso (TCC)
na [Sua Faculdade], curso de [Seu Curso].

É permitido:
- Estudar, analisar e testar o código
- Utilizar como referência para outros projetos acadêmicos
- Sugerir melhorias e reportar problemas

É proibido:
- Utilizar para fins comerciais sem autorização
- Plagiar o trabalho como próprio
- Distribuir sem os devidos créditos

Para outros usos, entre em contato.
Certifique-se de que a pasta img/img_perfil tem permissão de escrita

👥 Autores
LookEmploy
Fellipe Alencar Calorio Silva
Bernado Vitorio Leme Nicolás
João Victor De Jesus Silva
Giuliano Toniolo
Diego Quirino Ferreira
Alexandre Crivelaro Fonseca Orientador(a) do TCC
Instituição de Ensino: ETEC DE ITAQUERA
Curso: Desenvolvimento de Sistemas
Período: 2023.1/2026
Disciplina: Trabalho de Conclusão de Curso

### 🙏 Agradecimentos
Aos meus amigos que deram sugestões incriveis

Aos professores que contribuíram para minha formação

Aos colegas de classe pelo apoio mútuo

À minha família pelo suporte incondicional

Aos alunos e professores que testaram e opinaram

"Mais do que código, construímos conexões. Mais do que software, construímos confiança."

Última atualização: Dezembro de 2025 | Versão: 1.0.0
