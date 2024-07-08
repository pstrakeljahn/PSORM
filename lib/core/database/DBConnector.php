<?php

namespace PS\Core\Database;

use Config;
use PDO;
use PDOException;

class DBConnector
{
    private $host = Config::HOST;
    private $db_name = Config::DATABASE;
    private $username = Config::USERNAME;
    private $password = Config::PASSWORD;
    private $charset = Config::CHARSET;
    private $pdo;
    private $error;

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

    public function executeQuery($sql, $params = [], $returnPDO = false)
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
            throw new \Exception('Query failed: ' . $this->error);
            return null;
        }
    }
}
