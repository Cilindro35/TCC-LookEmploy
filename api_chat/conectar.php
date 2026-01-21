<?php
/**
 * Conexão segura com o banco de dados LookEmploy
 * Suporta variáveis de ambiente (.env) com fallbacks
 */

// ============================================
// 1. AUTOLOAD E CARREGAMENTO DO .env
// ============================================

// Carrega o autoload do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Tenta carregar variáveis do .env se a biblioteca existir
if (class_exists('Dotenv\\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Raiz do projeto
        $dotenv->load();
        
        // Valida variáveis obrigatórias
        $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER']);
    } catch (Exception $e) {
        // Log do erro mas continua com valores padrão
        error_log("LookEmploy: Erro ao carregar .env - " . $e->getMessage());
    }
}

// ============================================
// 2. CONFIGURAÇÃO DA CHAVE AES (PARA CRIPTOGRAFIA)
// ============================================

$aes = $_ENV['AES_KEY'] ?? $_SERVER['AES_KEY'] ?? getenv('AES_KEY');
if (!$aes) {
    // Chave padrão para desenvolvimento (DEVE SER ALTERADA EM PRODUÇÃO!)
    $def = $_ENV['ENCRYPTION_KEY'] 
        ?? $_SERVER['ENCRYPTION_KEY'] 
        ?? getenv('ENCRYPTION_KEY') 
        ?? 'lookemploy_default_aes_key_32_chars_min_secure!!';
    
    $_ENV['AES_KEY'] = $def;
    $_SERVER['AES_KEY'] = $def;
    putenv("AES_KEY=$def");
    
    // Aviso em ambiente de desenvolvimento
    if ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development') === 'development') {
        error_log("AVISO: Usando chave AES padrão. Para produção, defina AES_KEY no .env");
    }
}

// ============================================
// 3. CONFIGURAÇÃO DO BANCO DE DADOS
// ============================================

$dbConfig = [
    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
    'name'     => $_ENV['DB_NAME'] ?? 'LookEmploy',
    'user'     => $_ENV['DB_USER'] ?? 'root',
    'pass'     => $_ENV['DB_PASS'] ?? '',
    'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    'port'     => $_ENV['DB_PORT'] ?? '3306',
    'timezone' => $_ENV['DB_TIMEZONE'] ?? '-03:00'
];

// ============================================
// 4. CONEXÃO COM PDO (MAIS SEGURA)
// ============================================

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Mais segurança
        PDO::ATTR_PERSISTENT         => false, // Conexões não persistentes
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '{$dbConfig['timezone']}'"
    ]);
    
    // Define configurações adicionais
    $pdo->exec("SET NAMES {$dbConfig['charset']}");
    $pdo->exec("SET time_zone = '{$dbConfig['timezone']}'");
    
    // Para debugging em desenvolvimento
    if (($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false) == 'true') {
        error_log("LookEmploy: Conectado ao banco {$dbConfig['name']} em {$dbConfig['host']}");
    }
    
} catch (PDOException $e) {
    // Mensagens de erro mais amigáveis
    $errorMessage = "ERRO DE CONEXÃO COM O BANCO DE DADOS\n";
    $errorMessage .= "=====================================\n";
    $errorMessage .= "Arquivo: " . basename(__FILE__) . "\n";
    $errorMessage .= "Mensagem: " . $e->getMessage() . "\n";
    $errorMessage .= "Banco: {$dbConfig['name']}@{$dbConfig['host']}\n";
    $errorMessage .= "Usuário: {$dbConfig['user']}\n";
    $errorMessage .= "=====================================\n";
    $errorMessage .= "Solução: Verifique:\n";
    $errorMessage .= "1. Servidor MySQL está rodando\n";
    $errorMessage .= "2. Banco '{$dbConfig['name']}' existe\n";
    $errorMessage .= "3. Usuário/senha estão corretos\n";
    $errorMessage .= "4. Arquivo .env configurado\n";
    
    // Em produção, mostra mensagem genérica
    if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
        die("Erro de conexão com o banco de dados. Contate o administrador.");
    } else {
        // Em desenvolvimento, mostra detalhes
        die("<pre>" . htmlspecialchars($errorMessage) . "</pre>");
    }
}

// ============================================
// 5. FUNÇÕES ÚTEIS PARA O SISTEMA
// ============================================

/**
 * Executa uma query SQL com parâmetros seguros
 */
function dbQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erro SQL: " . $e->getMessage() . " - Query: " . $sql);
        throw $e;
    }
}

/**
 * Retorna uma única linha
 */
function dbFetch($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Retorna todas as linhas
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insere dados e retorna o ID
 */
function dbInsert($table, $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    dbQuery($sql, $data);
    
    return $pdo->lastInsertId();
}

// ============================================
// 6. CONSTANTES DO SISTEMA
// ============================================

define('DB_PREFIX', $_ENV['DB_PREFIX'] ?? '');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');

// Retorna a instância do PDO para uso em outros arquivos
return $pdo;
?>
