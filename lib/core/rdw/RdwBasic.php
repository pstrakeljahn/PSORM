<?php

namespace PS\Core\Rdw;

class RdwBasic
{
    public final function save(): void
    {

    }

    public final function delete(): void
    {

    }

    public final function setPropertiesAsArray(array $data) {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

}
