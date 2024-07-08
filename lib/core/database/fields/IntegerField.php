<?php

namespace PS\Core\Database\Fields;

class IntegerField extends FieldBase
{
    private int $length = 10;
    private bool $unsigned = false;
    private bool $isBigInt = false;
    private bool $autoIncrement = false;

    public final function getMySQLDefinition()
    {
        return "`{$this->name}` " . ($this->isBigInt ? "BIGINT({$this->length})" : "INT({$this->length})") . ($this->unsigned ? ' UNSIGNED' : '') . ($this->autoIncrement ? ' AUTO_INCREMENT' : '');
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
        return $this;
    }

    public final function setAutoIncrement(bool $val)
    {
        $this->autoIncrement = $val;
        return $this;
    }
}
