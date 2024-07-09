<?php

namespace PS\Core\Database;

use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\IntegerField;

abstract class Entity
{
    readonly string $table;
    public static string $primaryKey = 'ID';
    protected array $altPrimaryKeys = [];
    protected $fields = [];
    public bool $disableID = false;
    public bool $withoutMeta = false;
    readonly string $entityName;

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
    }

    public final function _getFields(): array
    {
        return $this->fields;
    }

    public final function _getField($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return null;
    }

    public final function setDisableID(bool $val, array $arrPrimaryKey): self
    {
        $this->altPrimaryKeys = $arrPrimaryKey;
        $this->disableID = $val;
        return $this;
    }

    public final function setWithoutMeta(bool $val): self
    {
        $this->withoutMeta = $val;
        return $this;
    }

    public final function getCreateTableSQL()
    {
        $fieldsSQL = [];
        if (!$this->disableID) {
            $this->fields = [
                (new IntegerField(static::$primaryKey))->setLength(10)->setRequired(true)->setUnsigned(true)->setAutoIncrement(true),
                ...$this->fields];
            if(!$this->withoutMeta) {
                $this->fields = [
                    ...$this->fields,
                    (new DateField("_createdAt"))->setWithTime(true),
                    (new IntegerField("_createdBy"))->setLength(10)->setUnsigned(true),
                    (new DateField("_modfiedAt"))->setWithTime(true),
                    (new IntegerField("_modifiedBy"))->setLength(10)->setUnsigned(true)
                ];
            }
        }
        foreach ($this->fields as $field) {
            $fieldsSQL[] = $field->getMySQLDefinition();
        }

        if (!$this->disableID) {
            $fieldsSQL[] = 'PRIMARY KEY(`' . static::$primaryKey . '`)';
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . $this->table . '` (' . implode(', ', $fieldsSQL) . ')';
        return $sql;
    }
}
