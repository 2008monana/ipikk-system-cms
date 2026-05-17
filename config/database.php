<?php
/**
 * Configuração da Base de Dados - IPIKK
 * 
 * Este arquivo contém as configurações de conexão com o MySQL
 * e funções para executar consultas de forma segura.
 */

// ============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ============================================

define('DB_HOST', 'localhost');           // Servidor do banco de dados
define('DB_NAME', 'ipikk_db');            // Nome do banco de dados
define('DB_USER', 'root');                // Usuário do banco (alterar conforme configuração)
define('DB_PASS', '');                    // Senha do banco (alterar conforme configuração)
define('DB_CHARSET', 'utf8mb4');          // Charset para suporte a emojis e caracteres especiais

// ============================================
// CLASSE DE CONEXÃO COM O BANCO (PDO)
// ============================================

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado - Singleton Pattern
     * Garante que apenas uma conexão seja estabelecida
     */
    private function __construct() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, logue o erro em vez de exibir
            die('Erro de conexão com o banco de dados: ' . $e->getMessage());
        }
    }
    
    /**
     * Retorna a instância única da conexão (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Executa uma consulta preparada e retorna o statement
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Retorna uma única linha
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Retorna todas as linhas
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Desfaz uma transação
     */
    public function rollback() {
        return $this->connection->rollback();
    }
}

// ============================================
// FUNÇÃO DE ACESSO RÁPIDO
// ============================================

/**
 * Retorna a conexão com o banco de dados
 * Uso: $db = getDB();
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

// ============================================
// INICIALIZAÇÃO DA CONEXÃO (se necessário)
// ============================================

// Para usar em qualquer arquivo: $db = getDB();