<?php

namespace PS\Core\Database\Fields;

class IntegerField extends FieldBase
{
    private int $length = 10;
    private bool $unsigned = false;
    private bool $isBigInt = false;
    private bool $autoIncrement = false;
    private $datatype = FieldBase::INT;
    private array $fkSettings = [
        "tableName" => null,
        "column" => null
    ];

    public final function getMySQLDefinition()
    {
        return "`{$this->name}` " . ($this->isBigInt ? "BIGINT({$this->length})" : "INT({$this->length})") . ($this->unsigned ? ' UNSIGNED' : '') . ($this->autoIncrement ? ' AUTO_INCREMENT' : '');
    }

    public final function getFKConstraint(bool $onlyName = false): ?string
    {
        if ($this->fkSettings['column'] === null) return null;
        if ($onlyName) return $this->name . '_' . $this->fkSettings['tableName'] . '_' . $this->fkSettings['column'];
        return sprintf("ADD CONSTRAINT fk_###TABLENAME###_%s FOREIGN KEY (%s) REFERENCES %s(%s)", $this->name . '_' . $this->fkSettings['tableName'] . '_' . $this->fkSettings['column'], $this->name, $this->fkSettings['tableName'], $this->fkSettings['column']);
    }

    public final function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public final function setUnsigned(int $unsigned): self
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    public final function setBigInt(bool $val)
    {
        $this->isBigInt = $val;
        if ($val) {
            $this->datatype = FieldBase::BIGINT;
        }
        return $this;
    }

    public final function setAutoIncrement(bool $val)
    {
        $this->autoIncrement = $val;
        return $this;
    }

    public final function setForeignKey(string $tableName, string $column)
    {
        $this->fkSettings = [
            "tableName" => $tableName,
            "column" => $column
        ];
        $this->setUnsigned(true);
        return $this;
    }
}
