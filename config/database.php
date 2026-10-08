<?php
/**
 * OpenBiblio - Configuração e Conexão com o Banco de Dados
 * Sistema de Gerenciamento para Bibliotecas Comunitárias
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'openbiblio');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma instância de conexão PDO com o MySQL
 * 
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em caso de erro de conexão, grava no log e exibe mensagem amigável
            error_log("Erro na conexão com o banco de dados OpenBiblio: " . $e->getMessage());
            die("
                <div style='font-family: sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>
                    <h2>Erro de Conexão com o Banco de Dados</h2>
                    <p>Não foi possível conectar ao banco de dados MySQL/MariaDB do <strong>OpenBiblio</strong>.</p>
                    <p><strong>Causa provável:</strong> O serviço do MySQL não está ativo no XAMPP ou o banco de dados 'openbiblio' não foi criado.</p>
                    <hr>
                    <small>Detalhes técnicos: " . htmlspecialchars($e->getMessage()) . "</small>
                </div>
            ");
        }
    }

    return $pdo;
}
