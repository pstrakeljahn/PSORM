<?php

namespace Entity;

use PS\Core\Database\Entity;
use PS\Core\Database\Fields\BooleanField;
use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\StringField;

class ServiceEntity extends Entity
{
    public bool $withoutMeta = true;
    public bool $apiDisabled = true;

    protected function setEntityName(): string
    {
        return 'Service';
    }

    protected function setTableName(): string
    {
        return 'services';
    }

    public function fieldDefinition(): array
    {
        $className = (new StringField('className'))
            ->setNotNullable(false)
            ->setRequired(true)
            ->setLength(255);

        $startTime = (new DateField('startTime'))
            ->setNotNullable(false)
            ->setWithTime(true);

        $active = (new BooleanField('active'))
            ->setNotNullable(true)
            ->setDefault(true);

        return [
            $className,
            $active,
            $startTime
        ];
    }
}
