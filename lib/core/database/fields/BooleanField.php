<?php

namespace PS\Core\Database\Fields;

class BooleanField extends FieldBase
{
    private $default = 0;
    readonly string $name;

    public function __construct($name)
    {
        $this->name = $name;    
    }

    public final function getMySQLDefinition()
    {
        return "{$this->name}` TINYINT(1)" . $this->getNotNullable() . " DEFAULT {$this->default}";
    }

    public final function setDefault(bool $default): self
    {
        $this->default = $default;
        return $this;
    }
}
