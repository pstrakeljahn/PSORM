<?php

namespace PS\Core\Database;

use PS\Core\Database\Fields\IntegerField;

abstract class Entity
{
    protected string $table;
    protected static string $primaryKey = 'ID';
    protected array $altPrimaryKeys = [];
    protected $fields = [];
    protected bool $disableID = false;
    protected string $entityName;

    public function __construct(string $entityName, array $fields)
    {
        $this->entityName = ucfirst($entityName);
        $this->fields = $fields;
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



    public final function getCreateTableSQL()
    {
        $fieldsSQL = [];
        if(!$this->disableID) {
            $this->fields = [(new IntegerField(static::$primaryKey))->setLength(10)->setUnsigned(true) , ...$this->fields];
        }
        foreach ($this->fields as $field) {
            $fieldsSQL[] = $field->getMySQLDefinition();
        }

        if(!$this->disableID) {
            $fieldsSQL[] = "PRIMARY KEY(`" . static::$primaryKey . "`)";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` (" . implode(", ", $fieldsSQL) . ")";
        return $sql;
    }
}