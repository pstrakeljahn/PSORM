<?php

namespace PS\Core\Database\Fields;

abstract class FieldBase
{
    private bool $notNullable = false;

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
