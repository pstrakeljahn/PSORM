<?php

namespace PS\Core\Database;

use Config;
use PDO;
use PDOException;
use PS\Core\Logging\Logging;

class DBConnector
{
    private string $host = Config::HOST;
    private string $db_name = Config::DATABASE;
    private string $username = Config::USERNAME;
    private string $password = Config::PASSWORD;
    private string $charset = Config::CHARSET;
    private PDO $pdo;
    private string $error;

    public function __construct()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->db_name;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new \Exception('Cannot connect to Database: ' . $this->error);
            die();
        }
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function executeQuery($sql, $params = [], $returnPDO = false, $throwException = true)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                if ($value === null) {
                    $paramType = PDO::PARAM_NULL;
                } else {
                    $paramType = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
                }
                $stmt->bindValue(':' . $key, $value, $paramType);
            }
            $stmt->execute();
            if ($returnPDO) {
                return $this->pdo;
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $log = Logging::getInstance();
            $log->add(Logging::LOG_TYPE_DB, 'Query failed: ' . $this->error);
            if ($throwException) {
                throw new \Exception('Query failed: ' . $this->error);
            }
            return null;
        }
    }
}
