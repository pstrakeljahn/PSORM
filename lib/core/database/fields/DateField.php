<?php

namespace PS\Core\Database\Fields;

class DateField extends FieldBase
{
    private $withTime = false;
    private $datatype = FieldBase::DATE;

    public final function getMySQLDefinition()
    {
        $type = $this->withTime ? 'DATETIME' : 'DATE';
        return "`{$this->name}` $type" . $this->getNotNullable();
    }

    public final function setWithTime(bool $withTime): self
    {
        $this->withTime = $withTime;
        if($withTime) {
            $this->datatype = FieldBase::DATETIME;
        }
        return $this;
    }
}
