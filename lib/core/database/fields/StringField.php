<?php

namespace PS\Core\Database\Fields;

class StringField extends FieldBase
{
    private $length = 255;
    private $datatype = FieldBase::VARCHAR;

    public final function getMySQLDefinition()
    {
        return "`{$this->name}` VARCHAR({$this->length})" . $this->getNotNullable();
    }

    public final function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }
}
