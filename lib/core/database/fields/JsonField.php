<?php

namespace PS\Core\Database\Fields;

class JsonField extends FieldBase
{
    public final function getMySQLDefinition()
    {
        return "`{$this->name}` JSON" . $this->getNotNullable();
    }
}
