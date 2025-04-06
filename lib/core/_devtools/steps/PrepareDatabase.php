<?php

namespace PS\Core\_devtools\Steps;

use Config;
use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Helper\EntityHelper;
use PS\Core\Database\DBConnector;
use PS\Core\Database\Entity;
use PS\Core\Helper\Env;

/**
 * Step that prepares the database by creating or updating tables based on entities.
 */
final class PrepareDatabase extends BuildStep
{
    private DBConnector $db;

    /** @var array<string, array<string, string>> */
    private array $fkConstraints = [];

    protected function setStepName(): string
    {
        return 'Prepare Database';
    }

    protected function setDescription(): string
    {
        return 'Creates or updates database tables and foreign key constraints.';
    }

    public function run(): bool
    {
        $this->ensureDatabaseExists();

        $this->db = new DBConnector();

        foreach (EntityHelper::loadEntityClasses() as $entityInstance) {
            if (!$this->tableExists($entityInstance->table)) {
                $this->createTable($entityInstance);
            } else {
                $this->alterTable($entityInstance);
            }

            $this->fkConstraints[$entityInstance->table] = $entityInstance->getFKConstraints();
        }

        $this->applyForeignKeyConstraints();

        return true;
    }

    private function ensureDatabaseExists(): void
    {
        $db = new DBConnector(true);
        $dbName = Env::get("DB_NAME") ?? '';
        $charset = Env::get("DB_CHARSET") ?? 'utf8mb4';

        $result = $db->executeQuery("SHOW DATABASES LIKE '{$dbName}'");
        if (empty($result)) {
            $sql = sprintf(
                "CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s_general_ci",
                $dbName,
                $charset,
                $charset
            );
            $db->executeQuery($sql);
        }
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->db->executeQuery("SHOW TABLES LIKE '{$tableName}'");
        return !empty($result);
    }

    private function createTable(Entity $entity): void
    {
        $this->db->executeQuery($entity->getCreateTableSQL());
        echo "\t- Table '{$entity->table}' created\n";
    }

    private function alterTable(Entity $entity): void
    {
        $existingColumns = $this->getCurrentTableColumns($entity->table);
        $desiredColumns = $this->getDesiredColumns($entity);

        $alterStatements = [];

        foreach ($desiredColumns as $name => $definition) {
            if (isset($existingColumns[$name])) {
                if (!$this->compareColumnDefinition($existingColumns[$name], $definition)) {
                    $alterStatements[] = "MODIFY COLUMN `$name` $definition";
                }
            } else {
                $alterStatements[] = "ADD COLUMN `$name` $definition";
            }
        }

        foreach ($existingColumns as $name => $_) {
            if (!isset($desiredColumns[$name])) {
                $alterStatements[] = "DROP COLUMN `$name`";
            }
        }

        if (!empty($alterStatements)) {
            $sql = sprintf("ALTER TABLE `%s` %s", $entity->table, implode(', ', $alterStatements));
            $this->db->executeQuery($sql);
        }
    }

    /**
     * @param string $table
     * @return array<string, array<string, string>>
     */
    private function getCurrentTableColumns(string $table): array
    {
        $rows = $this->db->executeQuery("SHOW COLUMNS FROM `$table`");

        $result = [];
        foreach ($rows as $row) {
            $result[$row['Field']] = $row;
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @return array<string, string>
     */
    private function getDesiredColumns(Entity $entity): array
    {
        $result = [];

        foreach ($entity->_getFields() as $field) {
            $result[$field->name] = str_replace("`{$field->name}` ", '', $field->getMySQLDefinition());
        }

        return $result;
    }

    /**
     * @param array $existingColumn
     * @param string $desiredDefinition
     * @return bool
     */
    private function compareColumnDefinition(array $existingColumn, string $desiredDefinition): bool
    {
        $sql = strtolower($existingColumn['Type']);

        if ($existingColumn['Null'] === 'NO') {
            $sql .= ' not null';
        }

        if (!is_null($existingColumn['Default'])) {
            $sql .= " default '" . $existingColumn['Default'] . "'";
        }

        if (!empty($existingColumn['Extra'])) {
            $sql .= ' ' . strtolower($existingColumn['Extra']);
        }

        return trim($sql) === strtolower(trim($desiredDefinition));
    }

    private function applyForeignKeyConstraints(): void
    {
        $dbName = Env::get("DB_NAME");

        foreach ($this->fkConstraints as $table => $constraints) {
            foreach ($constraints as $fkKey => $query) {
                $fkName = sprintf("fk_%s_%s", $table, $fkKey);

                $checkQuery = "
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                    WHERE CONSTRAINT_SCHEMA = :schema 
                    AND TABLE_NAME = :table 
                    AND CONSTRAINT_NAME = :fk
                ";

                $result = $this->db->executeQuery($checkQuery, [
                    'schema' => $dbName,
                    'table' => $table,
                    'fk' => $fkName
                ]);

                if (!empty($result)) {
                    continue;
                }

                $this->db->executeQuery($query);
            }
        }
    }
}
