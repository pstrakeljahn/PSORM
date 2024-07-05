<?php

namespace PS\Core\Database\Fields;

class IntegerField extends FieldBase
{
    private int $length = 10;
    private bool $unsigned = false;
    private bool $isBigInt = false;
    readonly string $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public final function getMySQLDefinition()
    {
        return "`{$this->name}` " . ($this->isBigInt ? "BIGINT({$this->length})" : "INT({$this->length})") . ($this->unsigned ? ' UNSIGNED' : '');
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
}
