<?php

namespace Entity;

use PS\Core\Database\Fields\StringField;
use PS\Core\Database\Entity;

class Template extends Entity
{
    protected function setEntitname(): string
    {
        return 'Template';
    }

    protected function setTabelName(): string
    {
        return 'templates';
    }

    public function fieldDefinition(): array
    {
        $name = (new StringField('name'))
            ->setNotNullable(true)
            ->setRequired(true)
            ->setLength(255);

        return [
            $name
        ];
    }
}
