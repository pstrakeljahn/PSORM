<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\_devtools\Helper\EntityHelper;
use PS\Core\Database\DBConnector;
use PS\Core\Database\Entity;
use PS\Core\Database\Fields\IntegerField;

class PrepareDatabase extends BuildStep
{
    private DBConnector $db;
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
        }
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
                if ($existingColumns[$column]['Type'] != $definition) {
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
        if(!$entityInstance->disableID) {
            $fields = [(new IntegerField($entityInstance::$primaryKey))->setLength(10)->setRequired(true)->setUnsigned(true), ...$entityInstance->_getFields()];
        } else {
            $fields = $entityInstance->_getFields();
        }
        $returnArray = [];
        foreach ($fields as $field) {
            $returnArray[$field->name] = str_replace("`$field->name` ", "", $field->getMySQLDefinition());
        }
        return $returnArray;
    }
}
