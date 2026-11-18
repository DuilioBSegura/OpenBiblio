<?php
/**
 * OpenBiblio - Configuração e Conexão com o Banco de Dados
 * Sistema de Gerenciamento para Bibliotecas Comunitárias
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'openbiblio');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

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
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
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
            $isDebug = in_array(strtolower((string)(getenv('APP_DEBUG') ?: 'false')), ['1', 'true', 'yes'], true);
            $detailsHtml = $isDebug
                ? "<hr><small>Detalhes técnicos: " . htmlspecialchars($e->getMessage()) . "</small>"
                : "";
            http_response_code(503);
            die("
                <div style='font-family: sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>
                    <h2>Erro de Conexão com o Banco de Dados</h2>
                    <p>Não foi possível conectar ao banco de dados MySQL/MariaDB do <strong>OpenBiblio</strong>.</p>
                    <p><strong>Causa provável:</strong> O serviço de banco de dados está indisponível ou as credenciais de ambiente não foram configuradas corretamente.</p>
                    {$detailsHtml}
                </div>
            ");
        }
    }

    return $pdo;
}
