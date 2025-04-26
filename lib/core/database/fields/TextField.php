<?php

namespace PS\Core\Database\Fields;

class TextField extends FieldBase
{
    public final function getMySQLDefinition()
    {
        return "`{$this->name}` TEXT" . $this->getNotNullable();
    }
}
