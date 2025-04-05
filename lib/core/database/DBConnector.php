<?php

namespace PS\Core\Database;

use PDO;
use PDOException;
use PS\Core\Helper\Env;
use PS\Core\Logging\Logging;

/**
 * Class DBConnector
 *
 * Manages the PDO connection and allows execution of parameterized SQL queries.
 */
class DBConnector
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;
    private string $port;

    /** @var PDO */
    private PDO $pdo;

    /** @var string|null */
    private ?string $error = null;

    /**
     * DBConnector constructor.
     *
     * Initializes PDO connection using environment configuration.
     *
     * @param bool $withoutDbSelection If true, skips the DB selection in DSN.
     * @throws \Exception If the connection fails.
     */
    public function __construct(bool $withoutDbSelection = false)
    {
        $this->host     = Env::get("DB_HOST");
        $this->dbName   = Env::get("DB_NAME");
        $this->username = Env::get("DB_USER");
        $this->password = Env::get("DB_PASS");
        $this->charset  = Env::get("DB_CHARSET") ?? 'utf8mb4';
        $this->port     = Env::get("DB_PORT") ?? '3306';

        $dsn = "mysql:host={$this->host};charset={$this->charset};port={$this->port};";
        if (!$withoutDbSelection) {
            $dsn .= "dbname={$this->dbName};";
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new \Exception('Cannot connect to database: ' . $this->error);
        }
    }

    /**
     * Returns the active PDO connection.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Executes a parameterized SQL query.
     *
     * @param string $sql             The SQL statement with named placeholders.
     * @param array $params           Associative array of parameters (e.g. ['id' => 1]).
     * @param bool $returnPDO         If true, returns the PDO instance instead of results.
     * @param bool $throwException    If true, rethrows exceptions. Otherwise returns null.
     *
     * @return array|PDO|null         Query result as array, PDO instance, or null on failure.
     * @throws \Exception             If query fails and $throwException is true.
     */
    public function executeQuery(string $sql, array $params = [], bool $returnPDO = false, bool $throwException = true): array|PDO|null
    {
        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $paramType = match (true) {
                    is_null($value) => PDO::PARAM_NULL,
                    is_int($value)  => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    default         => PDO::PARAM_STR,
                };

                $stmt->bindValue(':' . $key, $value, $paramType);
            }

            $stmt->execute();

            return $returnPDO ? $this->pdo : $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();

            Logging::getInstance()->add(Logging::LOG_TYPE_DB, 'Query failed: ' . $this->error);

            if ($throwException) {
                throw new \Exception('Query failed: ' . $this->error);
            }

            return null;
        }
    }
}
