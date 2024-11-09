<?php

namespace PS\Core\Database;

use ObjectPeer\UserPeer;
use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\IntegerField;

abstract class Entity
{
    readonly string $table;
    readonly string $entityName;
    readonly array $arrPrimaryKey;
    readonly bool $apiDisabled;
    private $preparedFields = null;
    protected $fields = [];
    public static string $primaryKey = 'ID';
    public bool $withoutMeta = false;
    public array $arrMetaFields = [];

    abstract public function fieldDefinition(): array;
    abstract protected function setEntitname(): string;
    abstract protected function setTabelName(): string;

    public function __construct()
    {
        $this->run();
    }

    protected function apiDisabled(): bool
    {
        return false;
    }

    public final function run(): void
    {
        $this->entityName = ucfirst($this->setEntitname());
        $this->fields = $this->fieldDefinition();
        $this->table = $this->setTabelName();
        $this->apiDisabled = $this->apiDisabled();
        $this->arrPrimaryKey = [
            (new IntegerField(static::$primaryKey))->setLength(10)->setRequired(true)->setUnsigned(true)->setAutoIncrement(true),
        ];
        if (!$this->withoutMeta) {
            $this->arrMetaFields = [
                (new DateField("_createdAt"))->setWithTime(true)->setNotNullable(false),
                (new IntegerField("_createdBy"))->setLength(10)->setNotNullable(false)->setUnsigned(true)->setForeignKey('users', 'ID'),
                (new DateField("_modfiedAt"))->setWithTime(true)->setNotNullable(false),
                (new IntegerField("_modifiedBy"))->setLength(10)->setNotNullable(false)->setUnsigned(true)->setForeignKey('users', 'ID')
            ];
        }
    }

    public final function _getFields(): array
    {
        if ($this->preparedFields === null) {
            $this->preparedFields === array();

            $this->preparedFields = [
                ...$this->arrPrimaryKey,
                ...$this->fields
            ];
            if (!$this->withoutMeta) {
                $this->preparedFields = [
                    ...$this->preparedFields,
                    ...$this->arrMetaFields
                ];
            }
        }
        return $this->preparedFields;
    }

    public final function _getField($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return null;
    }

    public final function setWithoutMeta(bool $val): self
    {
        $this->withoutMeta = $val;
        return $this;
    }

    public final function getCreateTableSQL()
    {
        $fieldsSQL = [];
        foreach ($this->_getFields() as $field) {
            $fieldsSQL[] = $field->getMySQLDefinition();
        }

        $fieldsSQL[] = 'PRIMARY KEY(`' . static::$primaryKey . '`)';

        $sql = 'CREATE TABLE IF NOT EXISTS `' . $this->table . '` (' . implode(', ', $fieldsSQL) . ')';
        return $sql;
    }

    public final function getFKConstraints(): array
    {
        $returnArray = [];
        foreach ($this->_getFields() as $field) {
            if (method_exists($field, 'getFKConstraint')) {
                if ($field->getFKConstraint() !== null) {
                    $returnArray[$field->getFKConstraint(true)] = str_replace("###TABLENAME###", $this->table, sprintf("ALTER TABLE %s %s", $this->table, $field->getFKConstraint()));
                }
            }
        }
        return $returnArray;
    }
}
