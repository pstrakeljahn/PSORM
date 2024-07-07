<?php

namespace PS\Core\Database\Fields;

class EnumField extends FieldBase
{
    private $allowedValues;

    public function getMySQLDefinition()
    {
        $values = "'" . implode("', '", $this->allowedValues) . "'";
        return "`{$this->name}` ENUM($values)";
    }

    public final function setValues(array $allowedValues): self
    {
        $this->allowedValues = $allowedValues;
        return $this;
    }
}
