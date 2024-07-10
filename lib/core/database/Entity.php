<?php

namespace PS\Core\Database;

use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\IntegerField;

abstract class Entity
{
    readonly string $table;
    public static string $primaryKey = 'ID';
    protected $fields = [];
    public bool $withoutMeta = false;
    readonly string $entityName;
    readonly array $arrPrimaryKey;
    readonly array $arrMetaFields;

    abstract public function fieldDefinition(): array;
    abstract protected function setEntitname(): string;
    abstract protected function setTabelName(): string;

    public function __construct()
    {
        $this->run();
    }

    public final function run(): void
    {
        $this->entityName = ucfirst($this->setEntitname());
        $this->fields = $this->fieldDefinition();
        $this->table = $this->setTabelName();
        $this->arrPrimaryKey = [
            (new IntegerField(static::$primaryKey))->setLength(10)->setRequired(true)->setUnsigned(true)->setAutoIncrement(true),
        ];
        if (!$this->withoutMeta) {
            $this->arrMetaFields = [
                (new DateField("_createdAt"))->setWithTime(true),
                (new IntegerField("_createdBy"))->setLength(10)->setUnsigned(true),
                (new DateField("_modfiedAt"))->setWithTime(true),
                (new IntegerField("_modifiedBy"))->setLength(10)->setUnsigned(true)
            ];
        }
    }

    public final function _getFields(): array
    {
        $this->fields = [
            ...$this->arrPrimaryKey,
            ...$this->fields
        ];
        if (!$this->withoutMeta) {
            $this->fields = [
                ...$this->fields,
                ...$this->arrMetaFields
            ];
        }
        return $this->fields;
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
}
