<?php

namespace Entity;

use PS\Core\Database\Fields\StringField;
use PS\Core\Database\Entity;

class Template extends Entity
{
    protected function setEntityName(): string
    {
        return 'Template';
    }

    protected function setTableName(): string
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
