<?php

namespace PS\Core\_devtools\Steps;

use Config;
use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Helper\EntityHelper;
use PS\Core\Database\DBConnector;
use PS\Core\Database\Entity;

class PrepareDatabase extends BuildStep
{
    private DBConnector $db;
    private array $fkConstraints = [];
    protected function setStepName(): string
    {
        return 'Prepare Database';
    }

    protected function setDescription(): string
    {
        return 'Creates database tables';
    }

    public function run(): bool
    {
        $this->db = new DBConnector();
        foreach (EntityHelper::loadEntityClasses() as $entityInstance) {
            if (!$this->tableExists($entityInstance->table)) {
                $this->createTable($entityInstance);
            } else {
                $this->alterTable($entityInstance);
            }
            $this->fkConstraints[$entityInstance->table] = $entityInstance->getFKConstraints();
        }
        $this->executeFKConstraints();
        return true;
    }

    private function tableExists(string $tableName): bool
    {
        return count($this->db->executeQuery("SHOW TABLES LIKE '" . $tableName . "'")) > 0;
    }

    private function createTable(Entity $entityInstance): void
    {
        $this->db->executeQuery($entityInstance->getCreateTableSQL());
        echo "\t- Table '$entityInstance->table' created\n";
    }

    private function alterTable(Entity $entityInstance): void
    {
        $columnsQuery = "SHOW COLUMNS FROM `$entityInstance->table`";
        $columnsResult = $this->db->executeQuery($columnsQuery);

        $existingColumns = [];
        foreach ($columnsResult as $row) {
            $existingColumns[$row['Field']] = $row;
        }

        $desiredColumns = self::getDesiredColumns($entityInstance);

        $alterTableQueries = [];

        foreach ($desiredColumns as $column => $definition) {
            if (isset($existingColumns[$column])) {
                if (!$this->compareColumnDefinition($existingColumns[$column], $definition)) {
                    $alterTableQueries[] = "MODIFY COLUMN `$column` $definition";
                }
            } else {
                $alterTableQueries[] = "ADD COLUMN `$column` $definition";
            }
        }

        foreach ($existingColumns as $column => $row) {
            if (!isset($desiredColumns[$column])) {
                $alterTableQueries[] = "DROP COLUMN `$column`";
            }
        }

        if (!empty($alterTableQueries)) {
            $alterTableSQL = "ALTER TABLE `$entityInstance->table` " . implode(', ', $alterTableQueries);
            $this->db->executeQuery($alterTableSQL);
        }
    }

    private static function getDesiredColumns($entityInstance): array
    {
        $fields = $entityInstance->_getFields();
        $returnArray = [];
        foreach ($fields as $field) {
            $returnArray[$field->name] = str_replace("`$field->name` ", "", $field->getMySQLDefinition());
        }
        return $returnArray;
    }

    private function compareColumnDefinition(array $existingColumn, string $desiredDefinition): bool
    {
        $currentDefinition = $existingColumn['Type'];
        if ($existingColumn['Null'] === 'NO') {
            $currentDefinition .= ' NOT NULL';
        }
        if (!empty($existingColumn['Default'])) {
            $currentDefinition .= " DEFAULT '{$existingColumn['Default']}'";
        }
        if ($existingColumn['Extra']) {
            $currentDefinition .= ' ' . $existingColumn['Extra'];
        }

        return strtolower(trim($currentDefinition)) === strtolower(trim($desiredDefinition));
    }

    private function executeFKConstraints()
    {
        foreach ($this->fkConstraints as $tableName => $constraint) {
            foreach ($constraint as $fk => $query) {
                $fkName = sprintf("fk_%s_%s", $tableName, $fk);
                $res = $this->db->executeQuery(
                    sprintf(
                        "SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                        WHERE CONSTRAINT_SCHEMA = '%s' 
                        AND TABLE_NAME = '%s' 
                        AND CONSTRAINT_NAME = '%s'",
                        Config::DATABASE,
                        $tableName,
                        $fkName
                    )
                );
                if (count($res)) {
                    $res[0]["CONSTRAINT_NAME"] === $fkName;
                    continue 2;
                }
                $this->db->executeQuery($query);
            }
        }
    }
}
