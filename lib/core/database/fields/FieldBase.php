<?php

namespace PS\Core\Database\Fields;

abstract class FieldBase
{
    readonly string $name;
    public bool $required = false;
    protected bool $notNullable = false;
    public bool $apiReadable = true;

    // Datatypes
    public const BIGINT = "bigint";
    public const BOOLEAN = "boolean";
    public const DATE = "date";
    public const DATETIME = "datetime";
    public const ENUM = "enum";
    public const INT = "int";
    public const VARCHAR = "varchar";

    public function __construct($name)
    {
        $this->name = $name;
    }

    public final function setNotNullable(bool $val)
    {
        $this->notNullable = $val;
        return $this;
    }

    public final function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }

    public final function setApiReadable(bool $val)
    {
        $this->apiReadable = $val;
        return $this;
    }

    public final function getNotNullable(): string
    {
        return $this->notNullable ? ' NOT NULL' : '';
    }
}
