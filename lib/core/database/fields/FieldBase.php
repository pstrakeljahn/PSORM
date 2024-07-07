<?php

namespace PS\Core\Database\Fields;

abstract class FieldBase
{
    readonly string $name;
    private bool $notNullable = false;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public final function setNotNullable(bool $val)
    {
        $this->notNullable = $val;
        return $this;
    }

    public final function getNotNullable(): string
    {
        return $this->notNullable ? ' NOT NULL' : '';
    }
}
